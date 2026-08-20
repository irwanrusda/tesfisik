<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$isCli = PHP_SAPI === 'cli';
$key = (string) env('MIGRATION_KEY', '');
if (!$isCli && ($key === '' || !hash_equals($key, (string) ($_GET['key'] ?? '')))) {
    http_response_code(403);
    exit('Akses migrasi ditolak.');
}

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::connection();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS migrations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $executed = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    $files = glob(__DIR__ . '/database/migrations/*.sql') ?: [];
    sort($files, SORT_NATURAL);
    $count = 0;

    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $executed, true)) {
            echo "Lewati: {$name}\n";
            continue;
        }

        // MySQL and MariaDB implicitly commit DDL statements, so each SQL
        // migration is recorded only after the complete file succeeds.
        $pdo->exec((string) file_get_contents($file));
        $statement = $pdo->prepare('INSERT INTO migrations (migration) VALUES (?)');
        $statement->execute([$name]);
        $count++;
        echo "Berhasil: {$name}\n";
    }

    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount === 0) {
        $username = (string) env('ADMIN_USERNAME', 'admin');
        $password = (string) env('ADMIN_PASSWORD', 'Admin123!');
        $statement = $pdo->prepare(
            "INSERT INTO users (name, username, password, role, is_active) VALUES (?, ?, ?, 'superadmin', 1)"
        );
        $statement->execute(['Super Administrator', $username, password_hash($password, PASSWORD_DEFAULT)]);
        echo "Superadmin awal dibuat dengan username: {$username}\n";
        if (env('ADMIN_PASSWORD', '') === '') {
            echo "PERINGATAN: Password awal adalah Admin123! Segera ubah setelah login.\n";
        }
    }

    $defaultAccounts = [
        ['Akun Input', (string) env('INPUT_USERNAME', 'input'), (string) env('INPUT_PASSWORD', 'Input123!'), 'input'],
        ['Panitia Tes', (string) env('PANITIA_USERNAME', 'panitia'), (string) env('PANITIA_PASSWORD', 'Panitia123!'), 'panitia'],
    ];
    $accountStatement = $pdo->prepare(
        'INSERT INTO users (name, username, password, role, is_active) VALUES (?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE role = VALUES(role), is_active = 1'
    );
    foreach ($defaultAccounts as [$name, $username, $password, $role]) {
        $accountStatement->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
    }
    echo "Akun input dan panitia tersedia.\n";

    echo "Migrasi selesai. {$count} migrasi baru dijalankan.\n";
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Migrasi gagal: ' . $exception->getMessage() . "\n";
    exit(1);
}
