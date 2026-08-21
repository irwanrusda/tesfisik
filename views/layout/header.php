<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$pageTitle = $pageTitle ?? config('app.name');
$user = Auth::user();
$role = $user['role'] ?? '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title><?= e($pageTitle) ?> | <?= e(config('app.name')) ?></title>
    <link rel="icon" type="image/svg+xml" href="https://konisumbar.org/assets/img/logo_no_text.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Source+Serif+4:opsz,wght@8..60,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img class="brand-logo" src="https://konisumbar.org/assets/img/logo_no_text.svg" alt="Logo KONI Sumatera Barat">
            <div><strong>KONI SUMBAR</strong><small>Tes Fisik 2026</small></div>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-label">UTAMA</p>
            <?php if ($role === 'superadmin'): ?>
                <a class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= e(base_url('dashboard.php')) ?>">
                    <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span> Dashboard
                </a>
            <?php endif; ?>
            <?php if (in_array($role, ['superadmin', 'panitia'], true)): ?>
                <a class="nav-item <?= $currentPage === 'summary.php' ? 'active' : '' ?>" href="<?= e(base_url('summary.php')) ?>">
                    <span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span> Summary
                </a>
                <a class="nav-item <?= $currentPage === 'analysis.php' ? 'active' : '' ?>" href="<?= e(base_url('analysis.php')) ?>">
                    <span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span> Analisis
                </a>
            <?php endif; ?>
            <?php if (in_array($role, ['superadmin', 'input'], true)): ?>
                <?php $inputMenuOpen = in_array($currentPage, ['test-create.php', 'test-edit.php', 'test-station.php', 'bleep-test.php'], true); ?>
                <div class="nav-accordion <?= $inputMenuOpen ? 'open' : '' ?>" data-nav-accordion>
                    <button class="nav-item nav-accordion-trigger <?= $inputMenuOpen ? 'active' : '' ?>" type="button" data-nav-accordion-trigger aria-expanded="<?= $inputMenuOpen ? 'true' : 'false' ?>">
                        <span class="nav-icon"><i class="fa-solid fa-file-circle-plus"></i></span>
                        <span>Input Pos Tes</span>
                        <i class="fa-solid fa-chevron-down nav-chevron"></i>
                    </button>
                    <div class="nav-submenu">
                        <a class="nav-subitem <?= in_array($currentPage, ['test-create.php', 'test-edit.php'], true) ? 'active' : '' ?>" href="<?= e(base_url('test-create.php')) ?>">
                            <i class="fa-solid fa-id-card"></i><span><strong>Daftar Atlet Tes</strong><small>Buat biodata dan nomor tes</small></span>
                        </a>
                        <?php foreach (physical_test_items() as $navCode => $navItem): ?>
                            <a class="nav-subitem <?= $currentPage === 'test-station.php' && ($_GET['code'] ?? '') === $navCode ? 'active' : '' ?>" href="<?= e(base_url('test-station.php?code=' . urlencode($navCode))) ?>">
                                <i class="fa-solid fa-clipboard-check"></i><span><strong><?= e($navItem['method']) ?></strong><small><?= e($navItem['component']) ?></small></span>
                            </a>
                        <?php endforeach; ?>
                        <a class="nav-subitem <?= $currentPage === 'bleep-test.php' ? 'active' : '' ?>" href="<?= e(base_url('bleep-test.php')) ?>">
                            <i class="fa-solid fa-heart-pulse"></i><span><strong>Bleep Test VO2max</strong><small>Daya tahan aerobik 20 meter</small></span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            <a class="nav-item <?= in_array($currentPage, ['reports.php', 'test-view.php'], true) ? 'active' : '' ?>" href="<?= e(base_url('reports.php')) ?>">
                <span class="nav-icon"><i class="fa-solid fa-file-lines"></i></span> Laporan
            </a>
            <a class="nav-item <?= $currentPage === 'master-data.php' ? 'active' : '' ?>" href="<?= e(base_url('master-data.php')) ?>">
                <span class="nav-icon"><i class="fa-solid fa-people-group"></i></span> Atlet & Pelatih
            </a>
            <?php if ($role === 'superadmin'): ?>
                <p class="nav-label">ADMINISTRASI</p>
                <a class="nav-item <?= $currentPage === 'users.php' ? 'active' : '' ?>" href="<?= e(base_url('users.php')) ?>">
                    <span class="nav-icon"><i class="fa-solid fa-user-gear"></i></span> Manajemen User
                </a>
                <a class="nav-item <?= $currentPage === 'audit-logs.php' ? 'active' : '' ?>" href="<?= e(base_url('audit-logs.php')) ?>">
                    <span class="nav-icon"><i class="fa-solid fa-clock-rotate-left"></i></span> Audit Log
                </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-user">
            <div class="avatar"><?= e(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?></div>
            <div class="user-copy"><strong><?= e($user['name'] ?? '') ?></strong><small><?= e(ucfirst($user['role'] ?? '')) ?></small></div>
            <form method="post" action="<?= e(base_url('logout.php')) ?>">
                <?= csrf_field() ?>
                <button class="logout-button" title="Keluar" type="submit" aria-label="Keluar"><i class="fa-solid fa-right-from-bracket"></i></button>
            </form>
        </div>
    </aside>
    <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Tutup menu"></button>
    <div class="main-wrap">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
            <div><p class="topbar-date"><?= e(date('l, d F Y')) ?></p></div>
            <div class="topbar-badge">KONI SUMBAR <span>2026</span></div>
        </header>
        <main class="content">
            <?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($message = flash('error')): ?><div class="alert alert-danger"><?= e($message) ?></div><?php endif; ?>
