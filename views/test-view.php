<div class="page-heading">
    <div><p class="eyebrow">DETAIL PENGUKURAN</p><h1><?= e($test['athlete_name']) ?></h1><p><?= e($test['test_number']) ?> · <?= e($test['sport']) ?></p></div>
    <div class="button-row"><?php if ((Auth::user()['role'] ?? '') === 'input'): ?><a class="btn btn-light" href="<?= e(base_url('test-edit.php?id=' . $test['id'])) ?>">Edit Data</a><?php endif; ?><a class="btn btn-primary" target="_blank" href="<?= e(base_url('test-print.php?id=' . $test['id'])) ?>">Cetak Blanko</a></div>
</div>
<section class="detail-hero">
    <div class="detail-person"><div class="person-monogram"><?= e(strtoupper(substr($test['athlete_name'], 0, 1))) ?></div><div><span>ATLET</span><h2><?= e($test['athlete_name']) ?></h2><p><?= e($test['birth_place']) ?>, <?= e(format_date($test['birth_date'])) ?> · <?= e(calculate_age($test['birth_date'], $test['test_date'])) ?> tahun</p></div></div>
    <div class="detail-metrics"><div><span>Tinggi</span><strong><?= e($test['height_cm']) ?><small> cm</small></strong></div><div><span>Berat</span><strong><?= e($test['weight_kg']) ?><small> kg</small></strong></div><div><span>IMT</span><strong><?= e($test['bmi']) ?></strong></div></div>
</section>
<?php if ($photos): ?>
<section class="panel documentation-panel">
    <div class="panel-header documentation-header"><div><p class="eyebrow">DOKUMENTASI</p><h2>Foto Kegiatan Tes</h2><p>Rekaman visual pelaksanaan pengukuran fisik atlet.</p></div><span class="documentation-count"><i class="fa-solid fa-images"></i><?= e(count($photos)) ?> foto</span></div>
    <div class="documentation-grid">
        <?php foreach ($photos as $photo): ?>
            <a href="<?= e(signed_photo_url((int) $photo['id'])) ?>" target="_blank" class="documentation-card">
                <img src="<?= e(signed_photo_url((int) $photo['id'])) ?>" alt="<?= e($photo['original_name']) ?>" loading="lazy">
                <span><strong><?= e($photo['original_name']) ?></strong><small><i class="fa-solid fa-expand"></i> Buka foto · <?= e(round((int) $photo['file_size'] / 1024)) ?> KB</small></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<section class="detail-grid">
    <article class="panel panel-wide">
        <div class="panel-header"><div><p class="eyebrow">KONDISI FISIK</p><h2>Hasil Pengukuran</h2></div></div>
        <div class="result-cards">
        <?php foreach (physical_test_items() as $code => $item): $result = $results[$code] ?? []; ?>
            <div class="result-card"><div><span><?= e($item['component']) ?></span><strong><?= e($item['method']) ?></strong><small><?= e($item['detail']) ?></small></div><div class="result-score"><strong><?= e($result['result_value'] ?? '-') ?></strong><small><?= e($item['unit']) ?></small></div><div><span class="category-tag"><?= e($result['category'] ?? 'Belum dinilai') ?></span><small><?= e($result['examiner_notes'] ?? '') ?></small></div></div>
        <?php endforeach; ?>
        </div>
    </article>
    <aside class="panel info-panel">
        <p class="eyebrow">INFORMASI TES</p><h2>Pelaksanaan</h2>
        <dl><dt>Tanggal Tes</dt><dd><?= e(format_date($test['test_date'])) ?></dd><dt>Tempat</dt><dd><?= e($test['test_place']) ?></dd><dt>Jenis Kelamin</dt><dd><?= $test['gender'] === 'L' ? 'Laki-Laki' : 'Perempuan' ?></dd><dt>Petugas Input</dt><dd><?= e($test['creator_name']) ?></dd><dt>Catatan</dt><dd><?= e($test['notes'] ?: '-') ?></dd></dl>
    </aside>
</section>
