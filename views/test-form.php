<?php
$isEdit = $test !== null;
$value = static fn(string $key, mixed $default = '') => $isEdit ? ($test[$key] ?? $default) : old($key, $default);
$resultValue = static function (string $code, string $key) use ($results, $isEdit) {
    if ($isEdit) return $results[$code][$key] ?? '';
    return old('results', [])[$code][$key] ?? '';
};
?>
<div class="page-heading">
    <div><p class="eyebrow"><?= $isEdit ? 'PERBARUI PENGUKURAN' : 'PENGUKURAN BARU' ?></p><h1><?= $isEdit ? 'Edit Data Tes' : 'Input Data Tes Fisik' ?></h1><p>Isi identitas atlet dan hasil setiap item pengukuran.</p></div>
    <?php if ($isEdit): ?><a class="btn btn-light" href="<?= e(base_url('test-view.php?id=' . $test['id'])) ?>">Kembali ke Detail</a><?php endif; ?>
</div>
<form method="post" action="<?= e(base_url($formAction)) ?>" class="test-form" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= e($test['id']) ?>"><?php endif; ?>
    <section class="panel form-section">
        <div class="section-number">01</div><div class="section-title"><p class="eyebrow">DATA DIRI</p><h2>Identitas Atlet</h2></div>
        <div class="form-grid">
            <?php $selectedAthleteId = (int) $value('master_person_id', 0); ?>
            <label class="field field-span-2"><span>Pilih Atlet dari Master Data *</span><div class="searchable-dropdown" data-athlete-dropdown><input type="search" data-athlete-combobox placeholder="Ketik nama atlet atau cabang olahraga" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="athlete-options" required><input type="hidden" name="master_person_id" value="<?= e($selectedAthleteId) ?>" data-athlete-value><div class="searchable-options" id="athlete-options" data-athlete-options role="listbox"><?php foreach ($athletes as $athlete): ?><button type="button" class="searchable-option" data-athlete-option data-id="<?= e($athlete['id']) ?>" data-name="<?= e($athlete['name']) ?>" data-sport="<?= e($athlete['sport']) ?>" data-gender="<?= e($athlete['gender']) ?>" data-status="<?= e($athlete['development_status']) ?>" role="option" <?= $selectedAthleteId === (int) $athlete['id'] ? 'aria-selected="true"' : '' ?>><strong><?= e($athlete['name']) ?></strong><span><?= e($athlete['sport']) ?><?= $athlete['development_status'] ? ' · ' . e($athlete['development_status']) : '' ?></span></button><?php endforeach; ?><p class="searchable-empty" data-athlete-empty>Atlet tidak ditemukan.</p></div></div><small class="field-help">Ketik nama atau cabor, lalu pilih atlet dari hasil yang muncul.</small></label>
            <label class="field"><span>Nama Atlet</span><input name="athlete_name" value="<?= e($value('athlete_name')) ?>" data-athlete-name readonly></label>
            <label class="field"><span>Cabang Olahraga</span><input name="sport" value="<?= e($value('sport')) ?>" data-athlete-sport readonly></label>
            <label class="field"><span>Jenis Kelamin</span><input value="<?= $value('gender') === 'L' ? 'Laki-Laki' : ($value('gender') === 'P' ? 'Perempuan' : '') ?>" data-athlete-gender-label readonly><input type="hidden" name="gender" value="<?= e($value('gender')) ?>" data-athlete-gender></label>
            <label class="field"><span>Status Pembinaan</span><input value="" data-athlete-status readonly></label>
            <label class="field"><span>Tempat Lahir *</span><input name="birth_place" value="<?= e($value('birth_place')) ?>" required></label>
            <label class="field"><span>Tanggal Lahir *</span><input type="date" name="birth_date" value="<?= e($value('birth_date')) ?>" required></label>
            <label class="field"><span>Tinggi Badan (cm) *</span><input type="number" name="height_cm" step="0.01" min="1" value="<?= e($value('height_cm')) ?>" data-height required></label>
            <label class="field"><span>Berat Badan (kg) *</span><input type="number" name="weight_kg" step="0.01" min="1" value="<?= e($value('weight_kg')) ?>" data-weight required></label>
            <label class="field"><span>IMT (otomatis)</span><input value="<?= e($value('bmi')) ?>" data-bmi readonly placeholder="0.00"></label>
            <label class="field"><span>Tanggal Tes *</span><input type="date" name="test_date" value="<?= e($value('test_date', date('Y-m-d'))) ?>" required></label>
            <label class="field"><span>Tempat Tes</span><input name="test_place" value="<?= e($value('test_place', 'Padang')) ?>"></label>
            <label class="field field-span-2"><span>Catatan Umum</span><textarea name="notes" rows="3"><?= e($value('notes')) ?></textarea></label>
        </div>
    </section>
    <section class="panel form-section">
        <div class="section-number">02</div><div class="section-title"><p class="eyebrow">HASIL TES</p><h2>Kondisi Fisik</h2></div>
        <div class="measurement-list">
            <?php foreach (physical_test_items() as $code => $item): ?>
                <div class="measurement-row">
                    <div class="measurement-name"><span><?= e($item['component']) ?></span><strong><?= e($item['method']) ?></strong><small><?= e($item['detail']) ?></small></div>
                    <label class="field compact"><span>Skor (<?= e($item['unit']) ?>)</span><input type="number" step="0.01" min="0" name="results[<?= e($code) ?>][value]" value="<?= e($resultValue($code, 'result_value') ?: $resultValue($code, 'value')) ?>"></label>
                    <label class="field compact"><span>Kategori</span><select name="results[<?= e($code) ?>][category]"><option value="">Pilih kategori</option><?php foreach (['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'] as $category): ?><option value="<?= e($category) ?>" <?= $resultValue($code, 'category') === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></label>
                    <label class="field compact"><span>Ket/Paraf</span><input name="results[<?= e($code) ?>][notes]" value="<?= e($resultValue($code, 'examiner_notes') ?: $resultValue($code, 'notes')) ?>"></label>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel form-section documentation-form-section">
        <div class="section-number">03</div><div class="section-title documentation-title"><div><p class="eyebrow">DOKUMENTASI</p><h2>Foto Kegiatan Tes</h2><p>Ambil foto langsung atau pilih beberapa dokumentasi dari galeri perangkat.</p></div><span class="documentation-title-icon"><i class="fa-solid fa-camera-retro"></i></span></div>
        <label class="photo-upload photo-upload-standard">
            <input type="file" name="documentation_photos[]" accept="image/*" multiple data-photo-input>
            <span class="photo-upload-icon"><i class="fa-solid fa-paperclip"></i></span>
            <strong>Lampirkan Foto Dokumentasi</strong>
            <small>Pilih atau ambil satu maupun beberapa foto dari perangkat. Maksimal 10 foto.</small>
        </label>
        <div class="photo-preview-grid" data-photo-preview></div>
        <?php if ($photos): ?>
            <div class="existing-photos">
                <div class="existing-photos-heading"><div><p class="eyebrow">FOTO TERSIMPAN</p><strong><?= e(count($photos)) ?> dokumentasi</strong></div><small>Centang foto yang ingin dihapus, lalu simpan perubahan.</small></div>
                <div class="photo-grid">
                    <?php foreach ($photos as $photo): ?>
                        <label class="photo-manage-card">
                            <img src="<?= e(signed_photo_url((int) $photo['id'])) ?>" alt="<?= e($photo['original_name']) ?>" loading="lazy">
                            <span><?= e($photo['original_name']) ?><small><?= e(round((int) $photo['file_size'] / 1024)) ?> KB</small></span>
                            <span class="photo-delete"><input type="checkbox" name="delete_photos[]" value="<?= e($photo['id']) ?>"> Hapus saat disimpan</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
    <div class="form-actions"><a class="btn btn-light" href="<?= e(base_url('reports.php')) ?>">Batal</a><button class="btn btn-primary" type="submit"><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Data Tes' ?></button></div>
</form>
