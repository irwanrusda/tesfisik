<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'input']);

$pdo = Database::connection();
$protocol = bleep_test_protocol();
$editing = null;

if (isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM bleep_tests WHERE id = ?');
    $statement->execute([(int) $_GET['edit']]);
    $editing = $statement->fetch() ?: null;
}

if (request_method('POST')) {
    verify_csrf();
    set_old($_POST);
    $id = (int) ($_POST['id'] ?? 0);
    $masterPersonId = (int) ($_POST['master_person_id'] ?? 0);
    $testDate = (string) ($_POST['test_date'] ?? '');
    $testPlace = trim((string) ($_POST['test_place'] ?? 'Padang')) ?: 'Padang';
    $level = (int) ($_POST['level'] ?? 0);
    $shuttle = (int) ($_POST['shuttle'] ?? 0);
    $category = trim((string) ($_POST['category'] ?? '')) ?: null;
    $notes = trim((string) ($_POST['notes'] ?? '')) ?: null;

    try {
        if ($masterPersonId < 1 || $testDate === '') {
            throw new RuntimeException('Atlet dan tanggal tes wajib diisi.');
        }
        $athleteStatement = $pdo->prepare("SELECT mp.id, mp.name, mp.gender, s.name AS sport,
            (SELECT at.birth_date FROM athlete_tests at WHERE at.master_person_id = mp.id ORDER BY at.test_date DESC, at.id DESC LIMIT 1) AS latest_birth_date
            FROM master_people mp JOIN sports s ON s.id = mp.sport_id
            WHERE mp.id = ? AND mp.person_type = 'Atlet' AND mp.is_active = 1");
        $athleteStatement->execute([$masterPersonId]);
        $athlete = $athleteStatement->fetch();
        if (!$athlete) throw new RuntimeException('Atlet tidak ditemukan pada master data aktif.');
        if (!$athlete['latest_birth_date']) throw new RuntimeException('Atlet belum memiliki data tanggal lahir pada Tes Fisik.');
        $birthDate = $athlete['latest_birth_date'];

        $metrics = bleep_test_metrics($level, $shuttle, calculate_age($birthDate, $testDate));
        if ($id > 0) {
            $statement = $pdo->prepare('UPDATE bleep_tests SET master_person_id = ?, athlete_name = ?, sport = ?, gender = ?, birth_date = ?, test_date = ?, test_place = ?, level = ?, shuttle = ?, completed_shuttles = ?, distance_m = ?, speed_kmh = ?, vo2max = ?, category = ?, notes = ? WHERE id = ?');
            $statement->execute([$athlete['id'], $athlete['name'], $athlete['sport'], $athlete['gender'], $birthDate, $testDate, $testPlace, $level, $shuttle, $metrics['completed_shuttles'], $metrics['distance'], $metrics['speed'], $metrics['vo2max'], $category, $notes, $id]);
            flash('success', 'Hasil Bleep Test berhasil diperbarui.');
        } else {
            $testNumber = 'BT-' . date('Ymd', strtotime($testDate)) . '-' . strtoupper(bin2hex(random_bytes(2)));
            $statement = $pdo->prepare('INSERT INTO bleep_tests (test_number, master_person_id, athlete_name, sport, gender, birth_date, test_date, test_place, level, shuttle, completed_shuttles, distance_m, speed_kmh, vo2max, category, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->execute([$testNumber, $athlete['id'], $athlete['name'], $athlete['sport'], $athlete['gender'], $birthDate, $testDate, $testPlace, $level, $shuttle, $metrics['completed_shuttles'], $metrics['distance'], $metrics['speed'], $metrics['vo2max'], $category, $notes, Auth::user()['id']]);
            flash('success', 'Hasil Bleep Test berhasil disimpan.');
        }
        clear_old();
        redirect('bleep-test.php');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
        redirect('bleep-test.php' . ($id > 0 ? '?edit=' . $id : ''));
    }
}

$athletes = $pdo->query("SELECT mp.id, mp.name, mp.gender, mp.development_status, s.name AS sport,
    (SELECT at.birth_date FROM athlete_tests at WHERE at.master_person_id = mp.id ORDER BY at.test_date DESC, at.id DESC LIMIT 1) AS latest_birth_date
    FROM master_people mp
    JOIN sports s ON s.id = mp.sport_id
    WHERE mp.person_type = 'Atlet' AND mp.is_active = 1
    ORDER BY s.name, mp.name")->fetchAll();
$history = $pdo->query('SELECT bt.*, u.name AS officer_name FROM bleep_tests bt JOIN users u ON u.id = bt.created_by ORDER BY bt.test_date DESC, bt.id DESC LIMIT 100')->fetchAll();

view('bleep-test', compact('protocol', 'athletes', 'history', 'editing') + ['pageTitle' => 'Bleep Test VO2max']);
