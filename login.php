<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (Auth::check()) {
    redirect(Auth::homePath());
}

$error = null;
if (request_method('POST')) {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (Auth::attempt($username, $password)) {
        redirect(Auth::homePath());
    }
    $error = 'Username atau password tidak sesuai.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>Login | <?= e(config('app.name')) ?></title>
    <link rel="icon" type="image/svg+xml" href="https://konisumbar.org/assets/img/logo_no_text.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Source+Serif+4:opsz,wght@8..60,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/app.css?v=' . filemtime(BASE_PATH . '/assets/css/app.css'))) ?>">
</head>
<body class="login-body">
<main class="login-shell">
    <section class="login-brand">
        <img class="brand-logo brand-logo-large" src="https://konisumbar.org/assets/img/logo_no_text.svg" alt="Logo KONI Sumatera Barat">
        <p class="eyebrow">KONI SUMATERA BARAT</p>
        <h1>Pengukuran fisik yang rapi, cepat, dan terukur.</h1>
        <p class="login-copy">Sistem pencatatan hasil tes kondisi fisik atlet Sumatera Barat tahun 2026.</p>
        <div class="login-stats">
            <span><strong>8</strong> item tes</span>
            <span><strong>1</strong> laporan terpadu</span>
        </div>
    </section>
    <section class="login-panel">
        <div class="login-card">
            <p class="eyebrow">AKSES PETUGAS</p>
            <h2>Masuk ke sistem</h2>
            <p class="muted">Gunakan akun yang telah diberikan administrator.</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" class="form-stack">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Username</span>
                    <input type="text" name="username" autocomplete="username" required autofocus>
                </label>
                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Masuk</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
