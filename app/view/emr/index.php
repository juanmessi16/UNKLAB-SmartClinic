<?php
require __DIR__ . '/../template/header.php';
$isStaff = $data['is_staff'];
$f       = $data['filter'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-journal-medical text-teal me-2"></i><?= $isStaff ? 'Electronic Medical Records (EMR)' : 'Riwayat Pemeriksaan Medis Saya' ?></h2>
    <p><?= $isStaff ? 'Pencatatan rekam medis elektronik, tanda vital, diagnosis ICD-10, resep digital, dan penerbitan surat sakit.' : 'Daftar hasil pemeriksaan dokter UKESMAS, tanda vital, resep obat, dan surat keterangan sakit Anda.' ?></p>
  </div>
  <?php if ($isStaff): ?>
    <a href="<?= url('antrean/') ?>" class="btn btn-teal"><i class="bi bi-clipboard2-pulse-fill me-1"></i>Periksa Pasien dari Antrean</a>
  <?php else: ?>
    <a href="<?= url('triase/') ?>" class="btn btn-teal"><i class="bi bi-heart-pulse-fill me-1"></i>Konsultasi &amp; Berobat Baru</a>
  <?php endif; ?>
</div>

<?php if ($isStaff): ?>
<form class="filter-bar" method="get" action="<?= url('emr/') ?>">
  <div class="flex-grow-1" style="min-width:200px">
    <label class="form-label">Cari Pasien / NIM / Diagnosis</label>
    <input type="text" class="form-control" name="q" value="<?= e($f['q']) ?>" placeholder="Nama, NIM, atau diagnosis...">
  </div>
  <div>
    <label class="form-label">Kategori Penyakit</label>
    <select class="form-select" name="kategori">
      <option value="">Semua</option>
      <?php foreach ($data['categories'] as $c): ?>
        <option value="<?= e($c) ?>" <?= $f['kategori'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="form-label">Dari Tanggal</label>
    <input type="date" class="form-control" name="dari" value="<?= e($f['dari']) ?>">
  </div>
  <div>
    <label class="form-label">Sampai Tanggal</label>
    <input type="date" class="form-control" name="sampai" value="<?= e($f['sampai']) ?>">
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-navy" type="submit"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
    <a class="btn btn-soft" href="<?= url('emr/') ?>">Reset</a>
  </div>
</form>
<?php endif; ?>

<div class="table-wrap">
  <div class="table-responsive">
    <table class="table table-modern">
      <thead>
        <tr>
          <th>No. EMR</th>
          <th>Tanggal Kunjungan</th>
          <th>Pasien</th>
          <th>Tanda Vital</th>
          <th>Diagnosis &amp; Kategori</th>
          <th>Surat Sakit</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($data['records']): foreach ($data['records'] as $r): ?>
        <tr>
          <td class="mono fw-bold">#<?= (int)$r['id'] ?></td>
          <td class="small"><?= e(tgl($r['visit_date'], true)) ?></td>
          <td>
            <div class="fw-bold text-navy"><?= e($r['nama']) ?></div>
            <div class="small text-muted-2">NIM / NIP: <?= e($r['nim_nip']) ?></div>
          </td>
          <td class="small">
            <div>TD: <strong><?= e($r['tekanan_sistolik']) ?>/<?= e($r['tekanan_diastolik']) ?></strong> mmHg</div>
            <div>Suhu: <strong class="<?= $r['suhu'] >= 38 ? 'text-danger' : '' ?>"><?= e($r['suhu']) ?>&deg;C</strong> &middot; Nadi: <?= e($r['detak_jantung']) ?> bpm</div>
          </td>
          <td>
            <div class="fw-bold text-navy"><?= e($r['diagnosis']) ?></div>
            <span class="badge badge-soft-primary"><?= e($r['kategori_penyakit']) ?></span>
          </td>
          <td>
            <?= $r['ada_surat'] ? '<span class="badge badge-soft-success"><i class="bi bi-check2-circle me-1"></i>Diterbitkan</span>' : '<span class="text-muted-2 small">Tidak ada</span>' ?>
          </td>
          <td>
            <a href="<?= url('emr/detail/' . $r['id']) ?>" class="btn btn-soft btn-sm"><i class="bi bi-eye me-1"></i>Detail EMR</a>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7"><div class="empty-state"><i class="bi bi-journal-x"></i>Belum ada data rekam medis yang ditemukan.</div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
