<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireRole('superadmin');

$pdo = Database::connection();
$editing = null;

if (isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT id, name, username, role, is_active FROM users WHERE id = ?');
    $statement->execute([(int) $_GET['edit']]);
    $editing = $statement->fetch() ?: null;
}

if (request_method('POST')) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');

    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === (int) Auth::user()['id']) {
                throw new RuntimeException('Akun yang sedang digunakan tidak dapat dihapus.');
            }
            $statement = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $statement->execute([$id]);
            flash('success', 'User berhasil dihapus.');
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $role = in_array($_POST['role'] ?? '', ['superadmin', 'input', 'panitia'], true) ? $_POST['role'] : 'input';
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($name === '' || $username === '') {
                throw new RuntimeException('Nama dan username wajib diisi.');
            }
            if ($id === 0 && strlen($password) < 8) {
                throw new RuntimeException('Password user baru minimal 8 karakter.');
            }

            if ($id > 0) {
                if ($id === (int) Auth::user()['id'] && ($role !== 'superadmin' || $isActive === 0)) {
                    throw new RuntimeException('Akun yang sedang digunakan harus tetap aktif sebagai superadmin.');
                }
                if ($password !== '') {
                    $statement = $pdo->prepare('UPDATE users SET name = ?, username = ?, password = ?, role = ?, is_active = ? WHERE id = ?');
                    $statement->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role, $isActive, $id]);
                } else {
                    $statement = $pdo->prepare('UPDATE users SET name = ?, username = ?, role = ?, is_active = ? WHERE id = ?');
                    $statement->execute([$name, $username, $role, $isActive, $id]);
                }
                flash('success', 'User berhasil diperbarui.');
            } else {
                $statement = $pdo->prepare('INSERT INTO users (name, username, password, role, is_active) VALUES (?, ?, ?, ?, ?)');
                $statement->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role, $isActive]);
                flash('success', 'User baru berhasil ditambahkan.');
            }
        }
    } catch (PDOException $exception) {
        flash('error', $exception->getCode() === '23000' ? 'Username sudah digunakan.' : 'Data user gagal disimpan.');
    } catch (RuntimeException $exception) {
        flash('error', $exception->getMessage());
    }
    redirect('users.php');
}

$users = $pdo->query('SELECT id, name, username, role, is_active, created_at FROM users ORDER BY created_at DESC')->fetchAll();
view('users', ['pageTitle' => 'Manajemen User', 'users' => $users, 'editing' => $editing]);
