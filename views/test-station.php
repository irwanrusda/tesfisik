<div data-auto-refresh="30000" data-preserve-station-forms data-refresh-page="station">
<div class="page-heading station-heading">
    <div><p class="eyebrow">POS INPUT TES</p><h1><?= e($item['method']) ?></h1><p><?= e($item['component']) ?><?= $item['detail'] !== '' ? ' · ' . e($item['detail']) : '' ?>. Petugas pos cukup pilih atlet yang menunggu, isi skor, lalu simpan.</p></div>
    <?php if (!crud_locked_for_current_user()): ?><a class="btn btn-light station-register-button" href="<?= e(base_url('test-create.php')) ?>">＋ Daftarkan Atlet Tes</a><?php endif; ?>
</div>
<section class="station-current panel">
    <div class="station-current-main">
        <span class="station-current-label">Pos aktif</span>
        <strong class="station-current-title"><?= e($item['method']) ?></strong>
        <div class="station-current-meta">
            <span><?= e($item['component']) ?></span>
            <?php if ($item['detail'] !== ''): ?><span><?= e($item['detail']) ?></span><?php endif; ?>
            <span>Satuan: <?= e($item['unit']) ?></span>
        </div>
        <?php if ($code === 'sit_up'): ?><p class="station-condition-note">Jumlah gerakan selama <?= e($conditions['sit_up_duration_seconds']) ?> detik.</p><?php endif; ?>
        <?php if ($code === 'push_up'): ?><p class="station-condition-note">Jumlah gerakan selama <?= e($conditions['push_up_duration_seconds']) ?> detik.</p><?php endif; ?>
        <?php if ($code === 'pull_up'): ?><p class="station-condition-note">Laki-laki: jumlah angkat badan. Perempuan: <?= $conditions['female_pull_up_mode'] === 'hold' ? 'menahan badan dalam detik' : 'jumlah angkat badan' ?>.</p><?php endif; ?>
    </div>
    <div class="station-current-date"><i class="fa-regular fa-calendar"></i><span><?= e(format_date($date)) ?></span></div>
</section>
<section class="summary-kpis station-kpis">
    <div class="metric-card metric-primary"><span>Total</span><strong><?= e((int) ($counts['total'] ?? 0)) ?></strong><small>Atlet hari ini</small></div>
    <div class="metric-card metric-warning"><span>Menunggu</span><strong><?= e((int) ($counts['waiting'] ?? 0)) ?></strong><small>Belum isi</small></div>
    <div class="metric-card metric-success"><span>Selesai</span><strong><?= e((int) ($counts['done'] ?? 0)) ?></strong><small>Sudah isi</small></div>
</section>
<section class="panel filter-panel">
    <form class="filter-grid station-filter" method="get" data-station-filter>
        <input type="hidden" name="code" value="<?= e($code) ?>">
        <label class="field"><span>Tanggal Tes</span><input type="date" name="date" value="<?= e($date) ?>"></label>
        <label class="field"><span>Status</span><select name="status"><option value="waiting" <?= $status === 'waiting' ? 'selected' : '' ?>>Menunggu</option><option value="done" <?= $status === 'done' ? 'selected' : '' ?>>Selesai</option><option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua</option></select></label>
        <label class="field"><span>Cari Atlet/Cabor</span><input name="q" value="<?= e($q) ?>" placeholder="Nama atlet, cabor, atau nomor tes" autocomplete="off" data-station-search></label>
        <div class="filter-actions"><button class="btn btn-primary" type="submit">Terapkan</button><a class="btn btn-light" href="<?= e(base_url('test-station.php?code=' . urlencode($code))) ?>">Reset</a></div>
    </form>
