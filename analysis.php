<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia']);

$pdo = Database::connection();
$testItems = physical_test_items();
$rankingCode = (string) ($_GET['ranking_test'] ?? 'sit_up');
if (!isset($testItems[$rankingCode])) $rankingCode = 'sit_up';
$rankingDefinition = $testItems[$rankingCode];
$rankingDirection = in_array($rankingCode, ['sprint_30m', 'illinois'], true) ? 'ASC' : 'DESC';
$rankingStatement = $pdo->prepare(
    "SELECT tr.result_value, tr.unit, tr.category, at.athlete_name, at.sport, at.gender, at.test_date, u.name AS officer_name
     FROM test_results tr
     JOIN athlete_tests at ON at.id = tr.athlete_test_id
     JOIN users u ON u.id = at.created_by
     WHERE tr.test_code = ? AND tr.result_value IS NOT NULL
     ORDER BY tr.result_value {$rankingDirection}, at.test_date DESC, at.athlete_name
     LIMIT 100"
);
$rankingStatement->execute([$rankingCode]);
$rankingRows = $rankingStatement->fetchAll();
$testCount = (int) $pdo->query('SELECT COUNT(*) FROM athlete_tests')->fetchColumn();
$testedAthletes = (int) $pdo->query('SELECT COUNT(DISTINCT master_person_id) FROM athlete_tests WHERE master_person_id IS NOT NULL')->fetchColumn();
$retestedAthletes = (int) $pdo->query('SELECT COUNT(*) FROM (SELECT master_person_id FROM athlete_tests WHERE master_person_id IS NOT NULL GROUP BY master_person_id HAVING COUNT(*) > 1) repeated')->fetchColumn();
$expectedItems = count(physical_test_items());
$filledItems = (int) $pdo->query('SELECT COUNT(*) FROM test_results WHERE result_value IS NOT NULL')->fetchColumn();
$completeness = $testCount > 0 ? round($filledItems / ($testCount * $expectedItems) * 100, 1) : 0;

$categoryRows = $pdo->query(
    "SELECT COALESCE(NULLIF(category, ''), 'Belum Dinilai') AS label, COUNT(*) AS total
     FROM test_results WHERE result_value IS NOT NULL
     GROUP BY COALESCE(NULLIF(category, ''), 'Belum Dinilai')
     ORDER BY FIELD(label, 'Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang', 'Belum Dinilai')"
)->fetchAll();
$categoryTotal = array_sum(array_map(static fn($row) => (int) $row['total'], $categoryRows));

$bmiRows = $pdo->query(
    "SELECT CASE WHEN bmi < 18.5 THEN 'Kurus' WHEN bmi < 25 THEN 'Normal' WHEN bmi < 30 THEN 'Berlebih' ELSE 'Obesitas' END AS label, COUNT(*) AS total
     FROM athlete_tests WHERE bmi IS NOT NULL GROUP BY label ORDER BY FIELD(label, 'Kurus', 'Normal', 'Berlebih', 'Obesitas')"
)->fetchAll();
$bmiTotal = array_sum(array_map(static fn($row) => (int) $row['total'], $bmiRows));

$averageRows = $pdo->query('SELECT test_code, ROUND(AVG(result_value), 2) AS average, MIN(result_value) AS minimum, MAX(result_value) AS maximum, COUNT(result_value) AS samples FROM test_results WHERE result_value IS NOT NULL GROUP BY test_code')->fetchAll();
$averages = [];
foreach ($averageRows as $row) $averages[$row['test_code']] = $row;
$vo2maxSummary = $pdo->query('SELECT ROUND(AVG(vo2max), 2) AS average, MIN(vo2max) AS minimum, MAX(vo2max) AS maximum, COUNT(vo2max) AS samples FROM bleep_tests')->fetch();
$bleepOverview = $pdo->query('SELECT COUNT(*) AS total_tests, COUNT(DISTINCT master_person_id) AS athletes, ROUND(AVG(level), 1) AS average_level, ROUND(AVG(distance_m), 0) AS average_distance FROM bleep_tests')->fetch();
$bleepCategoryRows = $pdo->query("SELECT COALESCE(NULLIF(category, ''), 'Belum Dinilai') AS label, COUNT(*) AS total FROM bleep_tests GROUP BY COALESCE(NULLIF(category, ''), 'Belum Dinilai') ORDER BY FIELD(label, 'Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang', 'Belum Dinilai')")->fetchAll();
$bleepCategoryTotal = array_sum(array_map(static fn($row) => (int) $row['total'], $bleepCategoryRows));
$bleepSportAnalysis = $pdo->query('SELECT sport, COUNT(*) AS tests, COUNT(DISTINCT master_person_id) AS athletes, ROUND(AVG(vo2max), 2) AS average_vo2max, MAX(vo2max) AS highest_vo2max, ROUND(AVG(distance_m), 0) AS average_distance FROM bleep_tests GROUP BY sport ORDER BY average_vo2max DESC, tests DESC, sport LIMIT 12')->fetchAll();
$bleepTopAthletes = $pdo->query('SELECT athlete_name, sport, level, shuttle, distance_m, vo2max, test_date FROM bleep_tests ORDER BY vo2max DESC, test_date DESC LIMIT 10')->fetchAll();

