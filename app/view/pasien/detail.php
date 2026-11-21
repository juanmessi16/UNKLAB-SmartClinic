<?php
require __DIR__ . '/../template/header.php';
$p     = $data['patient'];
$quota = $data['quota'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-person-heart text-teal me-2"></i><?= e($p['nama']) ?> <span class="text-muted-2 fs-5">(<?= e($p['nim_nip']) ?>)</span></h2>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('triase/') ?>" class="btn btn-teal"><i class="bi bi-heart-pulse-fill me-1"></i>Konsultasi &amp; Buat Antrean</a>
    <a href="<?= url('pasien/') ?>" class="btn btn-soft"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card-x mb-3">
      <div class="card-head"><h6 class="section-title">Identitas Pasien</h6></div>
      <div class="card-body-x">
        <div class="row g-2 mb-3">
          <div class="col-6"><div class="vital-box text-center"><div class="l">Gender</div><div class="v"><?= $p['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></div></div></div>
          <div class="col-6"><div class="vital-box text-center"><div class="l">Izin Sakit</div><div class="v"><?= (int)$quota['terpakai'] ?>/<?= (int)$quota['kuota'] ?></div></div></div>
        </div>
        <div class="p-3 rounded-3 mb-2" style="background:var(--slate-50);border:1px solid var(--slate-200)">
          <div class="small fw-bold text-navy">Penyakit Kronis / Komorbid</div>
          <div><?= e($p['penyakit_kronis'] ?: 'Tidak ada') ?></div>
        </div>
        <div class="small text-muted-2 mt-2"><i class="bi bi-telephone me-1"></i>HP: <?= e($p['no_hp'] ?: '-') ?><br><i class="bi bi-person-lines-fill me-1"></i>Darurat: <?= e($p['kontak_darurat'] ?: '-') ?></div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card-x mb-4">
      <div class="card-head"><h6 class="section-title"><i class="bi bi-journal-medical text-gold me-1"></i>Riwayat Rekam Medis Elektronik (EMR)</h6></div>
      <div class="card-body-x">
        <?php if ($data['records']): ?>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead><tr><th>Tanggal</th><th>Diagnosis</th><th>Tanda Vital</th><th>Surat Sakit</th><th>Aksi</th></tr></thead>
              <tbody>
                <?php foreach ($data['records'] as $r): ?>
                <tr>
                  <td class="small"><?= e(tgl($r['visit_date'], true)) ?></td>
                  <td><div class="fw-bold text-navy"><?= e($r['diagnosis']) ?></div><span class="badge badge-soft-primary"><?= e($r['kategori_penyakit']) ?></span></td>
                  <td class="small"><?= e($r['tekanan_sistolik']) ?>/<?= e($r['tekanan_diastolik']) ?> mmHg &middot; <?= e($r['suhu']) ?>&deg;C &middot; <?= e($r['detak_jantung']) ?> bpm</td>
                  <td><?= $r['ada_surat'] ? '<span class="badge badge-soft-success">Ada Surat</span>' : '<span class="text-muted-2 small">-</span>' ?></td>
                  <td><a class="btn btn-soft btn-sm" href="<?= url('emr/detail/' . $r['id']) ?>">Buka EMR</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state"><i class="bi bi-journal-x"></i>Belum ada rekam medis untuk pasien ini.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-x">
      <div class="card-head"><h6 class="section-title"><i class="bi bi-file-earmark-medical text-gold me-1"></i>Riwayat Surat Keterangan Sakit</h6></div>
      <div class="card-body-x">
        <?php if ($data['leaves']): ?>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead><tr><th>Kode SKS</th><th>Periode</th><th>Hari</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody>
                <?php foreach ($data['leaves'] as $s): $st = Emr_model::leaveState($s); ?>
                <tr>
                  <td class="mono fw-bold"><?= e($s['kode_verifikasi']) ?></td>
                  <td><?= e(tgl($s['tanggal_mulai'])) ?> &ndash; <?= e(tgl($s['tanggal_selesai'])) ?></td>
                  <td><?= (int)$s['jumlah_hari'] ?> hari</td>
                  <td><span class="badge badge-soft-<?= $st[1] ?>"><?= e($st[0]) ?></span></td>
                  <td><a class="btn btn-soft btn-sm" href="<?= url('suratsakit/cetak/' . $s['id']) ?>"><i class="bi bi-printer me-1"></i>Cetak</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state"><i class="bi bi-file-earmark"></i>Belum ada surat keterangan sakit.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
