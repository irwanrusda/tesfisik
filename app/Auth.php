<?php

declare(strict_types=1);

final class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, username, password, role, is_active FROM users WHERE username = ? LIMIT 1'
        );
        $statement->execute([$username]);
        $user = $statement->fetch();

        if (!$user || !(bool) $user['is_active'] || !password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('login.php');
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireAnyRole([$role]);
    }

    public static function requireAnyRole(array $roles): void
    {
        self::requireLogin();
        if (!in_array(self::user()['role'] ?? '', $roles, true)) {
            http_response_code(403);
            exit('Anda tidak memiliki akses ke halaman ini.');
        }
    }

    public static function homePath(): string
    {
        return match (self::user()['role'] ?? '') {
            'input' => 'test-create.php',
            'panitia' => 'summary.php',
            default => 'dashboard.php',
        };
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
