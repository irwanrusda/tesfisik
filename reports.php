<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

$pdo = Database::connection();
if (request_method('POST')) {
    Auth::requireRole('superadmin');
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    try {
        $photoStatement = $pdo->prepare('SELECT file_name FROM test_photos WHERE athlete_test_id = ?');
        $photoStatement->execute([$id]);
        $photoFiles = $photoStatement->fetchAll(PDO::FETCH_COLUMN);
        $recordStatement = $pdo->prepare('SELECT test_number, athlete_name, sport, test_date FROM athlete_tests WHERE id = ?');
        $recordStatement->execute([$id]);
        $record = $recordStatement->fetch();
        if (!$record) throw new RuntimeException('Data tes tidak ditemukan.');
        write_audit_log($pdo, 'delete', 'tes_fisik', ['id' => $id, 'number' => $record['test_number'], 'athlete_name' => $record['athlete_name'], 'sport' => $record['sport']], ['tanggal_tes' => $record['test_date']]);
        $deleteStatement = $pdo->prepare('DELETE FROM athlete_tests WHERE id = ?');
        $deleteStatement->execute([$id]);
        if ($deleteStatement->rowCount() < 1) {
            throw new RuntimeException('Data tes tidak ditemukan.');
        }
        foreach ($photoFiles as $fileName) {
            $path = test_photo_path($fileName);
            if (is_file($path)) unlink($path);
        }
        flash('success', 'Data tes dan dokumentasinya berhasil dihapus.');
    } catch (Throwable $exception) {
        flash('error', 'Data tes gagal dihapus: ' . $exception->getMessage());
    }
    redirect('reports.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$sport = trim((string) ($_GET['sport'] ?? ''));
$from = (string) ($_GET['from'] ?? '');
$to = (string) ($_GET['to'] ?? '');
$where = [];
$params = [];
if ($q !== '') { $where[] = '(athlete_name LIKE ? OR test_number LIKE ?)'; $params[] = "%{$q}%"; $params[] = "%{$q}%"; }
if ($sport !== '') { $where[] = 'sport = ?'; $params[] = $sport; }
if ($from !== '') { $where[] = 'test_date >= ?'; $params[] = $from; }
if ($to !== '') { $where[] = 'test_date <= ?'; $params[] = $to; }
$sql = 'SELECT athlete_tests.id, athlete_tests.test_number, athlete_tests.athlete_name, athlete_tests.birth_date, athlete_tests.sport, athlete_tests.gender, athlete_tests.test_date, athlete_tests.bmi, athlete_tests.created_at, users.name AS creator_name FROM athlete_tests JOIN users ON users.id = athlete_tests.created_by' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY athlete_tests.test_date DESC, athlete_tests.id DESC';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$tests = $statement->fetchAll();
$sports = $pdo->query('SELECT DISTINCT sport FROM athlete_tests ORDER BY sport')->fetchAll(PDO::FETCH_COLUMN);
view('reports', compact('tests', 'sports', 'q', 'sport', 'from', 'to') + ['pageTitle' => 'Laporan']);
