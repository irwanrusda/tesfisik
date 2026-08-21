<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

if (request_method('POST')) {
    Auth::requireRole('superadmin');
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'sync');
    try {
        if ($action === 'add_athlete') {
            set_old($_POST);
            $athlete = MasterDataSync::addAthlete($_POST);
            clear_old();
            flash('success', "Atlet {$athlete['name']} berhasil ditambahkan ke Google Sheet dan database.");
        } else {
            $summary = MasterDataSync::run();
            flash('success', "Sinkronisasi selesai: {$summary['athletes']} atlet, {$summary['coaches']} pelatih, dan {$summary['sports']} cabor.");
        }
    } catch (Throwable $exception) {
        flash('error', ($action === 'add_athlete' ? 'Penambahan atlet gagal: ' : 'Sinkronisasi gagal: ') . $exception->getMessage());
    }
    redirect('master-data.php');
}

$type = in_array($_GET['type'] ?? '', ['Atlet', 'Pelatih'], true) ? $_GET['type'] : '';
$sport = trim((string) ($_GET['sport'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));
$where = ['master_people.is_active = 1'];
$params = [];
if ($type !== '') { $where[] = 'master_people.person_type = ?'; $params[] = $type; }
if ($sport !== '') { $where[] = 'sports.name = ?'; $params[] = $sport; }
if ($q !== '') { $where[] = 'master_people.name LIKE ?'; $params[] = "%{$q}%"; }

$pdo = Database::connection();
$statement = $pdo->prepare('SELECT master_people.*, sports.name AS sport_name FROM master_people JOIN sports ON sports.id = master_people.sport_id WHERE ' . implode(' AND ', $where) . ' ORDER BY sports.name, master_people.person_type, master_people.name');
$statement->execute($params);
$people = $statement->fetchAll();
$sports = $pdo->query('SELECT name FROM sports WHERE is_active = 1 ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
$counts = $pdo->query("SELECT COUNT(*) AS total, SUM(person_type = 'Atlet') AS athletes, SUM(person_type = 'Pelatih') AS coaches, MAX(synced_at) AS last_sync FROM master_people WHERE is_active = 1")->fetch();

view('master-data', compact('people', 'sports', 'counts', 'type', 'sport', 'q') + ['pageTitle' => 'Master Atlet dan Pelatih']);
