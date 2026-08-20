<div class="page-heading">
    <div><p class="eyebrow">INSIGHT HASIL PENGUKURAN</p><h1>Analisis Tes Fisik</h1><p>Evaluasi cakupan, kualitas data, dan kondisi fisik berdasarkan hasil yang telah diinput.</p></div>
    <a class="btn btn-light" href="<?= e(base_url('reports.php')) ?>">Buka Laporan</a>
</div>

<section class="summary-kpis analysis-kpis">
    <article class="metric-card metric-primary"><span>Total Formulir Tes</span><strong><?= e($testCount) ?></strong><small>Seluruh periode</small></article>
    <article class="metric-card"><span>Atlet Sudah Tes</span><strong><?= e($testedAthletes) ?></strong><small>Atlet unik</small></article>
    <article class="metric-card"><span>Tes Berulang</span><strong><?= e($retestedAthletes) ?></strong><small>Atlet dengan lebih dari 1 tes</small></article>
    <article class="metric-card <?= $completeness >= 90 ? 'metric-success' : 'metric-warning' ?>"><span>Kelengkapan Item</span><strong><?= e($completeness) ?>%</strong><small>Skor yang sudah terisi</small></article>
</section>

<section class="analytics-grid analysis-top-grid">
    <article class="panel">
        <div class="panel-header"><div><p class="eyebrow">KATEGORI KONDISI</p><h2>Distribusi Penilaian</h2></div></div>
        <div class="distribution-list">
            <?php if (!$categoryRows): ?><p class="empty-state">Belum ada skor dengan kategori penilaian.</p><?php endif; ?>
            <?php foreach ($categoryRows as $row): $rate = $categoryTotal ? round((int) $row['total'] / $categoryTotal * 100, 1) : 0; ?><div class="distribution-row"><span><?= e($row['label']) ?></span><div class="progress-track"><i style="width:<?= e($rate) ?>%"></i></div><strong><?= e((int) $row['total']) ?></strong><small><?= e($rate) ?>%</small></div><?php endforeach; ?>
        </div>
    </article>
    <article class="panel">
        <div class="panel-header"><div><p class="eyebrow">KOMPOSISI TUBUH</p><h2>Distribusi IMT</h2></div></div>
        <div class="bmi-grid">
            <?php if (!$bmiRows): ?><p class="empty-state">Belum ada data IMT.</p><?php endif; ?>
            <?php foreach ($bmiRows as $row): $rate = $bmiTotal ? round((int) $row['total'] / $bmiTotal * 100, 1) : 0; ?><div class="bmi-item"><span><?= e($row['label']) ?></span><strong><?= e((int) $row['total']) ?></strong><small><?= e($rate) ?>%</small></div><?php endforeach; ?>
        </div>
        <p class="analysis-note">IMT adalah indikator awal. Interpretasi atlet perlu mempertimbangkan massa otot dan karakteristik cabor.</p>
    </article>
</section>

<section class="panel analysis-section">
    <div class="panel-header"><div><p class="eyebrow">HASIL PER ITEM</p><h2>Rata-Rata Pengukuran</h2></div><span class="count-badge">Berdasarkan skor terisi</span></div>
    <div class="test-average-grid">
        <?php foreach (physical_test_items() as $code => $definition): $row = $averages[$code] ?? null; ?>
            <article class="average-card"><span><?= e($definition['component']) ?></span><h3><?= e($definition['method']) ?></h3><div><strong><?= e($row['average'] ?? '-') ?></strong><small><?= e($definition['unit']) ?></small></div><p><?= $row ? 'Rentang ' . e($row['minimum']) . ' - ' . e($row['maximum']) . ' · ' . e($row['samples']) . ' sampel' : 'Belum ada skor' ?></p></article>
        <?php endforeach; ?>
    </div>
</section>

<section class="analytics-grid recommendation-grid">
    <article class="panel">
        <div class="panel-header"><div><p class="eyebrow">CAKUPAN CABOR</p><h2>Keikutsertaan Tertinggi</h2></div></div>
        <div class="coverage-list">
        <?php foreach (array_slice($coverageRows, 0, 10) as $row): $rate = $row['athletes'] ? round((int) $row['tested'] / (int) $row['athletes'] * 100, 1) : 0; ?><div><span><strong><?= e($row['name']) ?></strong><small><?= e($row['tested']) ?>/<?= e($row['athletes']) ?> atlet</small></span><div class="progress-track"><i style="width:<?= e($rate) ?>%"></i></div><b><?= e($rate) ?>%</b></div><?php endforeach; ?>
        </div>
    </article>
    <article class="panel recommendation-panel">
        <div class="panel-header"><div><p class="eyebrow">REKOMENDASI OTOMATIS</p><h2>Tindak Lanjut</h2></div></div>
        <div class="recommendation-list"><?php foreach ($recommendations as $index => $item): ?><div><span><?= e($index + 1) ?></span><p><strong><?= e($item['title']) ?></strong><?= e($item['text']) ?></p></div><?php endforeach; ?></div>
    </article>
</section>

<section class="panel analysis-roadmap">
    <div class="panel-header"><div><p class="eyebrow">PENGEMBANGAN ANALISIS</p><h2>Analisis yang Disarankan Berikutnya</h2></div></div>
    <div class="roadmap-grid"><div><strong>Tren Per Atlet</strong><p>Perubahan hasil antarperiode untuk melihat progres latihan.</p></div><div><strong>Norma Usia & Gender</strong><p>Kategori otomatis berdasarkan tabel norma resmi setiap item tes.</p></div><div><strong>Profil Kebutuhan Cabor</strong><p>Bobot komponen fisik berbeda sesuai karakter tiap cabang olahraga.</p></div><div><strong>Atlet Prioritas Intervensi</strong><p>Peringkat atlet dengan hasil rendah, data tidak lengkap, atau penurunan performa.</p></div></div>
</section>
