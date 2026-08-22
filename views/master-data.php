<?php
$oldValue = static fn(string $key, mixed $default = ''): mixed => function_exists('old') ? old($key, $default) : ($_SESSION['_old'][$key] ?? $default);
?>
<div class="page-heading">
    <div><p class="eyebrow">SUMBER GOOGLE SHEET & WEBSITE</p><h1>Master Atlet & Pelatih</h1><p>Daftar resmi nama, cabor, prestasi, dan status pembinaan. Atlet dari website tetap aktif saat sinkron Google Sheet.</p></div>
    <?php if ((Auth::user()['role'] ?? '') === 'superadmin'): ?>
        <div class="button-row"><a class="btn btn-primary" href="#tambah-atlet"><i class="fa-solid fa-user-plus"></i> Tambah Atlet</a><form method="post" data-confirm="Ambil ulang data dari Google Sheet? Data atlet yang ditambahkan dari website tetap dipertahankan."><?= csrf_field() ?><input type="hidden" name="action" value="sync"><button class="btn btn-light" type="submit">Sinkronkan Google Sheet</button></form></div>
    <?php endif; ?>
</div>
<?php if ((Auth::user()['role'] ?? '') === 'superadmin'): ?>
<section class="panel add-athlete-panel" id="tambah-atlet">
    <div class="panel-header"><div><p class="eyebrow">SUPERADMIN</p><h2>Tambah Atlet ke Master Data</h2><p>Data akan disimpan di database website dan langsung tersedia di aplikasi. Setelah itu Bang Irwan bisa copy manual ke spreadsheet.</p></div><span class="count-badge"><i class="fa-solid fa-database"></i> Website</span></div>
    <form method="post" class="add-athlete-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="add_athlete">
        <label class="field field-span-2"><span>Nama Atlet *</span><input name="name" value="<?= e($oldValue('name')) ?>" required></label>
        <label class="field"><span>Jenis Kelamin *</span><select name="gender" required><option value="">Pilih</option><option value="L" <?= $oldValue('gender') === 'L' ? 'selected' : '' ?>>Laki-Laki</option><option value="P" <?= $oldValue('gender') === 'P' ? 'selected' : '' ?>>Perempuan</option></select></label>
        <label class="field"><span>Cabang Olahraga *</span><input name="sport" value="<?= e($oldValue('sport')) ?>" list="sports-list" required><datalist id="sports-list"><?php foreach ($sports as $item): ?><option value="<?= e($item) ?>"><?php endforeach; ?></datalist></label>
        <label class="field"><span>Prestasi</span><input name="achievement" value="<?= e($oldValue('achievement')) ?>" placeholder="Contoh: PON Beladiri 2025"></label>
        <label class="field"><span>Status Pembinaan</span><select name="development_status"><option value="">Belum ditentukan</option><?php foreach (['Andalan', 'Prioritas', 'Potensial'] as $item): ?><option value="<?= e($item) ?>" <?= $oldValue('development_status') === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></label>
        <label class="field field-span-2"><span>Keterangan</span><textarea name="description" rows="3"><?= e($oldValue('description')) ?></textarea></label>
        <div class="form-actions field-span-2"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan ke Website</button></div>
    </form>
</section>
<?php endif; ?>
<section class="stats-grid master-stats">
    <article class="stat-card accent-red"><span>Total Data Aktif</span><strong><?= e((int) ($counts['total'] ?? 0)) ?></strong><small>Atlet dan pelatih</small></article>
    <article class="stat-card accent-blue"><span>Atlet</span><strong><?= e((int) ($counts['athletes'] ?? 0)) ?></strong><small>Siap dipilih saat input tes</small></article>
    <article class="stat-card accent-gold"><span>Pelatih</span><strong><?= e((int) ($counts['coaches'] ?? 0)) ?></strong><small>Data pendamping cabor</small></article>
    <article class="stat-card accent-green"><span>Cabang Olahraga</span><strong><?= e(count($sports)) ?></strong><small>Sinkron terakhir <?= e($counts['last_sync'] ? format_date($counts['last_sync'], 'd-m-Y H:i') : '-') ?></small></article>
</section>
<section class="panel filter-panel">
    <form method="get" class="filter-grid master-filter">
        <label class="field"><span>Cari nama</span><input name="q" value="<?= e($q) ?>" placeholder="Nama atlet atau pelatih"></label>
        <label class="field"><span>Jenis Data</span><select name="type"><option value="">Semua</option><option value="Atlet" <?= $type === 'Atlet' ? 'selected' : '' ?>>Atlet</option><option value="Pelatih" <?= $type === 'Pelatih' ? 'selected' : '' ?>>Pelatih</option></select></label>
        <label class="field"><span>Cabang Olahraga</span><select name="sport"><option value="">Semua cabor</option><?php foreach ($sports as $item): ?><option value="<?= e($item) ?>" <?= $sport === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></label>
        <div class="filter-actions"><button class="btn btn-primary" type="submit">Terapkan</button><a class="btn btn-light" href="<?= e(base_url('master-data.php')) ?>">Reset</a></div>
    </form>
</section>
<section class="panel">
    <div class="panel-header"><div><p class="eyebrow">DAFTAR MASTER</p><h2>Atlet dan Pelatih</h2></div><span class="count-badge"><?= e(count($people)) ?> data</span></div>
    <div class="table-wrap"><table><thead><tr><th>Nama</th><th>Jenis</th><th>L/P</th><th>Cabor</th><th>Sumber</th><th>Prestasi</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
    <?php if (!$people): ?><tr><td colspan="8" class="empty-state">Belum ada master data. Superadmin dapat menjalankan sinkronisasi atau menambah atlet manual.</td></tr><?php endif; ?>
    <?php foreach ($people as $person): ?><tr><td><strong><?= e($person['name']) ?></strong></td><td><span class="role-badge"><?= e($person['person_type']) ?></span></td><td><?= e($person['gender']) ?></td><td><strong><?= e($person['sport_name']) ?></strong></td><td><span class="role-badge"><?= e(($person['data_source'] ?? 'spreadsheet') === 'website' ? 'Website' : 'Spreadsheet') ?></span></td><td><?= e($person['achievement'] ?: '-') ?></td><td><?= e($person['development_status'] ?: '-') ?></td><td class="wrap-cell"><?= e($person['description'] ?: '-') ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
