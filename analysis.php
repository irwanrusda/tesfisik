<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia']);

$pdo = Database::connection();
$testItems = physical_test_items();

if (request_method('POST')) {
    Auth::requireRole('superadmin');
    verify_csrf();
    $duplicateAction = (string) ($_POST['duplicate_action'] ?? '');
    $recordIds = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) ($_POST['record_ids'] ?? ''))))));
    sort($recordIds);
    if (count($recordIds) < 2 || !in_array($duplicateAction, ['merge', 'separate'], true)) {
        flash('error', 'Kandidat data ganda tidak valid.');
        redirect('analysis.php#data-ganda');
    }

    $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
    $recordStatement = $pdo->prepare("SELECT at.*, (SELECT COUNT(*) FROM test_results tr WHERE tr.athlete_test_id = at.id AND tr.result_value IS NOT NULL) AS filled_results FROM athlete_tests at WHERE at.id IN ({$placeholders}) ORDER BY filled_results DESC, at.id ASC");
    $recordStatement->execute($recordIds);
    $duplicateRecords = $recordStatement->fetchAll();
    if (count($duplicateRecords) !== count($recordIds)) {
        flash('error', 'Sebagian data kandidat sudah berubah atau tidak ditemukan.');
        redirect('analysis.php#data-ganda');
    }
    $identityKeys = array_unique(array_map(static fn(array $row): string => hash('sha256', strtolower(trim($row['athlete_name'])) . '|' . strtolower(trim($row['sport'])) . '|' . $row['gender']), $duplicateRecords));
    if (count($identityKeys) !== 1) {
        flash('error', 'Data yang dipilih tidak memiliki kombinasi nama, cabor, dan jenis kelamin yang sama.');
        redirect('analysis.php#data-ganda');
    }
    $athleteKey = $identityKeys[0];
    $fingerprint = hash('sha256', implode(',', $recordIds));

    if ($duplicateAction === 'separate') {
        $resolution = $pdo->prepare("INSERT INTO duplicate_resolutions (fingerprint, athlete_key, decision, record_ids, resolved_by) VALUES (?, ?, 'separate', ?, ?) ON DUPLICATE KEY UPDATE decision = VALUES(decision), resolved_by = VALUES(resolved_by), resolved_at = CURRENT_TIMESTAMP");
        $resolution->execute([$fingerprint, $athleteKey, implode(',', $recordIds), Auth::user()['id']]);
        write_audit_log($pdo, 'update', 'tes_fisik', ['id' => $recordIds[0], 'athlete_name' => $duplicateRecords[0]['athlete_name'], 'sport' => $duplicateRecords[0]['sport']], ['keputusan_duplikasi' => 'tetap_dipisah', 'record_ids' => $recordIds]);
        flash('success', 'Data ditandai sebagai atlet yang sengaja dipisah dan tidak akan muncul lagi selama susunan datanya tidak berubah.');
        redirect('analysis.php#data-ganda');
    }

    $primary = $duplicateRecords[0];
    $secondaryIds = array_values(array_diff($recordIds, [(int) $primary['id']]));
    $mergeFillFields = ['birth_place', 'birth_date', 'height_cm', 'weight_kg', 'bmi', 'test_place', 'notes'];
    $identityMergeFields = ['birth_place', 'birth_date', 'height_cm', 'weight_kg'];
    $mergedPrimaryData = [];
    $biodataConflicts = [];
    foreach ($mergeFillFields as $field) {
        $filledValues = [];
        foreach ($duplicateRecords as $candidate) {
            $candidateValue = $candidate[$field] ?? null;
            if ($candidateValue === null || trim((string) $candidateValue) === '') continue;
            $normalizedValue = is_numeric($candidateValue) ? (string) (float) $candidateValue : strtolower(trim((string) $candidateValue));
            $filledValues[$normalizedValue] = $candidateValue;
        }
        if (in_array($field, $identityMergeFields, true) && count($filledValues) > 1) {
            $biodataConflicts[$field] = array_values($filledValues);
        }
        $currentValue = $primary[$field] ?? null;
        $mergedPrimaryData[$field] = ($currentValue !== null && trim((string) $currentValue) !== '') ? $currentValue : null;
        if ($mergedPrimaryData[$field] !== null) continue;
        $mergedPrimaryData[$field] = $filledValues ? reset($filledValues) : null;
    }
    if ($biodataConflicts) {
        flash('error', 'Data ganda belum digabungkan karena ada biodata yang berbeda: ' . implode(', ', array_keys($biodataConflicts)) . '. Periksa manual dulu agar data tidak salah gabung.');
        redirect('analysis.php#data-ganda');
    }
    if (($mergedPrimaryData['bmi'] ?? null) === null && ($mergedPrimaryData['height_cm'] ?? null) !== null && ($mergedPrimaryData['weight_kg'] ?? null) !== null && (float) $mergedPrimaryData['height_cm'] > 0) {
        $heightM = (float) $mergedPrimaryData['height_cm'] / 100;
        $mergedPrimaryData['bmi'] = round((float) $mergedPrimaryData['weight_kg'] / ($heightM * $heightM), 2);
    }
    $conflicts = [];
    $pdo->beginTransaction();
    try {
        foreach ($secondaryIds as $secondaryId) {
            $resultsStatement = $pdo->prepare('SELECT * FROM test_results WHERE athlete_test_id = ? ORDER BY updated_at DESC, id DESC');
            $resultsStatement->execute([$secondaryId]);
            foreach ($resultsStatement->fetchAll() as $result) {
                $existingStatement = $pdo->prepare('SELECT * FROM test_results WHERE athlete_test_id = ? AND test_code = ? LIMIT 1');
                $existingStatement->execute([$primary['id'], $result['test_code']]);
                $existing = $existingStatement->fetch();
                if (!$existing) {
                    $move = $pdo->prepare('UPDATE test_results SET athlete_test_id = ? WHERE id = ?');
                    $move->execute([$primary['id'], $result['id']]);
                    continue;
                }
                $useSecondary = $existing['result_value'] === null || ($result['result_value'] !== null && strtotime($result['updated_at']) > strtotime($existing['updated_at']));
                if ($existing['result_value'] !== null && $result['result_value'] !== null && (float) $existing['result_value'] !== (float) $result['result_value']) {
                    $conflicts[] = ['test_code' => $result['test_code'], 'dipertahankan' => $useSecondary ? $result['result_value'] : $existing['result_value'], 'diabaikan' => $useSecondary ? $existing['result_value'] : $result['result_value']];
                }
                if ($useSecondary) {
                    $replace = $pdo->prepare('UPDATE test_results SET result_value = ?, unit = ?, category = ?, examiner_notes = ?, updated_at = ? WHERE id = ?');
                    $replace->execute([$result['result_value'], $result['unit'], $result['category'], $result['examiner_notes'], $result['updated_at'], $existing['id']]);
                }
                $deleteResult = $pdo->prepare('DELETE FROM test_results WHERE id = ?');
                $deleteResult->execute([$result['id']]);
            }

            $movePhotos = $pdo->prepare('UPDATE test_photos SET athlete_test_id = ? WHERE athlete_test_id = ?');
            $movePhotos->execute([$primary['id'], $secondaryId]);
        }

        $masterPersonIds = array_values(array_unique(array_filter(array_map(static fn(array $row): int => (int) ($row['master_person_id'] ?? 0), $duplicateRecords))));
        if ($masterPersonIds) {
            $primaryMasterId = (int) ($primary['master_person_id'] ?: $masterPersonIds[0]);
            $masterPlaceholders = implode(',', array_fill(0, count($masterPersonIds), '?'));
            $updateBleep = $pdo->prepare("UPDATE bleep_tests SET master_person_id = ? WHERE master_person_id IN ({$masterPlaceholders})");
            $updateBleep->execute(array_merge([$primaryMasterId], $masterPersonIds));
            $updatePrimary = $pdo->prepare('UPDATE athlete_tests SET master_person_id = ? WHERE id = ?');
            $updatePrimary->execute([$primaryMasterId, $primary['id']]);
        }

        $updatePrimaryData = $pdo->prepare('UPDATE athlete_tests SET birth_place = ?, birth_date = ?, height_cm = ?, weight_kg = ?, bmi = ?, test_place = ?, notes = ? WHERE id = ?');
        $updatePrimaryData->execute([
            $mergedPrimaryData['birth_place'],
            $mergedPrimaryData['birth_date'],
            $mergedPrimaryData['height_cm'],
            $mergedPrimaryData['weight_kg'],
            $mergedPrimaryData['bmi'],
            $mergedPrimaryData['test_place'] ?: $primary['test_place'],
            $mergedPrimaryData['notes'],
            $primary['id'],
        ]);

        foreach ($secondaryIds as $secondaryId) {
            $clearMasterReference = $pdo->prepare('UPDATE athlete_tests SET master_person_id = NULL WHERE id = ?');
            $clearMasterReference->execute([$secondaryId]);
            $deleteRecord = $pdo->prepare('DELETE FROM athlete_tests WHERE id = ?');
            $deleteRecord->execute([$secondaryId]);
        }
        $resolution = $pdo->prepare("INSERT INTO duplicate_resolutions (fingerprint, athlete_key, decision, record_ids, resolved_by) VALUES (?, ?, 'merged', ?, ?) ON DUPLICATE KEY UPDATE decision = VALUES(decision), resolved_by = VALUES(resolved_by), resolved_at = CURRENT_TIMESTAMP");
        $resolution->execute([$fingerprint, $athleteKey, implode(',', $recordIds), Auth::user()['id']]);
        write_audit_log($pdo, 'update', 'tes_fisik', ['id' => $primary['id'], 'number' => $primary['test_number'], 'athlete_name' => $primary['athlete_name'], 'sport' => $primary['sport']], ['penggabungan_data' => $recordIds, 'konflik_nilai' => $conflicts]);
        $pdo->commit();
        flash('success', 'Data ganda berhasil digabungkan. Biodata, nilai pos, Bleep Test, dan dokumentasi telah dipindahkan ke satu data utama.');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', 'Data ganda gagal digabungkan: ' . $exception->getMessage());
    }
    redirect('analysis.php#data-ganda');
}

