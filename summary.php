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
     ORDER BY (SUM(EXISTS (SELECT 1 FROM athlete_tests at2 WHERE at2.master_person_id = mp.id)) / NULLIF(COUNT(mp.id), 0)) DESC,
              SUM(EXISTS (SELECT 1 FROM athlete_tests at3 WHERE at3.master_person_id = mp.id)) DESC,
              COUNT(mp.id) DESC,
              s.name"
)->fetchAll();

$stationDefinitions = physical_test_items();
$stationResultRows = $pdo->query(
    "SELECT at.sport, tr.test_code, COUNT(DISTINCT at.master_person_id) AS tested
     FROM test_results tr
     JOIN athlete_tests at ON at.id = tr.athlete_test_id
     WHERE tr.result_value IS NOT NULL AND at.master_person_id IS NOT NULL
     GROUP BY at.sport, tr.test_code"
)->fetchAll();
$stationResultsBySport = [];
foreach ($stationResultRows as $row) {
    $stationResultsBySport[$row['sport']][$row['test_code']] = (int) $row['tested'];
}
$stationCoverageRows = [];
foreach ($sportRows as $sportRow) {
    $totalAthletes = (int) $sportRow['total'];
    $completedStations = 0;
    $notStartedStations = [];
    $incompleteStations = [];
    foreach ($stationDefinitions as $code => $definition) {
        $tested = (int) ($stationResultsBySport[$sportRow['name']][$code] ?? 0);
        if ($tested >= $totalAthletes && $totalAthletes > 0) {
            $completedStations++;
        } elseif ($tested === 0) {
            $notStartedStations[] = $definition['method'];
        } else {
            $incompleteStations[] = [
                'method' => $definition['method'],
                'tested' => $tested,
                'total' => $totalAthletes,
            ];
        }
    }
    $stationCoverageRows[] = [
        'sport' => $sportRow['name'],
        'athletes' => $totalAthletes,
        'completed' => $completedStations,
        'total_stations' => count($stationDefinitions),
        'coverage' => count($stationDefinitions) > 0 ? round($completedStations / count($stationDefinitions) * 100, 1) : 0,
        'not_started' => $notStartedStations,
        'incomplete' => $incompleteStations,
    ];
}
usort($stationCoverageRows, static function (array $left, array $right): int {
    return $left['coverage'] <=> $right['coverage']
        ?: count($right['not_started']) <=> count($left['not_started'])
        ?: strcmp($left['sport'], $right['sport']);
});

$notTested = $pdo->query(
    "SELECT mp.name, mp.gender, mp.development_status, s.name AS sport
     FROM master_people mp
     JOIN sports s ON s.id = mp.sport_id
     LEFT JOIN athlete_tests at ON at.master_person_id = mp.id
     WHERE mp.person_type = 'Atlet' AND mp.is_active = 1 AND at.id IS NULL
     ORDER BY FIELD(mp.development_status, 'Andalan', 'Prioritas', 'Potensial'), s.name, mp.name
     LIMIT 100"
)->fetchAll();

$bleepOverview = $pdo->query(
    "SELECT
        COUNT(*) AS total_tests,
        COUNT(DISTINCT master_person_id) AS tested,
        ROUND(AVG(vo2max), 2) AS average_vo2max,
        MAX(vo2max) AS highest_vo2max,
        MAX(test_date) AS latest_test
     FROM bleep_tests"
)->fetch();
$bleepTested = (int) ($bleepOverview['tested'] ?? 0);
$bleepOverview['not_tested'] = max(0, (int) ($overview['total'] ?? 0) - $bleepTested);

$bleepStatusRows = $pdo->query(
    "SELECT COALESCE(NULLIF(mp.development_status, ''), 'Belum Ditentukan') AS label,
        COUNT(mp.id) AS total,
        SUM(EXISTS (SELECT 1 FROM bleep_tests bt WHERE bt.master_person_id = mp.id)) AS tested
     FROM master_people mp
     WHERE mp.person_type = 'Atlet' AND mp.is_active = 1
     GROUP BY COALESCE(NULLIF(mp.development_status, ''), 'Belum Ditentukan')
     ORDER BY FIELD(label, 'Andalan', 'Prioritas', 'Potensial', 'Belum Ditentukan'), label"
)->fetchAll();

$bleepSportRows = $pdo->query(
    "SELECT s.name, COUNT(mp.id) AS athletes,
        SUM(EXISTS (SELECT 1 FROM bleep_tests bt WHERE bt.master_person_id = mp.id)) AS tested,
        ROUND((SELECT AVG(bt.vo2max) FROM bleep_tests bt WHERE bt.sport = s.name), 2) AS average_vo2max
     FROM sports s
     JOIN master_people mp ON mp.sport_id = s.id AND mp.person_type = 'Atlet' AND mp.is_active = 1
     GROUP BY s.id, s.name
     ORDER BY tested DESC, athletes DESC, s.name"
)->fetchAll();

view('summary', compact('overview', 'statusRows', 'sportRows', 'stationCoverageRows', 'notTested', 'bleepOverview', 'bleepStatusRows', 'bleepSportRows') + ['pageTitle' => 'Summary Atlet']);
