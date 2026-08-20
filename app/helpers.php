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
        'bleep_test' => ['component' => 'Daya Tahan Umum', 'detail' => '', 'method' => 'Bleep Test', 'unit' => 'level'],
    ];
}

function calculate_age(string $birthDate, ?string $atDate = null): int
{
    $birth = new DateTimeImmutable($birthDate);
    $at = new DateTimeImmutable($atDate ?: 'today');
    return $birth->diff($at)->y;
}
