<?php

declare(strict_types=1);

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function config(string $key, mixed $default = null): mixed
{
    static $configs = [];
    [$file, $item] = array_pad(explode('.', $key, 2), 2, null);

    if (!isset($configs[$file])) {
        $path = BASE_PATH . "/config/{$file}.php";
        $configs[$file] = is_file($path) ? require $path : [];
    }

    return $item === null ? $configs[$file] : ($configs[$file][$item] ?? $default);
}

function base_url(string $path = ''): string
{
    return config('app.url') . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman dan coba lagi.');
    }
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function set_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require BASE_PATH . '/views/layout/header.php';
    require BASE_PATH . "/views/{$template}.php";
    require BASE_PATH . '/views/layout/footer.php';
}

function request_method(string $method): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
}

function format_date(?string $date, string $format = 'd-m-Y'): string
{
    return $date ? date($format, strtotime($date)) : '-';
}

function nullable_number(mixed $value): ?float
{
    if ($value === '' || $value === null) {
        return null;
    }
    return (float) str_replace(',', '.', (string) $value);
}

function physical_test_items(): array
{
    return [
        'sit_up' => ['component' => 'Kekuatan', 'detail' => 'Otot Perut', 'method' => 'Sit Up', 'unit' => 'kali'],
        'push_up' => ['component' => 'Kekuatan', 'detail' => 'Otot Lengan & Bahu', 'method' => 'Push Up', 'unit' => 'kali'],
        'pull_up' => ['component' => 'Kekuatan', 'detail' => 'Otot Seluruh Tubuh Atas', 'method' => 'Pull Up', 'unit' => 'kali'],
        'medicine_ball' => ['component' => 'Power (Daya Ledak)', 'detail' => 'Otot Lengan & Bahu', 'method' => 'Medicine Ball Put', 'unit' => 'cm'],
        'vertical_jump' => ['component' => 'Power (Daya Ledak)', 'detail' => 'Otot Tungkai', 'method' => 'Vertical Jump', 'unit' => 'cm'],
        'sprint_30m' => ['component' => 'Kecepatan', 'detail' => 'Kecepatan Lari', 'method' => 'Lari 30 Meter', 'unit' => 'detik'],
        'illinois' => ['component' => 'Kelincahan', 'detail' => 'Kelincahan Seluruh Tubuh', 'method' => 'Illinois Test', 'unit' => 'detik'],
        'sit_reach' => ['component' => 'Fleksibilitas', 'detail' => '', 'method' => 'Sit and Reach', 'unit' => 'cm'],
    ];
}

function test_condition_defaults(): array
{
    return [
        'sit_up_duration_seconds' => '60',
        'push_up_duration_seconds' => '60',
        'female_pull_up_mode' => 'repetitions',
    ];
}

function test_conditions(?PDO $pdo = null): array
{
    $conditions = test_condition_defaults();
    try {
        $pdo ??= Database::connection();
        $rows = $pdo->query('SELECT setting_key, setting_value FROM test_settings')->fetchAll();
        foreach ($rows as $row) {
            if (array_key_exists($row['setting_key'], $conditions) && $row['setting_value'] !== '') {
                $conditions[$row['setting_key']] = $row['setting_value'];
            }
        }
    } catch (Throwable) {
        // Defaults keep test stations usable before the migration is run.
    }
    return $conditions;
}

function physical_test_input_definition(string $code, string $gender, ?array $conditions = null): array
{
    $items = physical_test_items();
    if (!isset($items[$code])) {
        throw new InvalidArgumentException('Komponen tes tidak ditemukan.');
    }

    $conditions ??= test_conditions();
    $definition = $items[$code];
    $definition['instruction'] = '';

    if ($code === 'sit_up') {
        $duration = max(1, (int) ($conditions['sit_up_duration_seconds'] ?? 60));
        $definition['instruction'] = "Hitung jumlah Sit Up selama {$duration} detik.";
    } elseif ($code === 'push_up') {
        $duration = max(1, (int) ($conditions['push_up_duration_seconds'] ?? 60));
        $definition['instruction'] = "Hitung jumlah Push Up selama {$duration} detik.";
    } elseif ($code === 'pull_up') {
        if ($gender === 'P' && ($conditions['female_pull_up_mode'] ?? 'repetitions') === 'hold') {
            $definition['method'] = 'Menahan Badan';
            $definition['unit'] = 'detik';
            $definition['instruction'] = 'Catat waktu atlet perempuan menahan badan dalam satuan detik.';
        } elseif ($gender === 'P') {
            $definition['instruction'] = 'Hitung jumlah angkat badan atlet perempuan.';
        } else {
            $definition['instruction'] = 'Hitung jumlah angkat badan atlet laki-laki.';
        }
    }

    return $definition;
}

