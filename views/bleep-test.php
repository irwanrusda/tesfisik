<?php
$formValue = static fn(string $key, mixed $default = '') => old($key, $editing[$key] ?? $default);
$selectedAthleteId = (int) $formValue('master_person_id', 0);
$selectedLevel = max(1, min(21, (int) $formValue('level', 1)));
$selectedShuttle = max(0, min($protocol[$selectedLevel]['shuttles'], (int) $formValue('shuttle', 0)));
$previewBirthDate = (string) $formValue('birth_date', '');
$previewTestDate = (string) $formValue('test_date', date('Y-m-d'));
$previewAge = 20;
try {
    if ($previewBirthDate !== '' && $previewTestDate !== '') $previewAge = calculate_age($previewBirthDate, $previewTestDate);
    if ($previewAge < 6 || $previewAge > 100) $previewAge = 20;
} catch (Throwable) {
    $previewAge = 20;
}
$selectedMetrics = bleep_test_metrics($selectedLevel, $selectedShuttle, $previewAge);
$previewGender = $editing['gender'] ?? 'L';
$selectedCategory = $editing['category'] ?? vo2max_category((float) $selectedMetrics['vo2max'], $previewGender, $previewAge);
?>
<div class="page-heading">
    <div><p class="eyebrow">MODUL DAYA TAHAN AEROBIK</p><h1>Bleep Test VO2max</h1><p>Modul mandiri untuk mencatat Multistage Fitness Test 20 meter.</p></div>
    <?php if ($editing): ?><a class="btn btn-light" href="<?= e(base_url('bleep-test.php')) ?>">Input Baru</a><?php endif; ?>
</div>

<?php if (!$crudLocked): ?><form method="post" class="bleep-layout" data-bleep-form>
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($formValue('id', 0)) ?>">
    <section class="panel bleep-control-panel">
        <div class="panel-header"><div><p class="eyebrow"><?= $editing ? 'PERBARUI HASIL' : 'INPUT HASIL BARU' ?></p><h2>Data Atlet & Hasil Tes</h2></div><span class="live-badge"><i class="fa-solid fa-heart-pulse"></i> VO2max</span></div>
        <div class="bleep-athlete-fields">
            <label class="field field-span-2"><span>Pilih Atlet *</span><div class="searchable-dropdown" data-bleep-athlete-dropdown><input type="search" data-bleep-athlete-search placeholder="Cari nama atau cabang olahraga" autocomplete="off" role="combobox" required><input type="hidden" name="master_person_id" value="<?= e($selectedAthleteId) ?>" data-bleep-athlete-value><div class="searchable-options" data-bleep-athlete-options><?php foreach ($athletes as $athlete): ?><button type="button" class="searchable-option" data-bleep-athlete-option data-id="<?= e($athlete['id']) ?>" data-name="<?= e($athlete['name']) ?>" data-sport="<?= e($athlete['sport']) ?>" data-birth-date="<?= e($athlete['latest_birth_date'] ?? '') ?>" data-gender="<?= e($athlete['gender']) ?>" <?= $selectedAthleteId === (int) $athlete['id'] ? 'aria-selected="true"' : '' ?>><strong><?= e($athlete['name']) ?></strong><span><?= e($athlete['sport']) ?><?= $athlete['development_status'] ? ' · ' . e($athlete['development_status']) : '' ?></span></button><?php endforeach; ?><p class="searchable-empty" data-bleep-athlete-empty>Atlet tidak ditemukan.</p></div></div></label>
            <input type="hidden" name="birth_date" value="<?= e($formValue('birth_date')) ?>" data-bleep-birth-date>
            <label class="field"><span>Tanggal Tes *</span><input type="date" name="test_date" value="<?= e($formValue('test_date', date('Y-m-d'))) ?>" data-bleep-test-date required></label>
            <label class="field"><span>Tempat Tes</span><input name="test_place" value="<?= e($formValue('test_place', 'Padang')) ?>"></label>
            <div class="bleep-validation-message field-span-2" data-bleep-validation hidden></div>
        </div>
        <?php $bleepStandard = physical_test_indicator($editing['sport'] ?? '', $previewGender, 'bleep_test', $editing['vo2max'] ?? $selectedMetrics['vo2max']); ?>
        <div class="bleep-metrics"><div><span>VO2max</span><strong data-vo2max><?= e(format_number_id($editing['vo2max'] ?? $selectedMetrics['vo2max'], 1)) ?></strong><small>ml/kg/menit</small></div><div><span>Kecepatan</span><strong data-bleep-speed><?= e(format_number_id($editing['speed_kmh'] ?? $selectedMetrics['speed'])) ?></strong><small>km/jam</small></div><div><span>Total Shuttle</span><strong data-total-shuttles><?= e($editing['completed_shuttles'] ?? $selectedMetrics['completed_shuttles']) ?></strong><small>balikan</small></div><div><span>Total Jarak</span><strong data-bleep-distance><?= e($editing['distance_m'] ?? $selectedMetrics['distance']) ?></strong><small>meter</small></div></div>
        <div class="bleep-standard <?= $bleepStandard['met'] === true ? 'met' : ($bleepStandard['met'] === false ? 'unmet' : '') ?>" data-bleep-standard><span>Indikator 80% Permenpora</span><strong data-bleep-standard-threshold><?= $bleepStandard['available'] ? e($bleepStandard['operator'] . ' ' . format_number_id($bleepStandard['threshold']) . ' ml/kg/menit') : 'Belum tersedia untuk cabor ini' ?></strong><small data-bleep-standard-status><?= e($bleepStandard['label']) ?></small></div>
        <div class="bleep-steppers">
            <div class="number-stepper"><span>LEVEL TERAKHIR *</span><div><button type="button" data-step="level" data-direction="-1"><i class="fa-solid fa-minus"></i></button><input type="number" name="level" value="<?= e($selectedLevel) ?>" min="1" max="21" data-bleep-level required><button type="button" data-step="level" data-direction="1"><i class="fa-solid fa-plus"></i></button></div><small>Rentang level 1 sampai 21</small></div>
            <div class="number-stepper"><span>SHUTTLE / BALIKAN *</span><div><button type="button" data-step="shuttle" data-direction="-1"><i class="fa-solid fa-minus"></i></button><input type="number" name="shuttle" value="<?= e($selectedShuttle) ?>" min="0" max="<?= e($protocol[$selectedLevel]['shuttles']) ?>" data-bleep-shuttle required><button type="button" data-step="shuttle" data-direction="1"><i class="fa-solid fa-plus"></i></button></div><small data-shuttle-limit>Maksimal <?= e($protocol[$selectedLevel]['shuttles']) ?> shuttle</small></div>
        </div>
        <div class="bleep-assessment"><label class="field"><span>Kategori VO2max</span><input name="category_display" value="<?= e($selectedCategory) ?>" data-bleep-category readonly><small class="field-help">Otomatis berdasarkan VO2max, jenis kelamin, dan kelompok umur.</small></label><label class="field"><span>Keterangan / Paraf</span><input name="notes" value="<?= e($formValue('notes')) ?>"></label></div>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $editing ? 'Simpan Perubahan' : 'Simpan Bleep Test' ?></button></div>
    </section>

    <section class="panel bleep-table-panel"><div class="panel-header"><div><p class="eyebrow">PREVIEW PROTOKOL</p><h2>Tabel Bleep Test 20 Meter</h2></div><span class="count-badge">21 level</span></div><div class="bleep-table-scroll"><table class="bleep-table"><thead><tr><th>Level</th><th>Kecepatan</th><th>Shuttle</th><th>Jarak Level</th><th>Total Shuttle</th><th>Total Jarak</th></tr></thead><tbody><?php foreach ($protocol as $row): ?><tr data-protocol-row="<?= e($row['level']) ?>" class="<?= $row['level'] === $selectedLevel ? 'active' : '' ?>"><td><strong><?= e($row['level']) ?></strong></td><td><?= e($row['speed']) ?> km/jam</td><td><?= e($row['shuttles']) ?></td><td><?= e($row['level_distance']) ?> m</td><td><?= e($row['cumulative_shuttles']) ?></td><td><?= e($row['cumulative_distance']) ?> m</td></tr><?php endforeach; ?></tbody></table></div><p class="bleep-method-note"><strong>Catatan:</strong> Baris merah mengikuti level yang dipilih. VO2max dihitung dari tabel VO2max Lari Multi Tahap berdasarkan level dan shuttle terakhir.</p></section>
