<?php
require __DIR__ . '/../template/header.php';
$r     = $data['record'];
$rx    = $data['prescriptions'];
$leave = $data['leave'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-file-medical-fill text-gold me-2"></i>Rekam Medis Elektronik #<?= (int)$r['id'] ?></h2>
    <p>Pemeriksaan tanggal <?= e(tgl($r['visit_date'], true)) ?> oleh <strong><?= e($r['dokter'] ?: 'Dokter Jaga UKESMAS') ?></strong></p>
  </div>
  <div class="d-flex gap-2">
    <?php if ($leave): ?>
      <a href="<?= url('suratsakit/cetak/' . $leave['id']) ?>" class="btn btn-gold"><i class="bi bi-printer-fill me-1"></i>Cetak Surat Sakit (<?= e($leave['kode_verifikasi']) ?>)</a>
    <?php endif; ?>
    <a href="<?= url($data['is_staff'] ? 'emr/' : 'emr/riwayat') ?>" class="btn btn-soft"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card-x mb-3">
      <div class="card-head"><h6 class="section-title">Identitas Pasien</h6></div>
      <div class="card-body-x">
        <div class="fw-bold fs-5 text-navy"><?= e($r['nama']) ?></div>
        <div class="small text-muted-2">NIM / NIP: <strong><?= e($r['nim_nip']) ?></strong></div>
      </div>
    </div>

    <div class="card-x">
      <div class="card-head"><h6 class="section-title">Tanda Vital Saat Pemeriksaan</h6></div>
      <div class="card-body-x">
        <div class="row g-2 text-center">
          <div class="col-4"><div class="vital-box"><div class="l">Tekanan Darah</div><div class="v"><?= e($r['tekanan_sistolik']) ?>/<?= e($r['tekanan_diastolik']) ?></div><div class="small text-muted-2">mmHg</div></div></div>
          <div class="col-4"><div class="vital-box"><div class="l">Suhu Tubuh</div><div class="v <?= $r['suhu'] >= 38 ? 'text-danger' : '' ?>"><?= e($r['suhu']) ?>&deg;C</div><div class="small text-muted-2">Celcius</div></div></div>
          <div class="col-4"><div class="vital-box"><div class="l">Detak Jantung</div><div class="v"><?= e($r['detak_jantung']) ?></div><div class="small text-muted-2">bpm</div></div></div>
          <div class="col-4"><div class="vital-box"><div class="l">Respirasi</div><div class="v"><?= e($r['respirasi']) ?></div><div class="small text-muted-2">/menit</div></div></div>
          <div class="col-4"><div class="vital-box"><div class="l">Saturasi O2</div><div class="v"><?= e($r['saturasi_o2']) ?>%</div><div class="small text-muted-2">SpO2</div></div></div>
          <div class="col-4"><div class="vital-box"><div class="l">Berat Badan</div><div class="v"><?= e($r['berat_badan']) ?></div><div class="small text-muted-2">kg</div></div></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card-x mb-3">
      <div class="card-head">
        <h6 class="section-title">Hasil Pemeriksaan Klinis</h6>
        <span class="badge badge-soft-primary"><?= e($r['kategori_penyakit']) ?><?= $r['kode_icd'] ? ' · ICD-10: ' . e($r['kode_icd']) : '' ?></span>
      </div>
      <div class="card-body-x">
        <div class="mb-3"><div class="small text-muted-2 fw-bold">Keluhan Utama</div><div><?= e($r['keluhan_utama']) ?></div></div>
        <div class="mb-3"><div class="small text-muted-2 fw-bold">Anamnesis</div><div><?= e($r['anamnesis']) ?></div></div>
        <div class="mb-3"><div class="small text-muted-2 fw-bold">Diagnosis Dokter</div><div class="fw-bold fs-5 text-navy"><?= e($r['diagnosis']) ?></div></div>
        <div class="mb-2"><div class="small text-muted-2 fw-bold">Tindakan &amp; Edukasi</div><div><?= e($r['tindakan']) ?></div></div>
        <?php if ($r['catatan']): ?><div class="small text-muted-2"><strong>Catatan:</strong> <?= e($r['catatan']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="card-x mb-3">
      <div class="card-head"><h6 class="section-title"><i class="bi bi-capsule me-1 text-gold"></i>Resep Obat Digital</h6></div>
      <div class="card-body-x">
        <?php if ($rx): ?>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead><tr><th>Kode</th><th>Nama Obat</th><th>Jumlah</th><th>Aturan Pakai</th></tr></thead>
              <tbody>
                <?php foreach ($rx as $item): ?>
                <tr>
                  <td class="mono small"><?= e($item['kode']) ?></td>
                  <td class="fw-bold text-navy"><?= e($item['nama']) ?></td>
                  <td><?= (int)$item['jumlah'] ?> <?= e($item['satuan']) ?></td>
                  <td><?= e($item['aturan_pakai']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="small text-muted-2">Tidak ada resep obat pada kunjungan ini.</div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($leave): ?>
    <div class="card-x">
      <div class="card-head">
        <h6 class="section-title"><i class="bi bi-patch-check-fill text-success me-1"></i>Surat Keterangan Sakit Diterbitkan</h6>
        <a href="<?= url('suratsakit/cetak/' . $leave['id']) ?>" class="btn btn-gold btn-sm"><i class="bi bi-printer me-1"></i>Buka &amp; Cetak</a>
      </div>
      <div class="card-body-x d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <div class="mono fw-800 fs-5 text-navy"><?= e($leave['kode_verifikasi']) ?></div>
          <div class="small text-muted-2">Berlaku: <?= e(tgl($leave['tanggal_mulai'])) ?> s/d <?= e(tgl($leave['tanggal_selesai'])) ?> (<?= (int)$leave['jumlah_hari'] ?> hari)</div>
        </div>
        <a href="<?= url('verifikasi/?kode=' . urlencode($leave['kode_verifikasi'])) ?>" class="btn btn-soft btn-sm">Uji Verifikasi Dosen</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
