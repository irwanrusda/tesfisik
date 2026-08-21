<div class="page-heading">
    <div><p class="eyebrow">ARSIP PENGUKURAN</p><h1>Laporan Tes Fisik</h1><p>Cari, periksa, dan cetak hasil tes atlet.</p></div>
    <?php if (in_array(Auth::user()['role'] ?? '', ['superadmin', 'input'], true)): ?><a class="btn btn-primary" href="<?= e(base_url('test-create.php')) ?>">＋ Input Tes Baru</a><?php endif; ?>
</div>
<section class="panel filter-panel">
    <form method="get" class="filter-grid">
        <label class="field"><span>Cari atlet / no. tes</span><input name="q" value="<?= e($q) ?>" placeholder="Ketik kata kunci"></label>
        <label class="field"><span>Cabang olahraga</span><select name="sport"><option value="">Semua cabang</option><?php foreach ($sports as $item): ?><option value="<?= e($item) ?>" <?= $sport === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Dari tanggal</span><input type="date" name="from" value="<?= e($from) ?>"></label>
        <label class="field"><span>Sampai tanggal</span><input type="date" name="to" value="<?= e($to) ?>"></label>
        <div class="filter-actions"><button class="btn btn-primary" type="submit">Terapkan</button><a class="btn btn-light" href="<?= e(base_url('reports.php')) ?>">Reset</a></div>
    </form>
</section>
<section class="panel">
    <div class="panel-header"><div><p class="eyebrow">HASIL PENCARIAN</p><h2>Data Tes Atlet</h2></div><span class="count-badge"><?= e(count($tests)) ?> data</span></div>
    <div class="table-wrap"><table><thead><tr><th>No. Tes</th><th>Atlet</th><th>Cabang</th><th>L/P</th><th>Tanggal Tes</th><th>IMT</th><th>Aksi</th></tr></thead><tbody>
    <?php if (!$tests): ?><tr><td colspan="7" class="empty-state">Tidak ada data yang sesuai.</td></tr><?php endif; ?>
    <?php foreach ($tests as $item): ?><tr>
        <td><span class="code-pill"><?= e($item['test_number']) ?></span></td><td><strong><?= e($item['athlete_name']) ?></strong><small class="cell-subtext"><?= e(calculate_age($item['birth_date'], $item['test_date'])) ?> tahun · Input: <?= e($item['creator_name']) ?></small></td><td><?= e($item['sport']) ?></td><td><?= e($item['gender']) ?></td><td><?= e(format_date($item['test_date'])) ?></td><td><?= e($item['bmi']) ?></td>
        <td><div class="action-row"><a class="btn btn-small btn-light" href="<?= e(base_url('test-view.php?id=' . $item['id'])) ?>">Detail</a><a class="btn btn-small btn-outline" target="_blank" href="<?= e(base_url('test-print.php?id=' . $item['id'])) ?>">Cetak</a><?php if ((Auth::user()['role'] ?? '') === 'superadmin'): ?><form method="post" data-confirm="Hapus permanen data tes <?= e($item['athlete_name']) ?> beserta hasil dan foto dokumentasinya?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button class="btn btn-small btn-danger" type="submit">Hapus</button></form><?php endif; ?></div></td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
</section>