</form><?php endif; ?>

<section class="panel bleep-history-panel"><div class="panel-header"><div><p class="eyebrow">RIWAYAT MODUL</p><h2>Hasil Bleep Test Terbaru</h2></div><span class="count-badge"><?= e(count($history)) ?> data</span></div><div class="table-wrap"><table><thead><tr><th>No. Tes</th><th>Atlet</th><th>Cabor</th><th>Tanggal</th><th>Level.Shuttle</th><th>Jarak</th><th>VO2max</th><th>Kategori</th><th>Aksi</th></tr></thead><tbody><?php if (!$history): ?><tr><td colspan="9" class="empty-state">Belum ada hasil Bleep Test.</td></tr><?php endif; ?><?php foreach ($history as $row): ?><tr><td><span class="code-pill"><?= e($row['test_number']) ?></span></td><td><strong><?= e($row['athlete_name']) ?></strong><small class="cell-subtext"><?= e($row['officer_name']) ?></small></td><td><?= e($row['sport']) ?></td><td><?= e(format_date($row['test_date'])) ?></td><td><?= e($row['level']) ?>.<?= e($row['shuttle']) ?></td><td><?= e($row['distance_m']) ?> m</td><td><strong><?= e(format_number_id($row['vo2max'], 1)) ?></strong></td><td><?= e($row['category'] ?: '-') ?></td><td><div class="action-row"><?php if (!$crudLocked || (Auth::user()['role'] ?? '') === 'superadmin'): ?><a class="btn btn-small btn-light" href="<?= e(base_url('bleep-test.php?edit=' . $row['id'])) ?>">Edit</a><?php endif; ?><?php if ((Auth::user()['role'] ?? '') === 'superadmin'): ?><form method="post" data-confirm="Hapus permanen hasil Bleep Test <?= e($row['athlete_name']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($row['id']) ?>"><button class="btn btn-small btn-danger" type="submit">Hapus</button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div></section>
