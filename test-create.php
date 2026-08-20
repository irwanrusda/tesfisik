<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

if (request_method('POST')) {
    verify_csrf();
    set_old($_POST);

    $athleteName = trim((string) ($_POST['athlete_name'] ?? ''));
    $masterPersonId = (int) ($_POST['master_person_id'] ?? 0);
    $birthPlace = trim((string) ($_POST['birth_place'] ?? ''));
    $birthDate = (string) ($_POST['birth_date'] ?? '');
    $sport = trim((string) ($_POST['sport'] ?? ''));
    $gender = (string) ($_POST['gender'] ?? '');
    $height = nullable_number($_POST['height_cm'] ?? null);
    $weight = nullable_number($_POST['weight_kg'] ?? null);
    $testDate = (string) ($_POST['test_date'] ?? '');
    $testPlace = trim((string) ($_POST['test_place'] ?? 'Padang'));

    if ($masterPersonId < 1 || $birthPlace === '' || $birthDate === '' || !$height || !$weight || $testDate === '') {
        flash('error', 'Lengkapi seluruh data wajib atlet.');
        redirect('test-create.php');
    }

    $bmi = round($weight / (($height / 100) ** 2), 2);
    $pdo = Database::connection();
    $pdo->beginTransaction();
    try {
        $testNumber = 'TF-' . date('Ymd', strtotime($testDate)) . '-' . strtoupper(bin2hex(random_bytes(2)));
        $masterStatement = $pdo->prepare("SELECT master_people.id, master_people.name, master_people.gender, sports.name AS sport FROM master_people JOIN sports ON sports.id = master_people.sport_id WHERE master_people.id = ? AND master_people.person_type = 'Atlet' AND master_people.is_active = 1");
        $masterStatement->execute([$masterPersonId]);
        $masterPerson = $masterStatement->fetch();
        if (!$masterPerson) {
            throw new RuntimeException('Atlet harus dipilih dari master data aktif.');
        }
        $athleteName = $masterPerson['name'];
        $sport = $masterPerson['sport'];
        $gender = $masterPerson['gender'];

        $statement = $pdo->prepare(
            'INSERT INTO athlete_tests (test_number, master_person_id, athlete_name, birth_place, birth_date, sport, gender, height_cm, weight_kg, bmi, test_date, test_place, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$testNumber, $masterPersonId, $athleteName, $birthPlace, $birthDate, $sport, $gender, $height, $weight, $bmi, $testDate, $testPlace ?: 'Padang', trim((string) ($_POST['notes'] ?? '')) ?: null, Auth::user()['id']]);
        $testId = (int) $pdo->lastInsertId();

        $resultStatement = $pdo->prepare('INSERT INTO test_results (athlete_test_id, test_code, result_value, unit, category, examiner_notes) VALUES (?, ?, ?, ?, ?, ?)');
        foreach (physical_test_items() as $code => $item) {
            $resultStatement->execute([
                $testId,
                $code,
                nullable_number($_POST['results'][$code]['value'] ?? null),
                $item['unit'],
                trim((string) ($_POST['results'][$code]['category'] ?? '')) ?: null,
                trim((string) ($_POST['results'][$code]['notes'] ?? '')) ?: null,
            ]);
        }
        $pdo->commit();
        clear_old();
        flash('success', "Data tes {$athleteName} berhasil disimpan.");
        redirect('test-view.php?id=' . $testId);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Data gagal disimpan. Silakan periksa isian dan coba lagi.');
        redirect('test-create.php');
    }
}

$athletes = Database::connection()->query("SELECT master_people.id, master_people.name, master_people.gender, master_people.achievement, master_people.development_status, sports.name AS sport FROM master_people JOIN sports ON sports.id = master_people.sport_id WHERE master_people.person_type = 'Atlet' AND master_people.is_active = 1 ORDER BY sports.name, master_people.name")->fetchAll();
view('test-form', ['pageTitle' => 'Input Data Tes', 'test' => null, 'results' => [], 'formAction' => 'test-create.php', 'athletes' => $athletes]);
