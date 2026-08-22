<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireRole('superadmin');

$pdo = Database::connection();
if (request_method('POST')) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'conditions');
    if ($action === 'crud_lock') {
        $locked = isset($_POST['crud_locked']) ? '1' : '0';
        $statement = $pdo->prepare('INSERT INTO test_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)');
        $statement->execute(['crud_locked', $locked, Auth::user()['id']]);
        flash('success', $locked === '1' ? 'CRUD berhasil ditutup untuk seluruh user selain superadmin.' : 'CRUD berhasil dibuka kembali.');
        redirect('test-settings.php');
    }

    $sitUpDuration = (int) ($_POST['sit_up_duration_seconds'] ?? 0);
    $pushUpDuration = (int) ($_POST['push_up_duration_seconds'] ?? 0);
    $femalePullUpMode = (string) ($_POST['female_pull_up_mode'] ?? '');

    if ($sitUpDuration < 1 || $sitUpDuration > 600 || $pushUpDuration < 1 || $pushUpDuration > 600) {
        flash('error', 'Durasi Sit Up dan Push Up harus antara 1 sampai 600 detik.');
        redirect('test-settings.php');
    }
    if (!in_array($femalePullUpMode, ['repetitions', 'hold'], true)) {
        flash('error', 'Kondisi Pull Up perempuan tidak valid.');
        redirect('test-settings.php');
    }

    $statement = $pdo->prepare('INSERT INTO test_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)');
    foreach ([
        'sit_up_duration_seconds' => (string) $sitUpDuration,
        'push_up_duration_seconds' => (string) $pushUpDuration,
        'female_pull_up_mode' => $femalePullUpMode,
    ] as $key => $value) {
        $statement->execute([$key, $value, Auth::user()['id']]);
    }
    flash('success', 'Konfigurasi kondisi tes berhasil disimpan.');
    redirect('test-settings.php');
}

$conditions = test_conditions($pdo);
$crudLocked = crud_is_locked($pdo);
view('test-settings', compact('conditions', 'crudLocked') + ['pageTitle' => 'Konfigurasi Tes']);
