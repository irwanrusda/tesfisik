<div class="page-heading dashboard-heading">
    <div>
        <p class="eyebrow">RINGKASAN KEGIATAN</p>
        <h1>Dashboard Tes Fisik</h1>
        <p>Pantau data pengukuran kondisi fisik atlet Sumatera Barat.</p>
    </div>
</div>

<section class="stats-grid">
    <article class="stat-card accent-red"><span>Total Tes</span><strong><?= e($totalTests) ?></strong><small>Seluruh pengukuran</small></article>
    <article class="stat-card accent-blue"><span>Atlet Terdata</span><strong><?= e($totalAthletes) ?></strong><small>Atlet unik</small></article>
    <article class="stat-card accent-gold"><span>Cabang Olahraga</span><strong><?= e($totalSports) ?></strong><small>Cabang terdaftar</small></article>
    <article class="stat-card accent-green"><span>Tes Bulan Ini</span><strong><?= e($thisMonth) ?></strong><small><?= e(date('F Y')) ?></small></article>
</section>

<section class="dashboard-grid">
    <article class="panel panel-wide">
        <div class="panel-header"><div><p class="eyebrow">DATA TERKINI</p><h2>Pengukuran Terbaru</h2></div><a href="<?= e(base_url('reports.php')) ?>">Lihat semua →</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>No. Tes</th><th>Atlet</th><th>Cabang</th><th>Tanggal</th><th>IMT</th><th></th></tr></thead>
                <tbody>
                <?php if (!$recent): ?>
                    <tr><td colspan="6" class="empty-state">Belum ada data tes.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent as $item): ?>
                    <tr>
                        <td><span class="code-pill"><?= e($item['test_number']) ?></span></td>
                        <td><strong><?= e($item['athlete_name']) ?></strong></td>
                        <td><?= e($item['sport']) ?></td>
                        <td><?= e(format_date($item['test_date'])) ?></td>
                        <td><?= e($item['bmi']) ?></td>
                        <td><a class="table-link" href="<?= e(base_url('test-view.php?id=' . $item['id'])) ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <article class="panel sport-panel">
        <div class="panel-header"><div><p class="eyebrow">SEBARAN</p><h2>Cabang Teratas</h2></div></div>
        <?php if (!$sports): ?><p class="empty-state">Data belum tersedia.</p><?php endif; ?>
        <div class="sport-list">
            <?php foreach ($sports as $index => $sport): ?>
                <div class="sport-row"><span class="sport-rank"><?= e($index + 1) ?></span><div><strong><?= e($sport['sport']) ?></strong><small><?= e($sport['total']) ?> data tes</small></div></div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
