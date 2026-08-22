<div class="page-heading">
    <div><p class="eyebrow">JEJAK AKTIVITAS</p><h1>Audit Log Pengisian</h1><p>Periksa siapa yang membuat, mengubah, atau menghapus data tes atlet.</p></div>
</div>
<section class="stats-grid audit-stats">
    <article class="stat-card accent-red"><span>Total Aktivitas</span><strong><?= e((int) ($summary['total'] ?? 0)) ?></strong><small>Maksimal 500 ditampilkan</small></article>
    <article class="stat-card accent-green"><span>Data Dibuat</span><strong><?= e((int) ($summary['created'] ?? 0)) ?></strong><small>Aksi input</small></article>
    <article class="stat-card accent-blue"><span>Data Diubah</span><strong><?= e((int) ($summary['updated'] ?? 0)) ?></strong><small>Aksi edit</small></article>
    <article class="stat-card accent-gold"><span>Data Dihapus</span><strong><?= e((int) ($summary['deleted'] ?? 0)) ?></strong><small>Aksi permanen</small></article>
</section>
<section class="panel filter-panel">
    <form method="get" class="filter-grid audit-filter">
        <label class="field"><span>Cari petugas / atlet / nomor</span><input name="q" value="<?= e($q) ?>" placeholder="Ketik kata kunci"></label>
        <label class="field"><span>Modul</span><select name="module"><option value="">Semua modul</option><option value="tes_fisik" <?= $module === 'tes_fisik' ? 'selected' : '' ?>>Tes Fisik</option><option value="tes_fisik_pos" <?= $module === 'tes_fisik_pos' ? 'selected' : '' ?>>Pos Tes Fisik</option><option value="bleep_test" <?= $module === 'bleep_test' ? 'selected' : '' ?>>Bleep Test</option><option value="master_data" <?= $module === 'master_data' ? 'selected' : '' ?>>Master Data</option></select></label>
        <label class="field"><span>Aksi</span><select name="action"><option value="">Semua aksi</option><option value="create" <?= $action === 'create' ? 'selected' : '' ?>>Input</option><option value="update" <?= $action === 'update' ? 'selected' : '' ?>>Edit</option><option value="delete" <?= $action === 'delete' ? 'selected' : '' ?>>Hapus</option></select></label>
        <label class="field"><span>Dari tanggal</span><input type="date" name="from" value="<?= e($from) ?>"></label>
        <label class="field"><span>Sampai tanggal</span><input type="date" name="to" value="<?= e($to) ?>"></label>
        <div class="filter-actions"><button class="btn btn-primary" type="submit">Terapkan</button><a class="btn btn-light" href="<?= e(base_url('audit-logs.php')) ?>">Reset</a></div>
    </form>
</section>
<section class="panel">
    <div class="panel-header"><div><p class="eyebrow">RIWAYAT AUDIT</p><h2>Aktivitas Data Tes</h2></div><span class="count-badge"><?= e(count($logs)) ?> aktivitas</span></div>
    <div class="table-wrap"><table class="audit-table"><thead><tr><th>Waktu</th><th>Petugas</th><th>Aksi</th><th>Modul</th><th>Atlet</th><th>No. Data</th><th>Detail</th><th>IP</th></tr></thead><tbody>
    <?php if (!$logs): ?><tr><td colspan="8" class="empty-state">Tidak ada aktivitas yang sesuai.</td></tr><?php endif; ?>
    <?php $moduleLabels = ['tes_fisik' => 'Tes Fisik', 'tes_fisik_pos' => 'Pos Tes Fisik', 'bleep_test' => 'Bleep Test', 'master_data' => 'Master Data']; ?>
    <?php foreach ($logs as $log): $details = json_decode((string) ($log['details'] ?? ''), true) ?: []; ?><tr><td><?= e(format_date($log['created_at'], 'd-m-Y H:i:s')) ?></td><td><strong><?= e($log['user_name']) ?></strong><small class="cell-subtext"><?= e($log['username']) ?> · <?= e($log['user_role']) ?></small></td><td><span class="audit-action audit-<?= e($log['action']) ?>"><?= e(['create' => 'Input', 'update' => 'Edit', 'delete' => 'Hapus'][$log['action']] ?? $log['action']) ?></span></td><td><?= e($moduleLabels[$log['module']] ?? $log['module']) ?></td><td><strong><?= e($log['athlete_name']) ?></strong><small class="cell-subtext"><?= e($log['sport'] ?: '-') ?></small></td><td><span class="code-pill"><?= e($log['record_number'] ?: '-') ?></span></td><td class="wrap-cell"><?php foreach ($details as $key => $value): ?><span class="audit-detail"><b><?= e(ucwords(str_replace('_', ' ', (string) $key))) ?>:</b> <?= e($value) ?></span><?php endforeach; ?></td><td><?= e($log['ip_address'] ?: '-') ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
