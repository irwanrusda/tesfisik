<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireRole('superadmin');

$pdo = Database::connection();
$totalTests = (int) $pdo->query('SELECT COUNT(*) FROM athlete_tests')->fetchColumn();
$totalAthletes = (int) $pdo->query('SELECT COUNT(DISTINCT athlete_name, birth_date) FROM athlete_tests')->fetchColumn();
$totalSports = (int) $pdo->query('SELECT COUNT(DISTINCT sport) FROM athlete_tests')->fetchColumn();
$thisMonth = (int) $pdo->query("SELECT COUNT(*) FROM athlete_tests WHERE DATE_FORMAT(test_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetchColumn();
$recent = $pdo->query('SELECT id, test_number, athlete_name, sport, test_date, bmi FROM athlete_tests ORDER BY created_at DESC LIMIT 6')->fetchAll();
$sports = $pdo->query('SELECT sport, COUNT(*) AS total FROM athlete_tests GROUP BY sport ORDER BY total DESC, sport ASC LIMIT 5')->fetchAll();

view('dashboard', [
    'pageTitle' => 'Dashboard',
    'totalTests' => $totalTests,
    'totalAthletes' => $totalAthletes,
    'totalSports' => $totalSports,
    'thisMonth' => $thisMonth,
    'recent' => $recent,
    'sports' => $sports,
]);