function crud_is_locked(?PDO $pdo = null): bool
{
    try {
        $pdo ??= Database::connection();
        $statement = $pdo->prepare("SELECT setting_value FROM test_settings WHERE setting_key = 'crud_locked' LIMIT 1");
        $statement->execute();
        return $statement->fetchColumn() === '1';
    } catch (Throwable) {
        return false;
    }
}

function crud_locked_for_current_user(?PDO $pdo = null): bool
{
    return (Auth::user()['role'] ?? '') !== 'superadmin' && crud_is_locked($pdo);
}

function require_crud_open(?PDO $pdo = null): void
{
    if (!crud_locked_for_current_user($pdo)) {
        return;
    }

    flash('error', 'Entri, edit, dan hapus data sedang ditutup oleh superadmin.');
    redirect('reports.php');
}

function calculate_age(string $birthDate, ?string $atDate = null): int
{
    $birth = new DateTimeImmutable($birthDate);
    $at = new DateTimeImmutable($atDate ?: 'today');
    if ($at < $birth) {
        throw new InvalidArgumentException('Tanggal tes tidak boleh lebih awal dari tanggal lahir.');
    }
    return $birth->diff($at)->y;
}

function bleep_test_protocol(): array
{
    $shuttles = [1 => 7, 8, 8, 9, 9, 10, 10, 11, 11, 11, 12, 12, 13, 13, 13, 14, 14, 15, 15, 16, 16];
    $protocol = [];
    $cumulativeShuttles = 0;

    foreach ($shuttles as $level => $levelShuttles) {
        $cumulativeShuttles += $levelShuttles;
        $protocol[$level] = [
            'level' => $level,
            'speed' => 8 + (0.5 * $level),
            'shuttles' => $levelShuttles,
            'level_distance' => $levelShuttles * 20,
            'cumulative_shuttles' => $cumulativeShuttles,
            'cumulative_distance' => $cumulativeShuttles * 20,
        ];
    }

    return $protocol;
}

function bleep_vo2max_from_level_shuttle(int $level, int $shuttle): float
{
    // Tabel VO2max Lari Multi Tahap/Bleep Test yang umum dipakai di Indonesia.
    // Rumus ini mereplikasi tabel level-shuttle: contoh 4.2 = 26.8,
    // 5.2 = 30.2, 15.2 = 64.6 ml/kg/menit.
    $levelShuttleScore = $level + ($shuttle / (($level * 0.4325) + 7.0048));
    return round((3.46 * $levelShuttleScore) + 12.19, 1);
}

function bleep_test_metrics(int $level, int $shuttle, ?int $age = null): array
{
    $protocol = bleep_test_protocol();
    if (!isset($protocol[$level])) {
        throw new InvalidArgumentException('Level Bleep Test harus berada pada rentang 1 sampai 21.');
    }
    if ($shuttle < 0 || $shuttle > $protocol[$level]['shuttles']) {
        throw new InvalidArgumentException("Shuttle level {$level} harus berada pada rentang 0 sampai {$protocol[$level]['shuttles']}.");
    }

    $previousShuttles = $level > 1 ? $protocol[$level - 1]['cumulative_shuttles'] : 0;
    $completedShuttles = $previousShuttles + $shuttle;
    $levelFraction = $protocol[$level]['shuttles'] > 0 ? $shuttle / $protocol[$level]['shuttles'] : 0;
    $speed = 8 + (0.5 * ($level + $levelFraction));

    return [
        'vo2max' => bleep_vo2max_from_level_shuttle($level, $shuttle),
        'speed' => round($speed, 2),
        'completed_shuttles' => $completedShuttles,
        'distance' => $completedShuttles * 20,
    ];
}

function calculate_bleep_vo2max(int $level, int $age = 20, int $shuttle = 0): float
{
    return bleep_test_metrics($level, $shuttle)['vo2max'];
}

function vo2max_age_group(?int $age): string
{
    if ($age === null || $age < 26) return '18-25';
    if ($age < 36) return '26-35';
    if ($age < 46) return '36-45';
    if ($age < 56) return '46-55';
    if ($age < 66) return '56-65';
    return '65+';
}

