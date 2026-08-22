<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

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
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'delete') {
        Auth::requireRole('superadmin');
        $deleteId = (int) ($_POST['id'] ?? 0);
        try {
            $recordStatement = $pdo->prepare('SELECT test_number, athlete_name, sport, test_date, level, shuttle, vo2max FROM bleep_tests WHERE id = ?');
            $recordStatement->execute([$deleteId]);
            $record = $recordStatement->fetch();
            if (!$record) throw new RuntimeException('Data Bleep Test tidak ditemukan.');
            write_audit_log($pdo, 'delete', 'bleep_test', ['id' => $deleteId, 'number' => $record['test_number'], 'athlete_name' => $record['athlete_name'], 'sport' => $record['sport']], ['tanggal_tes' => $record['test_date'], 'level' => $record['level'], 'shuttle' => $record['shuttle'], 'vo2max' => $record['vo2max']]);
            $deleteStatement = $pdo->prepare('DELETE FROM bleep_tests WHERE id = ?');
            $deleteStatement->execute([$deleteId]);
            if ($deleteStatement->rowCount() < 1) {
                throw new RuntimeException('Data Bleep Test tidak ditemukan.');
            }
            flash('success', 'Data Bleep Test berhasil dihapus.');
        } catch (Throwable $exception) {
            flash('error', 'Data Bleep Test gagal dihapus: ' . $exception->getMessage());
        }
        redirect('bleep-test.php');
    }

    set_old($_POST);
    $id = (int) ($_POST['id'] ?? 0);
    $masterPersonId = (int) ($_POST['master_person_id'] ?? 0);
    $testDate = (string) ($_POST['test_date'] ?? '');
    $testPlace = trim((string) ($_POST['test_place'] ?? 'Padang')) ?: 'Padang';
    $level = (int) ($_POST['level'] ?? 0);
    $shuttle = (int) ($_POST['shuttle'] ?? 0);
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
        $birthDate = $athlete['latest_birth_date'] ?: null;

        $age = $birthDate ? calculate_age($birthDate, $testDate) : null;
        $metrics = bleep_test_metrics($level, $shuttle, $age);
        $category = vo2max_category((float) $metrics['vo2max'], $athlete['gender'], $age);
        if ($id > 0) {
            $statement = $pdo->prepare('UPDATE bleep_tests SET master_person_id = ?, athlete_name = ?, sport = ?, gender = ?, birth_date = ?, test_date = ?, test_place = ?, level = ?, shuttle = ?, completed_shuttles = ?, distance_m = ?, speed_kmh = ?, vo2max = ?, category = ?, notes = ? WHERE id = ?');
            $statement->execute([$athlete['id'], $athlete['name'], $athlete['sport'], $athlete['gender'], $birthDate, $testDate, $testPlace, $level, $shuttle, $metrics['completed_shuttles'], $metrics['distance'], $metrics['speed'], $metrics['vo2max'], $category, $notes, $id]);
            $numberStatement = $pdo->prepare('SELECT test_number FROM bleep_tests WHERE id = ?');
            $numberStatement->execute([$id]);
            write_audit_log($pdo, 'update', 'bleep_test', ['id' => $id, 'number' => $numberStatement->fetchColumn(), 'athlete_name' => $athlete['name'], 'sport' => $athlete['sport']], ['tanggal_tes' => $testDate, 'level' => $level, 'shuttle' => $shuttle, 'vo2max' => $metrics['vo2max']]);
            flash('success', 'Hasil Bleep Test berhasil diperbarui.');
        } else {
            $testNumber = 'BT-' . date('Ymd', strtotime($testDate)) . '-' . strtoupper(bin2hex(random_bytes(4)));
            $statement = $pdo->prepare('INSERT INTO bleep_tests (test_number, master_person_id, athlete_name, sport, gender, birth_date, test_date, test_place, level, shuttle, completed_shuttles, distance_m, speed_kmh, vo2max, category, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->execute([$testNumber, $athlete['id'], $athlete['name'], $athlete['sport'], $athlete['gender'], $birthDate, $testDate, $testPlace, $level, $shuttle, $metrics['completed_shuttles'], $metrics['distance'], $metrics['speed'], $metrics['vo2max'], $category, $notes, Auth::user()['id']]);
            $bleepId = (int) $pdo->lastInsertId();
            write_audit_log($pdo, 'create', 'bleep_test', ['id' => $bleepId, 'number' => $testNumber, 'athlete_name' => $athlete['name'], 'sport' => $athlete['sport']], ['tanggal_tes' => $testDate, 'level' => $level, 'shuttle' => $shuttle, 'vo2max' => $metrics['vo2max']]);
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