$coverageRows = $pdo->query(
    "SELECT s.name, COUNT(mp.id) AS athletes, SUM(EXISTS (SELECT 1 FROM athlete_tests at WHERE at.master_person_id = mp.id)) AS tested
     FROM sports s JOIN master_people mp ON mp.sport_id = s.id AND mp.person_type = 'Atlet' AND mp.is_active = 1
     GROUP BY s.id, s.name ORDER BY tested DESC, athletes DESC, s.name"
)->fetchAll();

$weakRows = $pdo->query(
    "SELECT tr.test_code, SUM(tr.category IN ('Kurang', 'Sangat Kurang')) AS weak, COUNT(tr.result_value) AS total
     FROM test_results tr WHERE tr.result_value IS NOT NULL GROUP BY tr.test_code ORDER BY weak DESC, total DESC"
)->fetchAll();

$recommendations = [];
if ($testCount === 0) {
    $recommendations[] = ['title' => 'Mulai pengumpulan data tes', 'text' => 'Belum ada hasil tes yang dapat dianalisis. Jadwalkan tes per cabor dan prioritaskan atlet Andalan.'];
} else {
    if ($completeness < 90) $recommendations[] = ['title' => 'Lengkapi item pengukuran', 'text' => "Kelengkapan hasil baru {$completeness}%. Pastikan sembilan item tes diisi agar perbandingan atlet akurat."];
    $normalBmi = 0;
    foreach ($bmiRows as $row) if ($row['label'] === 'Normal') $normalBmi = (int) $row['total'];
    if ($bmiTotal > 0 && $normalBmi / $bmiTotal < .7) $recommendations[] = ['title' => 'Tinjau komposisi tubuh', 'text' => 'Kurang dari 70% hasil tes berada pada kategori IMT normal. Lakukan penilaian lanjutan sesuai karakteristik cabang olahraga.'];
    $topWeak = $weakRows[0] ?? null;
    if ($topWeak && (int) $topWeak['weak'] > 0) {
        $definition = physical_test_items()[$topWeak['test_code']] ?? ['method' => $topWeak['test_code']];
        $recommendations[] = ['title' => 'Prioritaskan ' . $definition['method'], 'text' => $topWeak['weak'] . ' hasil berada pada kategori Kurang atau Sangat Kurang, tertinggi di antara item yang telah dinilai.'];
    }
    if ($retestedAthletes === 0) $recommendations[] = ['title' => 'Siapkan tes berkala', 'text' => 'Belum ada atlet dengan tes berulang. Tes berkala diperlukan untuk membaca tren peningkatan atau penurunan kondisi fisik.'];
}
if (!$recommendations) $recommendations[] = ['title' => 'Kualitas data baik', 'text' => 'Data cukup lengkap. Lanjutkan pemantauan berkala dan bandingkan tren per atlet serta cabang olahraga.'];

view('analysis', compact('testItems', 'rankingCode', 'rankingDefinition', 'rankingDirection', 'rankingRows', 'testCount', 'testedAthletes', 'retestedAthletes', 'completeness', 'categoryRows', 'categoryTotal', 'bmiRows', 'bmiTotal', 'averages', 'vo2maxSummary', 'bleepOverview', 'bleepCategoryRows', 'bleepCategoryTotal', 'bleepSportAnalysis', 'bleepTopAthletes', 'coverageRows', 'recommendations') + ['pageTitle' => 'Analisis Tes']);