$duplicateGroups = [];
$duplicateGroupRows = $pdo->query("SELECT LOWER(TRIM(athlete_name)) AS normalized_name, LOWER(TRIM(sport)) AS normalized_sport, gender, COUNT(*) AS total, GROUP_CONCAT(id ORDER BY id) AS record_ids FROM athlete_tests GROUP BY LOWER(TRIM(athlete_name)), LOWER(TRIM(sport)), gender HAVING COUNT(*) > 1 ORDER BY total DESC, normalized_name")->fetchAll();
foreach ($duplicateGroupRows as $group) {
    $ids = array_map('intval', explode(',', $group['record_ids']));
    sort($ids);
    $fingerprint = hash('sha256', implode(',', $ids));
    $resolutionStatement = $pdo->prepare("SELECT decision FROM duplicate_resolutions WHERE fingerprint = ? AND decision = 'separate' LIMIT 1");
    $resolutionStatement->execute([$fingerprint]);
    if ($resolutionStatement->fetchColumn()) continue;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $detailStatement = $pdo->prepare("SELECT at.id, at.test_number, at.athlete_name, at.sport, at.gender, at.birth_date, at.test_date, at.created_at, u.name AS creator_name FROM athlete_tests at JOIN users u ON u.id = at.created_by WHERE at.id IN ({$placeholders}) ORDER BY at.id");
    $detailStatement->execute($ids);
    $records = $detailStatement->fetchAll();
    $resultStatement = $pdo->prepare("SELECT tr.athlete_test_id, tr.test_code, tr.result_value, tr.unit, tr.category, tr.updated_at FROM test_results tr WHERE tr.athlete_test_id IN ({$placeholders}) AND tr.result_value IS NOT NULL ORDER BY tr.test_code, tr.updated_at DESC");
    $resultStatement->execute($ids);
    $results = [];
    foreach ($resultStatement->fetchAll() as $result) $results[$result['athlete_test_id']][] = $result;
    $duplicateGroups[] = ['record_ids' => $ids, 'records' => $records, 'results' => $results];
}
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

view('analysis', compact('testItems', 'duplicateGroups', 'rankingCode', 'rankingDefinition', 'rankingDirection', 'rankingRows', 'testCount', 'testedAthletes', 'retestedAthletes', 'completeness', 'categoryRows', 'categoryTotal', 'bmiRows', 'bmiTotal', 'averages', 'vo2maxSummary', 'bleepOverview', 'bleepCategoryRows', 'bleepCategoryTotal', 'bleepSportAnalysis', 'bleepTopAthletes', 'coverageRows', 'recommendations') + ['pageTitle' => 'Analisis Tes']);
