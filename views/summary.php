<?php
$total = (int) ($overview['total'] ?? 0);
$tested = (int) ($overview['tested'] ?? 0);
$participation = $total > 0 ? round($tested / $total * 100, 1) : 0;
?>
<div data-auto-refresh="30000" data-refresh-page="summary">
<div class="page-heading">
    <div><p class="eyebrow">RINGKASAN MASTER DATA</p><h1>Summary Atlet</h1><p>Komposisi atlet aktif dan progres keikutsertaan tes fisik.</p></div>
    <?php if ((Auth::user()['role'] ?? '') === 'superadmin'): ?><a class="btn btn-primary" href="<?= e(base_url('test-create.php')) ?>">＋ Daftarkan Atlet Tes</a><?php endif; ?>
</div>

<section class="summary-kpis">
    <article class="metric-card metric-primary"><span>Total Atlet</span><strong><?= e($total) ?></strong><small><?= e((int) $overview['sports']) ?> cabang olahraga</small></article>
    <article class="metric-card"><span>Laki-Laki</span><strong><?= e((int) $overview['male']) ?></strong><small><?= e($total ? round((int) $overview['male'] / $total * 100, 1) : 0) ?>% dari atlet</small></article>
    <article class="metric-card"><span>Perempuan</span><strong><?= e((int) $overview['female']) ?></strong><small><?= e($total ? round((int) $overview['female'] / $total * 100, 1) : 0) ?>% dari atlet</small></article>
    <article class="metric-card metric-success"><span>Sudah Ikut Tes</span><strong><?= e($tested) ?></strong><small><?= e($participation) ?>% cakupan</small></article>
    <article class="metric-card metric-warning"><span>Belum Ikut Tes</span><strong><?= e((int) $overview['not_tested']) ?></strong><small>Perlu dijadwalkan</small></article>
</section>

<section class="panel bleep-summary-panel">
    <div class="panel-header"><div><p class="eyebrow">BLEEP TEST VO2MAX</p><h2>Summary Daya Tahan Aerobik</h2></div><a href="<?= e(base_url('analysis.php')) ?>">Buka analisis →</a></div>
    <div class="bleep-summary-kpis">
        <div><span>Total Pelaksanaan</span><strong><?= e((int) ($bleepOverview['total_tests'] ?? 0)) ?></strong><small>Seluruh tes tersimpan</small></div>
        <div><span>Atlet Sudah Tes</span><strong><?= e((int) ($bleepOverview['tested'] ?? 0)) ?></strong><small>Dari <?= e($total) ?> atlet aktif</small></div>
        <div><span>Belum Bleep Test</span><strong><?= e((int) ($bleepOverview['not_tested'] ?? 0)) ?></strong><small>Perlu dijadwalkan</small></div>
        <div><span>Rata-Rata VO2max</span><strong><?= e($bleepOverview['average_vo2max'] ?? '-') ?></strong><small>ml/kg/menit</small></div>
        <div><span>VO2max Tertinggi</span><strong><?= e($bleepOverview['highest_vo2max'] ?? '-') ?></strong><small>ml/kg/menit</small></div>
    </div>
    <div class="bleep-summary-content">
        <div class="progress-list">
            <p class="summary-subtitle">CAKUPAN BERDASARKAN STATUS</p>
            <?php foreach ($bleepStatusRows as $row): $rowTotal = (int) $row['total']; $rowTested = (int) $row['tested']; $rate = $rowTotal ? round($rowTested / $rowTotal * 100, 1) : 0; ?>
                <div class="progress-row"><div class="progress-copy"><strong><?= e($row['label']) ?></strong><span><?= e($rowTested) ?> dari <?= e($rowTotal) ?> atlet</span></div><div class="progress-track"><span style="width:<?= e($rate) ?>%"></span></div><b><?= e($rate) ?>%</b></div>
            <?php endforeach; ?>
        </div>
        <div class="bleep-sport-summary">
            <p class="summary-subtitle">CAKUPAN CABOR TERATAS</p>
            <?php foreach (array_slice($bleepSportRows, 0, 8) as $row): $rate = $row['athletes'] ? round((int) $row['tested'] / (int) $row['athletes'] * 100, 1) : 0; ?>
                <div><span><strong><?= e($row['name']) ?></strong><small><?= e($row['tested']) ?>/<?= e($row['athletes']) ?> atlet · VO2max <?= e($row['average_vo2max'] ?? '-') ?></small></span><b><?= e($rate) ?>%</b></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="analytics-grid">
    <article class="panel">
        <div class="panel-header"><div><p class="eyebrow">STATUS PEMBINAAN</p><h2>Komposisi & Cakupan Tes</h2></div></div>
        <div class="progress-list">
            <?php foreach ($statusRows as $row): $rowTotal = (int) $row['total']; $rowTested = (int) $row['tested']; $rate = $rowTotal ? round($rowTested / $rowTotal * 100, 1) : 0; ?>
                <div class="progress-row">
                    <div class="progress-copy"><strong><?= e($row['label']) ?></strong><span><?= e($rowTested) ?> dari <?= e($rowTotal) ?> sudah tes</span></div>
                    <div class="progress-track"><span style="width:<?= e($rate) ?>%"></span></div><b><?= e($rate) ?>%</b>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="panel participation-panel">
        <div class="panel-header"><div><p class="eyebrow">KEIKUTSERTAAN</p><h2>Cakupan Keseluruhan</h2></div></div>
        <div class="donut-wrap"><div class="donut" style="--value:<?= e($participation) ?>"><div><strong><?= e($participation) ?>%</strong><span>sudah tes</span></div></div><div class="legend-list"><span><i class="legend-tested"></i>Sudah tes <strong><?= e($tested) ?></strong></span><span><i class="legend-pending"></i>Belum tes <strong><?= e((int) $overview['not_tested']) ?></strong></span></div></div>
    </article>
