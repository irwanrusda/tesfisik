<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireAnyRole(['superadmin', 'panitia', 'input']);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::connection();
$statement = $pdo->prepare('SELECT athlete_tests.*, users.name AS creator_name FROM athlete_tests JOIN users ON users.id = athlete_tests.created_by WHERE athlete_tests.id = ?');
$statement->execute([$id]);
$test = $statement->fetch();
if (!$test) { http_response_code(404); exit('Data tes tidak ditemukan.'); }
$resultStatement = $pdo->prepare('SELECT * FROM test_results WHERE athlete_test_id = ?');
$resultStatement->execute([$id]);
$results = [];
foreach ($resultStatement->fetchAll() as $item) $results[$item['test_code']] = $item;
$groups = [
    ['component' => 'KEKUATAN', 'codes' => ['sit_up', 'push_up', 'pull_up']],
    ['component' => 'POWER (Daya Ledak)', 'codes' => ['medicine_ball', 'vertical_jump']],
    ['component' => 'KECEPATAN', 'codes' => ['sprint_30m']],
    ['component' => 'KELINCAHAN', 'codes' => ['illinois']],
    ['component' => 'FLEKSIBILITAS', 'codes' => ['sit_reach']],
    ['component' => 'DAYA TAHAN UMUM', 'codes' => ['bleep_test']],
];
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Blanko <?= e($test['athlete_name']) ?></title><link rel="icon" type="image/svg+xml" href="https://konisumbar.org/assets/img/logo_no_text.svg"><link rel="stylesheet" href="<?= e(base_url('assets/css/print.css')) ?>"></head><body>
<div class="print-toolbar"><button onclick="window.print()">Cetak / Simpan PDF</button><button onclick="window.close()">Tutup</button></div>
<main class="print-sheet">
    <header class="print-header"><div class="print-logo"><img src="https://konisumbar.org/assets/img/logo_no_text.svg" alt="Logo KONI Sumatera Barat"></div><div><h1>BLANKO TES FISIK ATLET SUMBAR</h1><h2>TAHUN 2026</h2></div><div class="test-number"><?= e($test['test_number']) ?></div></header>
    <section><h3>I. DATA DIRI</h3><table class="identity-table"><tr><td>Nama</td><td>: <?= e($test['athlete_name']) ?></td><td>Cabang Olahraga</td><td>: <?= e($test['sport']) ?></td></tr><tr><td>Tempat/Tgl. Lahir (Usia)</td><td>: <?= e($test['birth_place']) ?>, <?= e(format_date($test['birth_date'])) ?> (<?= e(calculate_age($test['birth_date'], $test['test_date'])) ?> Tahun)</td><td>Jenis Kelamin</td><td>: <?= $test['gender'] === 'L' ? 'Laki-Laki' : 'Perempuan' ?></td></tr><tr><td>Tinggi Badan</td><td>: <?= e($test['height_cm']) ?> cm</td><td>Berat Badan</td><td>: <?= e($test['weight_kg']) ?> kg</td></tr><tr><td>IMT</td><td>: <?= e($test['bmi']) ?></td><td>Tanggal Tes</td><td>: <?= e(format_date($test['test_date'])) ?></td></tr></table></section>
    <section><h3>II. HASIL TES KONDISI FISIK</h3><table class="test-table"><thead><tr><th>No.</th><th>Komponen</th><th>Teknik Pengukuran</th><th>Skor</th><th>Kategori</th><th>Ket/Paraf</th></tr></thead><tbody>
    <?php foreach ($groups as $number => $group): ?>
        <?php foreach ($group['codes'] as $index => $code): $definition = physical_test_items()[$code]; $result = $results[$code] ?? []; ?>
        <tr><?php if ($index === 0): ?><td rowspan="<?= e(count($group['codes'])) ?>"><?= e($number + 1) ?></td><?php endif; ?><td><?= $index === 0 ? '<strong>' . e($group['component']) . '</strong><br>' : '' ?><?= e($definition['detail']) ?></td><td><?= e($definition['method']) ?></td><td><?= e($result['result_value'] ?? '') ?> <?= e($definition['unit']) ?></td><td><?= e($result['category'] ?? '') ?></td><td><?= e($result['examiner_notes'] ?? '') ?></td></tr>
        <?php endforeach; ?>
    <?php endforeach; ?>
    </tbody></table></section>
    <?php if ($test['notes']): ?><p class="print-notes"><strong>Catatan:</strong> <?= e($test['notes']) ?></p><?php endif; ?>
    <footer class="signature"><div></div><div><p><?= e($test['test_place']) ?>, <?= e(format_date($test['test_date'], 'd F Y')) ?></p><strong>PANITIA TES,</strong><span></span><p>(........................................)</p></div></footer>
</main></body></html>
