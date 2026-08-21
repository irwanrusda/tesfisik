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
$sql = 'SELECT id, test_number, athlete_name, birth_date, sport, gender, test_date, bmi, created_at FROM athlete_tests' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY test_date DESC, id DESC';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$tests = $statement->fetchAll();
$sports = $pdo->query('SELECT DISTINCT sport FROM athlete_tests ORDER BY sport')->fetchAll(PDO::FETCH_COLUMN);
view('reports', compact('tests', 'sports', 'q', 'sport', 'from', 'to') + ['pageTitle' => 'Laporan']);