</section>

<section class="panel summary-table-panel">
    <div class="panel-header"><div><p class="eyebrow">PER CABANG OLAHRAGA</p><h2>Sebaran dan Progres Tes</h2></div><span class="count-badge"><?= e(count($sportRows)) ?> cabor</span></div>
    <div class="table-wrap"><table><thead><tr><th>Cabang Olahraga</th><th>Total Atlet</th><th>Laki-Laki</th><th>Perempuan</th><th>Sudah Tes</th><th>Belum Tes</th><th>Cakupan</th></tr></thead><tbody>
    <?php foreach ($sportRows as $row): $sportTotal = (int) $row['total']; $sportTested = (int) $row['tested']; $rate = $sportTotal ? round($sportTested / $sportTotal * 100, 1) : 0; ?><tr><td><strong><?= e($row['name']) ?></strong></td><td><?= e($sportTotal) ?></td><td><?= e((int) $row['male']) ?></td><td><?= e((int) $row['female']) ?></td><td><?= e($sportTested) ?></td><td><?= e($sportTotal - $sportTested) ?></td><td><div class="table-progress"><span style="width:<?= e($rate) ?>%"></span></div><small><?= e($rate) ?>%</small></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>

<section class="panel summary-table-panel station-coverage-panel">
    <div class="panel-header"><div><p class="eyebrow">KELENGKAPAN POS PER CABOR</p><h2>Pos yang Belum Diikuti</h2><p>Setiap pos dinyatakan lengkap jika seluruh atlet aktif pada cabor tersebut sudah memiliki nilai.</p></div><span class="count-badge"><?= e(count($stationCoverageRows)) ?> cabor</span></div>
    <div class="table-wrap"><table class="station-coverage-table"><thead><tr><th>Cabang Olahraga</th><th>Atlet</th><th>Pos Lengkap</th><th>Cakupan</th><th>Belum Diikuti</th><th>Belum Lengkap</th></tr></thead><tbody>
    <?php if (!$stationCoverageRows): ?><tr><td colspan="6" class="empty-state">Belum ada data cabor aktif.</td></tr><?php endif; ?>
    <?php foreach ($stationCoverageRows as $row): ?><tr>
        <td><strong><?= e($row['sport']) ?></strong></td>
        <td><?= e($row['athletes']) ?></td>
        <td><strong><?= e($row['completed']) ?>/<?= e($row['total_stations']) ?></strong></td>
        <td><div class="table-progress"><span style="width:<?= e($row['coverage']) ?>%"></span></div><small><?= e($row['coverage']) ?>%</small></td>
        <td class="station-gap-cell"><?php if ($row['not_started']): ?><?php foreach ($row['not_started'] as $method): ?><span class="station-gap missing"><?= e($method) ?></span><?php endforeach; ?><?php else: ?><span class="station-complete"><i class="fa-solid fa-check"></i> Semua pos sudah diikuti</span><?php endif; ?></td>
        <td class="station-gap-cell"><?php if ($row['incomplete']): ?><?php foreach ($row['incomplete'] as $station): ?><span class="station-gap partial"><?= e($station['method']) ?> <small><?= e($station['tested']) ?>/<?= e($station['total']) ?> atlet</small></span><?php endforeach; ?><?php else: ?><span class="station-complete"><i class="fa-solid fa-check"></i> Tidak ada</span><?php endif; ?></td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
</section>
</div>

<section class="panel summary-table-panel">
    <div class="panel-header"><div><p class="eyebrow">TINDAK LANJUT</p><h2>Atlet Belum Mengikuti Tes</h2></div><span class="count-badge">Maks. 100 data</span></div>
    <div class="table-wrap"><table><thead><tr><th>Nama</th><th>L/P</th><th>Cabang Olahraga</th><th>Status</th></tr></thead><tbody>
    <?php if (!$notTested): ?><tr><td colspan="4" class="empty-state">Seluruh atlet aktif sudah memiliki data tes.</td></tr><?php endif; ?>
    <?php foreach ($notTested as $athlete): ?><tr><td><strong><?= e($athlete['name']) ?></strong></td><td><?= e($athlete['gender']) ?></td><td><?= e($athlete['sport']) ?></td><td><span class="role-badge"><?= e($athlete['development_status'] ?: 'Belum Ditentukan') ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
