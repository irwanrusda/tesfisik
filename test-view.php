<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::connection();
$statement = $pdo->prepare('SELECT athlete_tests.*, users.name AS creator_name FROM athlete_tests JOIN users ON users.id = athlete_tests.created_by WHERE athlete_tests.id = ?');
$statement->execute([$id]);
$test = $statement->fetch();
if (!$test) { http_response_code(404); exit('Data tes tidak ditemukan.'); }
$resultStatement = $pdo->prepare('SELECT * FROM test_results WHERE athlete_test_id = ?');
$resultStatement->execute([$id]);
$results = [];
foreach ($resultStatement->fetchAll() as $item) $results[$item['test_code']] = $item;
$photoStatement = $pdo->prepare('SELECT id, original_name, file_size FROM test_photos WHERE athlete_test_id = ? ORDER BY created_at, id');
$photoStatement->execute([$id]);
$photos = $photoStatement->fetchAll();
view('test-view', compact('test', 'results', 'photos') + ['pageTitle' => 'Detail Tes']);
