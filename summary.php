<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia']);

$pdo = Database::connection();
$overview = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(gender = 'L') AS male,
        SUM(gender = 'P') AS female,
        COUNT(DISTINCT sport_id) AS sports,
        SUM(EXISTS (SELECT 1 FROM athlete_tests at WHERE at.master_person_id = mp.id)) AS tested,
        SUM(NOT EXISTS (SELECT 1 FROM athlete_tests at WHERE at.master_person_id = mp.id)) AS not_tested
     FROM master_people mp
     WHERE person_type = 'Atlet' AND is_active = 1"
)->fetch();

$statusRows = $pdo->query(
    "SELECT COALESCE(NULLIF(development_status, ''), 'Belum Ditentukan') AS label,
        COUNT(*) AS total,
        SUM(EXISTS (SELECT 1 FROM athlete_tests at WHERE at.master_person_id = mp.id)) AS tested
     FROM master_people mp
     WHERE person_type = 'Atlet' AND is_active = 1
     GROUP BY COALESCE(NULLIF(development_status, ''), 'Belum Ditentukan')
     ORDER BY FIELD(label, 'Andalan', 'Prioritas', 'Potensial', 'Belum Ditentukan'), label"
)->fetchAll();

$sportRows = $pdo->query(
    "SELECT s.name,
        COUNT(mp.id) AS total,
        SUM(mp.gender = 'L') AS male,
        SUM(mp.gender = 'P') AS female,
        SUM(EXISTS (SELECT 1 FROM athlete_tests at WHERE at.master_person_id = mp.id)) AS tested
     FROM sports s
     JOIN master_people mp ON mp.sport_id = s.id AND mp.person_type = 'Atlet' AND mp.is_active = 1
     GROUP BY s.id, s.name
     ORDER BY total DESC, s.name"
)->fetchAll();

$notTested = $pdo->query(
    "SELECT mp.name, mp.gender, mp.development_status, s.name AS sport
     FROM master_people mp
     JOIN sports s ON s.id = mp.sport_id
     LEFT JOIN athlete_tests at ON at.master_person_id = mp.id
     WHERE mp.person_type = 'Atlet' AND mp.is_active = 1 AND at.id IS NULL
     ORDER BY FIELD(mp.development_status, 'Andalan', 'Prioritas', 'Potensial'), s.name, mp.name
     LIMIT 100"
)->fetchAll();

view('summary', compact('overview', 'statusRows', 'sportRows', 'notTested') + ['pageTitle' => 'Summary Atlet']);
