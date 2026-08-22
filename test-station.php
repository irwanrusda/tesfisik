<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

$items = physical_test_items();
$code = (string) ($_GET['code'] ?? $_POST['test_code'] ?? '');
if (!isset($items[$code])) {
    $firstCode = array_key_first($items);
    redirect('test-station.php?code=' . urlencode((string) $firstCode));
}
$item = $items[$code];
$pdo = Database::connection();
$conditions = test_conditions($pdo);

$date = trim((string) ($_GET['date'] ?? $_POST['date'] ?? date('Y-m-d')));
if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
$status = (string) ($_GET['status'] ?? 'waiting');
if (!in_array($status, ['waiting', 'done', 'all'], true)) {
    $status = 'waiting';
}
$q = trim((string) ($_GET['q'] ?? ''));
$categories = ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'];

if (request_method('POST')) {
    require_crud_open($pdo);
    verify_csrf();
    $athleteTestId = (int) ($_POST['athlete_test_id'] ?? 0);
    $value = nullable_number($_POST['result_value'] ?? null);
    $category = trim((string) ($_POST['category'] ?? ''));
    $notes = trim((string) ($_POST['examiner_notes'] ?? ''));
    $photoFiles = uploaded_files('station_photos');

    if ($athleteTestId < 1) {
        flash('error', 'Pilih atlet yang valid.');
        redirect('test-station.php?code=' . urlencode($code) . '&date=' . urlencode($date) . '&status=' . urlencode($status) . '&q=' . urlencode($q));
    }
    if ($value === null || $value < 0) {
        flash('error', 'Skor wajib diisi dan tidak boleh negatif.');
        redirect('test-station.php?code=' . urlencode($code) . '&date=' . urlencode($date) . '&status=' . urlencode($status) . '&q=' . urlencode($q));
    }
    if ($category !== '' && !in_array($category, $categories, true)) {
        flash('error', 'Kategori tidak valid.');
        redirect('test-station.php?code=' . urlencode($code) . '&date=' . urlencode($date) . '&status=' . urlencode($status) . '&q=' . urlencode($q));
    }

    $testStatement = $pdo->prepare('SELECT id, test_number, athlete_name, sport, gender FROM athlete_tests WHERE id = ?');
    $testStatement->execute([$athleteTestId]);
    $test = $testStatement->fetch();
    if (!$test) {
        flash('error', 'Data atlet tidak ditemukan.');
        redirect('test-station.php?code=' . urlencode($code) . '&date=' . urlencode($date) . '&status=' . urlencode($status) . '&q=' . urlencode($q));
    }

    $inputDefinition = physical_test_input_definition($code, $test['gender'], $conditions);
    $pdo->beginTransaction();
    try {
        $upsert = $pdo->prepare('INSERT INTO test_results (athlete_test_id, test_code, result_value, unit, category, examiner_notes) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE result_value = VALUES(result_value), unit = VALUES(unit), category = VALUES(category), examiner_notes = VALUES(examiner_notes)');
        $upsert->execute([$athleteTestId, $code, $value, $inputDefinition['unit'], $category ?: null, $notes ?: null]);
        $storedPhotos = store_test_photos($pdo, $athleteTestId, $photoFiles, (int) Auth::user()['id'], $code);
        write_audit_log($pdo, 'update', 'tes_fisik_pos', ['id' => $athleteTestId, 'number' => $test['test_number'], 'athlete_name' => $test['athlete_name'], 'sport' => $test['sport']], ['test_code' => $code, 'method' => $inputDefinition['method'], 'value' => $value, 'unit' => $inputDefinition['unit'], 'category' => $category ?: null, 'foto_baru' => count($photoFiles)]);
        $pdo->commit();
        flash('success', 'Hasil ' . $item['method'] . ' untuk ' . $test['athlete_name'] . ' berhasil disimpan.');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($storedPhotos ?? [] as $path) if (is_file($path)) unlink($path);
        flash('error', 'Hasil pos gagal disimpan: ' . $exception->getMessage());
    }
    redirect('test-station.php?code=' . urlencode($code) . '&date=' . urlencode($date) . '&status=' . urlencode($status) . '&q=' . urlencode($q));
}

$where = ['athlete_tests.test_date = ?'];
$params = [$date];
if ($q !== '') {
    $where[] = '(athlete_tests.athlete_name LIKE ? OR athlete_tests.sport LIKE ? OR athlete_tests.test_number LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($status === 'waiting') {
    $where[] = '(test_results.result_value IS NULL)';
} elseif ($status === 'done') {
    $where[] = '(test_results.result_value IS NOT NULL)';
}

$sql = "SELECT athlete_tests.id, athlete_tests.test_number, athlete_tests.athlete_name, athlete_tests.sport, athlete_tests.gender, athlete_tests.birth_date, athlete_tests.test_date, athlete_tests.test_place, test_results.result_value, test_results.category, test_results.examiner_notes
        FROM athlete_tests
        LEFT JOIN test_results ON test_results.athlete_test_id = athlete_tests.id AND test_results.test_code = ?
        WHERE " . implode(' AND ', $where) . "
        ORDER BY CASE WHEN test_results.result_value IS NULL THEN 0 ELSE 1 END, athlete_tests.sport, athlete_tests.athlete_name";
$statement = $pdo->prepare($sql);
$statement->execute(array_merge([$code], $params));
$rows = $statement->fetchAll();
if ($rows) {
    $testIds = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($testIds), '?'));
    $photoStatement = $pdo->prepare("SELECT id, athlete_test_id, original_name, file_size FROM test_photos WHERE test_code = ? AND athlete_test_id IN ({$placeholders}) ORDER BY created_at, id");
    $photoStatement->execute(array_merge([$code], $testIds));
    $photosByTest = [];
    foreach ($photoStatement->fetchAll() as $photo) $photosByTest[$photo['athlete_test_id']][] = $photo;
    foreach ($rows as &$row) $row['station_photos'] = $photosByTest[$row['id']] ?? [];
    foreach ($rows as &$row) $row['input_definition'] = physical_test_input_definition($code, $row['gender'], $conditions);
    unset($row);
}

$countStatement = $pdo->prepare("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN test_results.result_value IS NULL THEN 1 ELSE 0 END) AS waiting,
    SUM(CASE WHEN test_results.result_value IS NOT NULL THEN 1 ELSE 0 END) AS done
    FROM athlete_tests
    LEFT JOIN test_results ON test_results.athlete_test_id = athlete_tests.id AND test_results.test_code = ?
    WHERE athlete_tests.test_date = ?");
$countStatement->execute([$code, $date]);
$counts = $countStatement->fetch() ?: ['total' => 0, 'waiting' => 0, 'done' => 0];

view('test-station', compact('items', 'code', 'item', 'date', 'status', 'q', 'rows', 'counts', 'categories', 'conditions') + ['pageTitle' => 'Pos ' . $item['method']]);
