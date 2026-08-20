<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

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
$statement = Database::connection()->prepare($sql);
$statement->execute($params);
$tests = $statement->fetchAll();
$sports = Database::connection()->query('SELECT DISTINCT sport FROM athlete_tests ORDER BY sport')->fetchAll(PDO::FETCH_COLUMN);
view('reports', compact('tests', 'sports', 'q', 'sport', 'from', 'to') + ['pageTitle' => 'Laporan']);