</section>
<section class="panel station-list-panel" data-station-results>
    <div class="panel-header"><div><p class="eyebrow">DAFTAR ATLET</p><h2><?= $status === 'waiting' ? 'Atlet Menunggu' : ($status === 'done' ? 'Atlet Selesai' : 'Semua Atlet') ?></h2></div><span class="count-badge"><?= e(count($rows)) ?> data</span></div>
    <div class="station-list">
        <?php if (!$rows): ?><p class="empty-state">Belum ada atlet pada filter ini.</p><?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <?php $rowDefinition = $row['input_definition']; ?>
            <article class="station-card <?= $row['result_value'] === null ? 'waiting' : 'done' ?>" data-station-card>
                <button class="station-athlete station-athlete-toggle" type="button" data-station-toggle aria-expanded="false">
                    <span class="person-monogram"><?= e(strtoupper(substr($row['athlete_name'], 0, 1))) ?></span>
                    <div class="station-athlete-copy"><strong><?= e($row['athlete_name']) ?></strong><div class="station-athlete-meta"><span><?= e($row['sport']) ?></span><span class="station-birth-date"><i class="fa-regular fa-calendar"></i><?= e(format_date($row['birth_date'])) ?></span><span class="station-meta-secondary"><?= e($row['gender'] === 'L' ? 'Laki-laki' : 'Perempuan') ?></span><span class="station-meta-secondary"><?= e($row['test_number']) ?></span></div></div>
                    <span class="station-status-pill <?= $row['result_value'] === null ? 'waiting' : 'done' ?>"><?= $row['result_value'] === null ? 'Menunggu' : 'Selesai' ?></span>
                    <i class="fa-solid fa-chevron-down station-expand-icon"></i>
                </button>
                <?php if (!crud_locked_for_current_user()): ?><form class="station-score-form" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="test_code" value="<?= e($code) ?>">
                    <input type="hidden" name="date" value="<?= e($date) ?>">
                    <input type="hidden" name="status" value="<?= e($status) ?>">
                    <input type="hidden" name="q" value="<?= e($q) ?>">
                    <input type="hidden" name="athlete_test_id" value="<?= e($row['id']) ?>">
                    <label class="field compact"><span><?= e($rowDefinition['method']) ?> (<?= e($rowDefinition['unit']) ?>) *</span><input type="number" step="0.01" min="0" name="result_value" value="<?= e($row['result_value'] ?? '') ?>" required></label>
                    <div class="station-indicator <?= $row['indicator']['met'] === true ? 'met' : ($row['indicator']['met'] === false ? 'unmet' : '') ?>"><span>Indikator 80%</span><?php if ($row['indicator']['available']): ?><strong><?= e($row['indicator']['operator']) ?> <?= e($row['indicator']['threshold']) ?> <?= e($rowDefinition['unit']) ?></strong><small><?= e($row['indicator']['label']) ?></small><?php else: ?><strong>Belum tersedia</strong><small>Tidak ada padanan standar untuk cabor/tes ini.</small><?php endif; ?></div>
                    <label class="field compact station-category-field"><span>Kategori</span><select name="category"><option value="">Pilih kategori</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>" <?= $row['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></label>
                    <label class="field compact"><span>Ket/Paraf</span><input name="examiner_notes" value="<?= e($row['examiner_notes'] ?? '') ?>" placeholder="Opsional"></label>
                    <div class="station-documentation">
                        <div class="station-documentation-heading"><span><i class="fa-solid fa-camera"></i></span><div><p class="eyebrow">DOKUMENTASI</p><strong>Foto Kegiatan Tes</strong></div></div>
                        <label class="station-photo-upload"><input type="file" name="station_photos[]" accept="image/*" multiple data-photo-input><i class="fa-solid fa-paperclip"></i><span>Lampirkan foto pos</span></label>
                        <div class="station-photo-preview" data-photo-preview></div>
                        <?php if ($row['station_photos']): ?><div class="station-photo-list"><?php foreach ($row['station_photos'] as $photo): ?><a href="<?= e(signed_photo_url((int) $photo['id'])) ?>" target="_blank"><img src="<?= e(signed_photo_url((int) $photo['id'])) ?>" alt="<?= e($photo['original_name']) ?>" loading="lazy"><span><?= e($photo['original_name']) ?></span></a><?php endforeach; ?></div><?php endif; ?>
                    </div>
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </form><?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
</div>
