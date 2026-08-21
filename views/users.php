<div class="page-heading">
    <div><p class="eyebrow">SUPERADMIN</p><h1>Manajemen User</h1><p>Kelola petugas yang dapat mengakses sistem.</p></div>
</div>
<section class="split-layout">
    <article class="panel form-panel">
        <div class="panel-header"><div><p class="eyebrow"><?= $editing ? 'UBAH AKUN' : 'AKUN BARU' ?></p><h2><?= $editing ? 'Edit User' : 'Tambah User' ?></h2></div></div>
        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($editing['id'] ?? 0) ?>">
            <label class="field"><span>Nama Lengkap *</span><input name="name" value="<?= e($editing['name'] ?? '') ?>" required></label>
            <label class="field"><span>Username *</span><input name="username" value="<?= e($editing['username'] ?? '') ?>" required></label>
            <label class="field"><span>Password <?= $editing ? '(kosongkan jika tidak diubah)' : '*' ?></span><input type="password" name="password" <?= $editing ? '' : 'required minlength="8"' ?>></label>
            <label class="field"><span>Peran *</span><select name="role" required><option value="input" <?= ($editing['role'] ?? '') === 'input' ? 'selected' : '' ?>>Input</option><option value="panitia" <?= ($editing['role'] ?? '') === 'panitia' ? 'selected' : '' ?>>Panitia</option><option value="superadmin" <?= ($editing['role'] ?? '') === 'superadmin' ? 'selected' : '' ?>>Superadmin</option></select></label>
            <label class="check-field"><input type="checkbox" name="is_active" value="1" <?= !isset($editing['is_active']) || $editing['is_active'] ? 'checked' : '' ?>><span>User aktif</span></label>
            <div class="button-row"><button class="btn btn-primary" type="submit"><?= $editing ? 'Simpan Perubahan' : 'Tambah User' ?></button><?php if ($editing): ?><a class="btn btn-light" href="<?= e(base_url('users.php')) ?>">Batal</a><?php endif; ?></div>
        </form>
    </article>
    <article class="panel panel-grow">
        <div class="panel-header"><div><p class="eyebrow">DAFTAR AKSES</p><h2>Semua User</h2></div><span class="count-badge"><?= e(count($users)) ?> user</span></div>
        <div class="table-wrap">
            <table><thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($users as $item): ?><tr>
                <td><strong><?= e($item['name']) ?></strong><small class="cell-subtext">Sejak <?= e(format_date($item['created_at'])) ?></small></td>
                <td><?= e($item['username']) ?></td><td><span class="role-badge"><?= e(ucfirst($item['role'])) ?></span></td>
                <td><span class="status-dot <?= $item['is_active'] ? 'online' : '' ?>"></span><?= $item['is_active'] ? 'Aktif' : 'Nonaktif' ?></td>
                <td><div class="action-row"><a class="btn btn-small btn-light" href="<?= e(base_url('users.php?edit=' . $item['id'])) ?>">Edit</a><?php if ((int) $item['id'] !== (int) Auth::user()['id']): ?><form method="post" data-confirm="Hapus user ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button class="btn btn-small btn-danger" type="submit">Hapus</button></form><?php endif; ?></div></td>
            </tr><?php endforeach; ?>
            </tbody></table>
        </div>
    </article>
</section>
