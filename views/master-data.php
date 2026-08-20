<div class="page-heading">
    <div><p class="eyebrow">SUMBER GOOGLE SHEET</p><h1>Master Atlet & Pelatih</h1><p>Daftar resmi nama, cabor, prestasi, dan status pembinaan.</p></div>
    <?php if ((Auth::user()['role'] ?? '') === 'superadmin'): ?>
        <form method="post" data-confirm="Ambil ulang seluruh data dari Google Sheet?"><?= csrf_field() ?><button class="btn btn-primary" type="submit">Sinkronkan Google Sheet</button></form>
    <?php endif; ?>
</div>
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
    <div class="table-wrap"><table><thead><tr><th>Nama</th><th>Jenis</th><th>L/P</th><th>Cabor</th><th>Prestasi</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
    <?php if (!$people): ?><tr><td colspan="7" class="empty-state">Belum ada master data. Superadmin dapat menjalankan sinkronisasi.</td></tr><?php endif; ?>
    <?php foreach ($people as $person): ?><tr><td><strong><?= e($person['name']) ?></strong></td><td><span class="role-badge"><?= e($person['person_type']) ?></span></td><td><?= e($person['gender']) ?></td><td><strong><?= e($person['sport_name']) ?></strong></td><td><?= e($person['achievement'] ?: '-') ?></td><td><?= e($person['development_status'] ?: '-') ?></td><td class="wrap-cell"><?= e($person['description'] ?: '-') ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
