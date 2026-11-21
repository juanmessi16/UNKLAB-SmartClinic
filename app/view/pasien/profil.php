<?php
require __DIR__ . '/../template/header.php';
$p     = $data['patient'];
$quota = $data['quota'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-person-heart text-teal me-2"></i>Profil Kesehatan Pasien</h2>
    <p>Pastikan kontak darurat dan informasi kesehatan Anda selalu mutakhir untuk kemudahan layanan di UKESMAS.</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card-x mb-3">
      <div class="card-head"><h6 class="section-title">Kartu Pasien Kampus</h6></div>
      <div class="card-body-x">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="avatar gold" style="width:56px;height:56px;font-size:1.3rem"><?= e(initials($p['nama'])) ?></div>
          <div>
            <h5 class="mb-0"><?= e($p['nama']) ?></h5>
            <div class="mono small text-muted-2">NIM / NIP: <strong><?= e($p['nim_nip']) ?></strong></div>
          </div>
        </div>
        <hr>
        <div class="d-flex justify-content-between small mb-1"><span>Kuota Izin Sakit Semester Berjalan</span><strong><?= (int)$quota['terpakai'] ?> / <?= (int)$quota['kuota'] ?> hari (Sisa <?= (int)$quota['sisa'] ?>)</strong></div>
        <div class="progress"><div class="progress-bar bg-teal" style="width:<?= min(100, round($quota['terpakai']*100/max(1,$quota['kuota']))) ?>%"></div></div>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card-x">
      <div class="card-head"><h6 class="section-title">Perbarui Data Medis &amp; Kontak</h6></div>
      <div class="card-body-x">
        <form method="post" action="<?= url('pasien/simpan_profil') ?>">
          <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nomor HP / WhatsApp Aktif</label>
              <input type="text" class="form-control" name="no_hp" value="<?= e($p['no_hp']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kontak Darurat (Orang Tua / Wali)</label>
              <input type="text" class="form-control" name="kontak_darurat" value="<?= e($p['kontak_darurat']) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Penyakit Kronis / Riwayat Medis Penting (Opsional)</label>
              <input type="text" class="form-control" name="penyakit_kronis" value="<?= e($p['penyakit_kronis']) ?>" placeholder="Contoh: Asma ringan, Gastritis kronis">
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-teal"><i class="bi bi-check2-circle me-1"></i>Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
