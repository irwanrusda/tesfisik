<div class="page-heading station-heading">
    <div><p class="eyebrow">POS INPUT TES</p><h1><?= e($item['method']) ?></h1><p><?= e($item['component']) ?><?= $item['detail'] !== '' ? ' · ' . e($item['detail']) : '' ?>. Petugas pos cukup pilih atlet yang menunggu, isi skor, lalu simpan.</p></div>
    <a class="btn btn-light" href="<?= e(base_url('test-create.php')) ?>">＋ Daftarkan Atlet Tes</a>
</div>
<section class="station-tabs panel">
    <?php foreach ($items as $itemCode => $definition): ?>
        <a class="station-tab <?= $itemCode === $code ? 'active' : '' ?>" href="<?= e(base_url('test-station.php?code=' . urlencode($itemCode) . '&date=' . urlencode($date))) ?>">
            <strong><?= e($definition['method']) ?></strong><small><?= e($definition['unit']) ?></small>
        </a>
    <?php endforeach; ?>
</section>
<section class="summary-kpis station-kpis">
    <div class="metric-card metric-primary"><span>Total Atlet</span><strong><?= e((int) ($counts['total'] ?? 0)) ?></strong><small>Tanggal <?= e(format_date($date)) ?></small></div>
    <div class="metric-card metric-warning"><span>Menunggu</span><strong><?= e((int) ($counts['waiting'] ?? 0)) ?></strong><small>Belum diisi pos ini</small></div>
    <div class="metric-card metric-success"><span>Selesai</span><strong><?= e((int) ($counts['done'] ?? 0)) ?></strong><small>Sudah punya skor</small></div>
</section>
<section class="panel filter-panel">
    <form class="filter-grid station-filter" method="get">
        <input type="hidden" name="code" value="<?= e($code) ?>">
        <label class="field"><span>Tanggal Tes</span><input type="date" name="date" value="<?= e($date) ?>"></label>
        <label class="field"><span>Status</span><select name="status"><option value="waiting" <?= $status === 'waiting' ? 'selected' : '' ?>>Menunggu</option><option value="done" <?= $status === 'done' ? 'selected' : '' ?>>Selesai</option><option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua</option></select></label>
        <label class="field"><span>Cari Atlet/Cabor</span><input name="q" value="<?= e($q) ?>" placeholder="Nama atlet, cabor, atau nomor tes"></label>
        <div class="filter-actions"><button class="btn btn-primary" type="submit">Terapkan</button><a class="btn btn-light" href="<?= e(base_url('test-station.php?code=' . urlencode($code))) ?>">Reset</a></div>
    </form>
</section>
<section class="panel station-list-panel">
    <div class="panel-header"><div><p class="eyebrow">DAFTAR ATLET</p><h2><?= $status === 'waiting' ? 'Atlet Menunggu' : ($status === 'done' ? 'Atlet Selesai' : 'Semua Atlet') ?></h2></div><span class="count-badge"><?= e(count($rows)) ?> data</span></div>
    <div class="station-list">
        <?php if (!$rows): ?><p class="empty-state">Belum ada atlet pada filter ini.</p><?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <article class="station-card <?= $row['result_value'] === null ? 'waiting' : 'done' ?>">
                <div class="station-athlete">
                    <span class="person-monogram"><?= e(strtoupper(substr($row['athlete_name'], 0, 1))) ?></span>
                    <div><strong><?= e($row['athlete_name']) ?></strong><small><?= e($row['sport']) ?> · <?= e($row['gender'] === 'L' ? 'Laki-laki' : 'Perempuan') ?> · <?= e($row['test_number']) ?></small></div>
                </div>
                <form class="station-score-form" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="test_code" value="<?= e($code) ?>">
                    <input type="hidden" name="date" value="<?= e($date) ?>">
                    <input type="hidden" name="status" value="<?= e($status) ?>">
                    <input type="hidden" name="q" value="<?= e($q) ?>">
                    <input type="hidden" name="athlete_test_id" value="<?= e($row['id']) ?>">
                    <label class="field compact"><span>Skor (<?= e($item['unit']) ?>)</span><input type="number" step="0.01" min="0" name="result_value" value="<?= e($row['result_value'] ?? '') ?>" required></label>
                    <label class="field compact"><span>Kategori</span><select name="category"><option value="">Pilih kategori</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>" <?= $row['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></label>
                    <label class="field compact"><span>Ket/Paraf</span><input name="examiner_notes" value="<?= e($row['examiner_notes'] ?? '') ?>" placeholder="Opsional"></label>
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
