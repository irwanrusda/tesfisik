<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

if (request_method('POST')) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'sync');
    if ($action === 'add_athlete') {
        Auth::requireAnyRole(['superadmin', 'input']);
    } else {
        Auth::requireRole('superadmin');
    }
    try {
        if ($action === 'add_athlete') {
            function_exists('set_old') ? set_old($_POST) : $_SESSION['_old'] = $_POST;
            $athlete = MasterDataSync::addAthlete($_POST);
            try {
                write_audit_log(Database::connection(), 'create', 'master_data', ['id' => $athlete['id'], 'athlete_name' => $athlete['name'], 'sport' => $athlete['sport']], ['source' => 'website']);
            } catch (Throwable) {
                // Audit master_data membutuhkan migrasi enum terbaru. Jangan gagalkan tambah atlet
                // bila file sudah terdeploy tetapi migrasi belum sempat berjalan.
            }
            if (function_exists('clear_old')) {
                clear_old();
            } else {
                unset($_SESSION['_old']);
            }
            flash('success', "Atlet {$athlete['name']} berhasil ditambahkan ke database website. Silakan copy manual ke spreadsheet bila diperlukan.");
        } else {
            $summary = MasterDataSync::run();
            flash('success', "Sinkronisasi selesai: {$summary['athletes']} atlet, {$summary['coaches']} pelatih, dan {$summary['sports']} cabor. Data atlet dari website tetap dipertahankan.");
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
$people = [];
$sports = [];
$counts = ['total' => 0, 'athletes' => 0, 'coaches' => 0, 'last_sync' => null];
try {
    $statement = $pdo->prepare('SELECT master_people.*, sports.name AS sport_name FROM master_people JOIN sports ON sports.id = master_people.sport_id WHERE ' . implode(' AND ', $where) . ' ORDER BY sports.name, master_people.person_type, master_people.name');
    $statement->execute($params);
    $people = $statement->fetchAll();
    $sports = $pdo->query('SELECT name FROM sports WHERE is_active = 1 ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
    $counts = $pdo->query("SELECT COUNT(*) AS total, SUM(CASE WHEN person_type = 'Atlet' THEN 1 ELSE 0 END) AS athletes, SUM(CASE WHEN person_type = 'Pelatih' THEN 1 ELSE 0 END) AS coaches, MAX(synced_at) AS last_sync FROM master_people WHERE is_active = 1")->fetch() ?: $counts;
} catch (Throwable $exception) {
    error_log('[master-data] ' . $exception->getMessage());
    flash('error', 'Master data belum dapat dimuat. Jalankan migrasi database terbaru, lalu muat ulang halaman.');
}

view('master-data', compact('people', 'sports', 'counts', 'type', 'sport', 'q') + ['pageTitle' => 'Master Atlet dan Pelatih']);