function vo2max_category(float $vo2max, string $gender, ?int $age = null): string
{
    $group = vo2max_age_group($age);
    $genderKey = $gender === 'P' ? 'P' : 'L';
    $norms = [
        'L' => [
            '18-25' => [60, 52, 47, 42, 37, 30],
            '26-35' => [56, 49, 43, 40, 35, 30],
            '36-45' => [51, 43, 39, 35, 31, 26],
            '46-55' => [45, 39, 36, 32, 29, 25],
            '56-65' => [41, 36, 32, 30, 26, 22],
            '65+' => [37, 33, 29, 26, 22, 20],
        ],
        'P' => [
            '18-25' => [56, 47, 42, 38, 33, 28],
            '26-35' => [52, 45, 39, 35, 31, 26],
            '36-45' => [45, 38, 34, 31, 27, 22],
            '46-55' => [40, 34, 31, 28, 25, 20],
            '56-65' => [37, 32, 28, 25, 22, 18],
            '65+' => [32, 28, 25, 22, 19, 17],
        ],
    ];
    [$excellent, $good, $aboveAverage, $average, $belowAverage, $poor] = $norms[$genderKey][$group];

    if ($vo2max > $excellent) return 'Excellent';
    if ($vo2max >= $good) return 'Good';
    if ($vo2max >= $aboveAverage) return 'Above Average';
    if ($vo2max >= $average) return 'Average';
    if ($vo2max >= $belowAverage) return 'Below Average';
    if ($vo2max >= $poor) return 'Poor';
    return 'Very Poor';
}

function uploaded_files(string $field): array
{
    $files = $_FILES[$field] ?? null;
    if (!$files || !is_array($files['name'] ?? null)) {
        return [];
    }

    $normalized = [];
    foreach ($files['name'] as $index => $name) {
        $error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $normalized[] = [
            'name' => (string) $name,
            'type' => (string) ($files['type'][$index] ?? ''),
            'tmp_name' => (string) ($files['tmp_name'][$index] ?? ''),
            'error' => $error,
            'size' => (int) ($files['size'][$index] ?? 0),
        ];
    }
    return $normalized;
}

function store_test_photos(PDO $pdo, int $testId, array $files, int $userId, ?string $testCode = null): array
{
    if (!$files) {
        return [];
    }
    if (count($files) > 10) {
        throw new RuntimeException('Maksimal 10 foto dapat diunggah sekaligus.');
    }

    $directory = BASE_PATH . '/storage/uploads/test-photos';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan foto tidak dapat dibuat.');
    }

    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $statement = $pdo->prepare('INSERT INTO test_photos (athlete_test_id, test_code, file_name, original_name, mime_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $storedPaths = [];

    try {
        foreach ($files as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Salah satu foto gagal diunggah.');
            }
            if ($file['size'] < 1 || $file['size'] > 5 * 1024 * 1024) {
                throw new RuntimeException('Ukuran setiap foto maksimal 5 MB.');
            }

            $imageInfo = @getimagesize($file['tmp_name']);
            $mimeType = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
            if (!isset($allowedTypes[$mimeType])) {
                throw new RuntimeException('Format foto harus JPG, PNG, atau WebP.');
            }

            $fileName = bin2hex(random_bytes(20)) . '.' . $allowedTypes[$mimeType];
            $target = $directory . '/' . $fileName;
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                throw new RuntimeException('Foto gagal disimpan ke server.');
            }
            $storedPaths[] = $target;
            $originalName = substr(basename(str_replace('\\', '/', $file['name'])), 0, 255);
            $statement->execute([$testId, $testCode, $fileName, $originalName, $mimeType, $file['size'], $userId]);
        }
    } catch (Throwable $exception) {
        foreach ($storedPaths as $path) {
            if (is_file($path)) unlink($path);
        }
        throw $exception;
    }

    return $storedPaths;
}

function test_photo_path(string $fileName): string
{
    return BASE_PATH . '/storage/uploads/test-photos/' . basename($fileName);
}

function signed_photo_url(int $photoId, int $validForSeconds = 900): string
{
    $expires = time() + $validForSeconds;
    $secret = (string) env('PHOTO_URL_SECRET', env('MIGRATION_KEY', ''));
    if ($secret === '') {
        throw new RuntimeException('PHOTO_URL_SECRET belum dikonfigurasi.');
    }
    $signature = hash_hmac('sha256', $photoId . '|' . $expires, $secret);
    return base_url("test-photo.php?id={$photoId}&expires={$expires}&signature={$signature}");
}

function write_audit_log(PDO $pdo, string $action, string $module, array $record, array $details = []): void
{
    $user = Auth::user();
    if (!$user) {
        throw new RuntimeException('User audit tidak tersedia.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, user_name, username, user_role, action, module, record_id, record_number, athlete_name, sport, details, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $user['id'],
        $user['name'],
        $user['username'],
        $user['role'],
        $action,
        $module,
        $record['id'] ?? null,
        $record['number'] ?? null,
        $record['athlete_name'],
        $record['sport'] ?? null,
        $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
        substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
    ]);
}
