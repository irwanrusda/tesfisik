<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);
require_crud_open();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$pdo = Database::connection();
$statement = $pdo->prepare('SELECT * FROM athlete_tests WHERE id = ?');
$statement->execute([$id]);
$test = $statement->fetch();
if (!$test) {
    http_response_code(404);
    exit('Data tes tidak ditemukan.');
}

if (request_method('POST')) {
    verify_csrf();
    set_old($_POST);
    $photoFiles = uploaded_files('documentation_photos');
    $masterPersonId = (int) ($_POST['master_person_id'] ?? 0);
    $height = nullable_number($_POST['height_cm'] ?? null);
    $weight = nullable_number($_POST['weight_kg'] ?? null);
    if ($masterPersonId < 1) { flash('error', 'Pilih atlet dari master data.'); redirect('test-edit.php?id=' . $id); }
    if ($height !== null && $height <= 0) { flash('error', 'Tinggi badan harus lebih dari 0 cm.'); redirect('test-edit.php?id=' . $id); }
    if ($weight !== null && $weight <= 0) { flash('error', 'Berat badan harus lebih dari 0 kg.'); redirect('test-edit.php?id=' . $id); }
    if (trim((string) ($_POST['test_date'] ?? '')) === '') { flash('error', 'Tanggal tes wajib diisi.'); redirect('test-edit.php?id=' . $id); }
    if (trim((string) ($_POST['birth_date'] ?? '')) !== '') {
        try { calculate_age($_POST['birth_date'], $_POST['test_date']); } catch (Throwable $exception) { flash('error', $exception->getMessage()); redirect('test-edit.php?id=' . $id); }
    }

    $pdo->beginTransaction();
    try {
        $masterStatement = $pdo->prepare("SELECT master_people.id, master_people.name, master_people.gender, sports.name AS sport FROM master_people JOIN sports ON sports.id = master_people.sport_id WHERE master_people.id = ? AND master_people.person_type = 'Atlet' AND master_people.is_active = 1");
        $masterStatement->execute([$masterPersonId]);
        $masterPerson = $masterStatement->fetch();
        if (!$masterPerson) {
            throw new RuntimeException('Atlet harus dipilih dari master data aktif.');
        }
        $bmi = ($height !== null && $weight !== null && $height > 0 && $weight > 0) ? round($weight / (($height / 100) ** 2), 2) : null;
        $update = $pdo->prepare('UPDATE athlete_tests SET master_person_id = ?, athlete_name = ?, birth_place = ?, birth_date = ?, sport = ?, gender = ?, height_cm = ?, weight_kg = ?, bmi = ?, test_date = ?, test_place = ?, notes = ? WHERE id = ?');
        $update->execute([
            $masterPersonId, $masterPerson['name'], trim((string) ($_POST['birth_place'] ?? '')) ?: null, trim((string) ($_POST['birth_date'] ?? '')) ?: null, $masterPerson['sport'], $masterPerson['gender'], $height, $weight, $bmi,
            $_POST['test_date'], trim($_POST['test_place'] ?? '') ?: 'Padang', trim($_POST['notes'] ?? '') ?: null, $id,
        ]);
        if (isset($_POST['results']) && is_array($_POST['results'])) {
            $upsert = $pdo->prepare('INSERT INTO test_results (athlete_test_id, test_code, result_value, unit, category, examiner_notes) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE result_value = VALUES(result_value), unit = VALUES(unit), category = VALUES(category), examiner_notes = VALUES(examiner_notes)');
            foreach (physical_test_items() as $code => $item) {
                $upsert->execute([$id, $code, nullable_number($_POST['results'][$code]['value'] ?? null), $item['unit'], trim((string) ($_POST['results'][$code]['category'] ?? '')) ?: null, trim((string) ($_POST['results'][$code]['notes'] ?? '')) ?: null]);
            }
        }
        $storedPhotos = store_test_photos($pdo, $id, $photoFiles, (int) Auth::user()['id']);
        $deleteIds = array_values(array_filter(array_map('intval', $_POST['delete_photos'] ?? [])));
        $deletedFiles = [];
        if ($deleteIds) {
            $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
            $photoQuery = $pdo->prepare("SELECT id, file_name FROM test_photos WHERE athlete_test_id = ? AND id IN ({$placeholders})");
            $photoQuery->execute(array_merge([$id], $deleteIds));
            $deletedFiles = $photoQuery->fetchAll();
            $deleteStatement = $pdo->prepare("DELETE FROM test_photos WHERE athlete_test_id = ? AND id IN ({$placeholders})");
            $deleteStatement->execute(array_merge([$id], $deleteIds));
        }
        write_audit_log($pdo, 'update', 'tes_fisik', ['id' => $id, 'number' => $test['test_number'], 'athlete_name' => $masterPerson['name'], 'sport' => $masterPerson['sport']], ['tanggal_tes' => $_POST['test_date']]);
        $pdo->commit();
        foreach ($deletedFiles as $photo) {
            $path = test_photo_path($photo['file_name']);
            if (is_file($path)) unlink($path);
        }
        clear_old();
        flash('success', 'Data tes berhasil diperbarui.');
        redirect('test-view.php?id=' . $id);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($storedPhotos ?? [] as $path) {
            if (is_file($path)) unlink($path);
        }
        flash('error', 'Perubahan data gagal disimpan: ' . $exception->getMessage());
        redirect('test-edit.php?id=' . $id);
    }
}

$resultStatement = $pdo->prepare('SELECT test_code, result_value, category, examiner_notes FROM test_results WHERE athlete_test_id = ?');
$resultStatement->execute([$id]);
$results = [];
foreach ($resultStatement->fetchAll() as $result) $results[$result['test_code']] = $result;
$athletes = $pdo->query("SELECT master_people.id, master_people.name, master_people.gender, master_people.achievement, master_people.development_status, sports.name AS sport FROM master_people JOIN sports ON sports.id = master_people.sport_id WHERE master_people.person_type = 'Atlet' AND master_people.is_active = 1 ORDER BY sports.name, master_people.name")->fetchAll();
$photoStatement = $pdo->prepare('SELECT id, original_name, file_size FROM test_photos WHERE athlete_test_id = ? ORDER BY created_at, id');
$photoStatement->execute([$id]);
$photos = $photoStatement->fetchAll();
view('test-form', ['pageTitle' => 'Edit Data Tes', 'test' => $test, 'results' => $results, 'formAction' => 'test-edit.php?id=' . $id, 'athletes' => $athletes, 'photos' => $photos, 'showMeasurements' => false]);
