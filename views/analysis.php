<div data-auto-refresh="30000" data-refresh-page="analysis">
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

<section class="panel vo2max-analysis">
    <div class="panel-header"><div><p class="eyebrow">KAPASITAS AEROBIK</p><h2>Analisis VO2max Bleep Test</h2></div><span class="count-badge"><?= e((int) ($vo2maxSummary['samples'] ?? 0)) ?> sampel</span></div>
    <div class="vo2max-summary"><div><span>Rata-Rata</span><strong><?= e($vo2maxSummary['average'] ?? '-') ?></strong><small>ml/kg/menit</small></div><div><span>Terendah</span><strong><?= e($vo2maxSummary['minimum'] ?? '-') ?></strong><small>ml/kg/menit</small></div><div><span>Tertinggi</span><strong><?= e($vo2maxSummary['maximum'] ?? '-') ?></strong><small>ml/kg/menit</small></div><p>VO2max dihitung otomatis dari level dan shuttle terakhir Bleep Test menggunakan tabel VO2max Lari Multi Tahap.</p></div>
</section>

<section class="summary-kpis bleep-analysis-kpis">
    <article class="metric-card metric-primary"><span>Total Bleep Test</span><strong><?= e((int) ($bleepOverview['total_tests'] ?? 0)) ?></strong><small>Seluruh pelaksanaan</small></article>
    <article class="metric-card"><span>Atlet Terukur</span><strong><?= e((int) ($bleepOverview['athletes'] ?? 0)) ?></strong><small>Atlet unik</small></article>
    <article class="metric-card"><span>Rata-Rata Level</span><strong><?= e($bleepOverview['average_level'] ?? '-') ?></strong><small>Level terakhir</small></article>
    <article class="metric-card"><span>Rata-Rata Jarak</span><strong><?= e($bleepOverview['average_distance'] ?? '-') ?></strong><small>Meter</small></article>
</section>

<section class="analytics-grid bleep-analysis-grid">
    <article class="panel">
        <div class="panel-header"><div><p class="eyebrow">KATEGORI VO2MAX</p><h2>Distribusi Hasil Bleep Test</h2></div></div>
        <div class="distribution-list"><?php if (!$bleepCategoryRows): ?><p class="empty-state">Belum ada hasil Bleep Test.</p><?php endif; ?><?php foreach ($bleepCategoryRows as $row): $rate = $bleepCategoryTotal ? round((int) $row['total'] / $bleepCategoryTotal * 100, 1) : 0; ?><div class="distribution-row"><span><?= e($row['label']) ?></span><div class="progress-track"><i style="width:<?= e($rate) ?>%"></i></div><strong><?= e((int) $row['total']) ?></strong><small><?= e($rate) ?>%</small></div><?php endforeach; ?></div>
    </article>
    <article class="panel">
        <div class="panel-header"><div><p class="eyebrow">PERFORMA CABOR</p><h2>Rata-Rata VO2max Tertinggi</h2></div></div>
        <div class="bleep-sport-analysis"><?php if (!$bleepSportAnalysis): ?><p class="empty-state">Belum ada data per cabor.</p><?php endif; ?><?php foreach ($bleepSportAnalysis as $index => $row): ?><div><span class="sport-rank"><?= e($index + 1) ?></span><p><strong><?= e($row['sport']) ?></strong><small><?= e($row['athletes']) ?> atlet · <?= e($row['tests']) ?> tes · jarak rata-rata <?= e($row['average_distance']) ?> m</small></p><b><?= e($row['average_vo2max']) ?><small> ml/kg/menit</small></b></div><?php endforeach; ?></div>
    </article>
</section>

<section class="panel bleep-top-panel">
    <div class="panel-header"><div><p class="eyebrow">PERFORMA INDIVIDU</p><h2>VO2max Atlet Tertinggi</h2></div><span class="count-badge">10 hasil teratas</span></div>
    <div class="table-wrap"><table><thead><tr><th>Peringkat</th><th>Atlet</th><th>Cabor</th><th>Level.Shuttle</th><th>Jarak</th><th>VO2max</th><th>Tanggal</th></tr></thead><tbody><?php if (!$bleepTopAthletes): ?><tr><td colspan="7" class="empty-state">Belum ada hasil Bleep Test.</td></tr><?php endif; ?><?php foreach ($bleepTopAthletes as $index => $row): ?><tr><td><span class="sport-rank"><?= e($index + 1) ?></span></td><td><strong><?= e($row['athlete_name']) ?></strong></td><td><?= e($row['sport']) ?></td><td><?= e($row['level']) ?>.<?= e($row['shuttle']) ?></td><td><?= e($row['distance_m']) ?> m</td><td><strong><?= e($row['vo2max']) ?></strong></td><td><?= e(format_date($row['test_date'])) ?></td></tr><?php endforeach; ?></tbody></table></div>
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

<section class="panel ranking-panel">
    <div class="panel-header ranking-header"><div><p class="eyebrow">URUTAN NILAI TES</p><h2>Peringkat Atlet per Komponen</h2><p>Pilih kategori tes untuk melihat urutan hasil atlet.</p></div><form method="get" class="ranking-filter" data-ranking-filter><label class="field"><span>Komponen Tes</span><select name="ranking_test" data-ranking-select><?php foreach ($testItems as $code => $definition): ?><option value="<?= e($code) ?>" <?= $rankingCode === $code ? 'selected' : '' ?>><?= e($definition['method'] . ' · ' . $definition['component']) ?></option><?php endforeach; ?></select></label></form></div>
    <div data-ranking-results>
        <div class="ranking-summary"><div><span>Komponen</span><strong><?= e($rankingDefinition['method']) ?></strong><small><?= e($rankingDefinition['component']) ?></small></div><div><span>Satuan</span><strong><?= e($rankingDefinition['unit']) ?></strong><small><?= $rankingDirection === 'ASC' ? 'Nilai terendah terbaik' : 'Nilai tertinggi terbaik' ?></small></div><div><span>Jumlah Hasil</span><strong><?= e(count($rankingRows)) ?></strong><small>Maksimal 100 hasil</small></div></div>
        <div class="table-wrap"><table class="ranking-table"><thead><tr><th>Peringkat</th><th>Atlet</th><th>Cabor</th><th>L/P</th><th>Nilai</th><th>Kategori</th><th>Tanggal</th><th>Petugas</th></tr></thead><tbody>
        <?php if (!$rankingRows): ?><tr><td colspan="8" class="empty-state">Belum ada nilai untuk komponen ini.</td></tr><?php endif; ?>
        <?php foreach ($rankingRows as $index => $row): ?><tr><td><span class="ranking-number <?= $index < 3 ? 'top' : '' ?>"><?= e($index + 1) ?></span></td><td><strong><?= e($row['athlete_name']) ?></strong></td><td><?= e($row['sport']) ?></td><td><?= e($row['gender']) ?></td><td><strong class="ranking-score"><?= e($row['result_value']) ?></strong> <small><?= e($row['unit']) ?></small></td><td><?= e($row['category'] ?: '-') ?></td><td><?= e(format_date($row['test_date'])) ?></td><td><?= e($row['officer_name']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </div>
</section>

<?php /* Panel data ganda disembunyikan dari halaman analisis sesuai permintaan operasional.
       Logika backend duplicate resolution di analysis.php tetap tersedia bila perlu diaktifkan lagi. */ ?>
</div>

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
