<div class="page-heading">
    <div><p class="eyebrow">SUPERADMIN</p><h1>Konfigurasi Kondisi Tes</h1><p>Tetapkan durasi tes dan metode Pull Up perempuan yang digunakan petugas pos.</p></div>
</div>
<form method="post" class="settings-layout">
    <?= csrf_field() ?>
    <section class="panel settings-panel">
        <div class="panel-header"><div><p class="eyebrow">KEKUATAN</p><h2>Sit Up dan Push Up</h2></div></div>
        <div class="settings-fields">
            <label class="field"><span>Durasi Sit Up (detik)</span><input type="number" name="sit_up_duration_seconds" min="1" max="600" value="<?= e($conditions['sit_up_duration_seconds']) ?>" required><small class="field-help">Petugas menghitung jumlah Sit Up dalam rentang waktu ini. Default 60 detik.</small></label>
            <label class="field"><span>Durasi Push Up (detik)</span><input type="number" name="push_up_duration_seconds" min="1" max="600" value="<?= e($conditions['push_up_duration_seconds']) ?>" required><small class="field-help">Petugas menghitung jumlah Push Up dalam rentang waktu ini. Default 60 detik.</small></label>
        </div>
    </section>
    <section class="panel settings-panel">
        <div class="panel-header"><div><p class="eyebrow">PULL UP</p><h2>Kondisi Berdasarkan Jenis Kelamin</h2></div></div>
        <div class="settings-summary"><div><span>Laki-Laki</span><strong>Jumlah Angkat Badan</strong><small>Satuan selalu kali.</small></div><label class="field"><span>Metode Perempuan</span><select name="female_pull_up_mode"><option value="repetitions" <?= $conditions['female_pull_up_mode'] === 'repetitions' ? 'selected' : '' ?>>Jumlah Angkat Badan (kali)</option><option value="hold" <?= $conditions['female_pull_up_mode'] === 'hold' ? 'selected' : '' ?>>Menahan Badan (detik)</option></select><small class="field-help">Default menggunakan jumlah angkat badan. Pilihan ini mengubah label dan satuan pada pos Pull Up untuk atlet perempuan.</small></label></div>
    </section>
    <div class="form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan Konfigurasi</button></div>
</form>
