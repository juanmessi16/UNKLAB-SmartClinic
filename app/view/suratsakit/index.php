<?php
require __DIR__ . '/../template/header.php';
$isStaff = $data['is_staff'];
$f       = $data['filter'];
$quota   = $data['quota'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-file-earmark-medical-fill text-gold me-2"></i><?= $isStaff ? 'Manajemen Surat Keterangan Sakit Digital' : 'Surat Keterangan Sakit Saya' ?></h2>
    <p>Setiap surat sakit dilengkapi kode verifikasi unik (<code>SKS-XXXX-XXXX</code>) dan QR Code resmi untuk diverifikasi oleh dosen pengampu mata kuliah.</p>
  </div>
  <a href="<?= url('verifikasi/') ?>" class="btn btn-navy"><i class="bi bi-patch-check-fill me-1"></i>Halaman Verifikasi Dosen</a>
</div>

<?php if ($quota): ?>
<div class="card-x p-3 mb-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="fw-bold text-navy">Kuota Izin Sakit Semester Berjalan</div>
      <div class="small text-muted-2">Terpakai <strong><?= (int)$quota['terpakai'] ?> hari</strong> dari batas maksimal <?= (int)$quota['kuota'] ?> hari (Sisa <?= (int)$quota['sisa'] ?> hari)</div>
    </div>
    <div style="min-width:220px">
      <div class="progress"><div class="progress-bar bg-warning" style="width:<?= min(100, round($quota['terpakai']*100/max(1,$quota['kuota']))) ?>%"></div></div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($isStaff): ?>
<form class="filter-bar" method="get" action="<?= url('suratsakit/') ?>">
  <div class="flex-grow-1">
    <label class="form-label">Cari Kode SKS / Nama / NIM</label>
    <input type="text" class="form-control" name="q" value="<?= e($f['q']) ?>" placeholder="Contoh: SKS- atau nama mahasiswa...">
  </div>
  <div>
    <label class="form-label">Status Masa Berlaku</label>
    <select class="form-select" name="status">
      <option value="">Semua</option>
      <option value="aktif" <?= $f['status'] === 'aktif' ? 'selected' : '' ?>>Masih Aktif</option>
      <option value="selesai" <?= $f['status'] === 'selesai' ? 'selected' : '' ?>>Telah Berakhir</option>
      <option value="dicabut" <?= $f['status'] === 'dicabut' ? 'selected' : '' ?>>Dicabut</option>
    </select>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-navy" type="submit"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
    <a class="btn btn-soft" href="<?= url('suratsakit/') ?>">Reset</a>
  </div>
</form>
<?php endif; ?>

<div class="table-wrap">
  <div class="table-responsive">
    <table class="table table-modern">
      <thead>
        <tr>
          <th>Kode Verifikasi</th>
          <th>Mahasiswa / Pasien</th>
          <th>Tanggal Izin</th>
          <th>Durasi</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($data['leaves']): foreach ($data['leaves'] as $s):
          $st = Emr_model::leaveState($s);
        ?>
        <tr>
          <td class="mono fw-800 text-navy"><?= e($s['kode_verifikasi']) ?></td>
          <td><div class="fw-bold"><?= e($s['nama']) ?></div><div class="small text-muted-2"><?= e($s['nim_nip']) ?></div></td>
          <td class="small"><?= e(tgl($s['tanggal_mulai'])) ?> s/d <?= e(tgl($s['tanggal_selesai'])) ?></td>
          <td><strong><?= (int)$s['jumlah_hari'] ?></strong> hari</td>
          <td><span class="badge badge-soft-<?= $st[1] ?>"><?= e($st[0]) ?></span></td>
          <td>
            <div class="d-flex gap-1">
              <a class="btn btn-gold btn-sm" href="<?= url('suratsakit/cetak/' . $s['id']) ?>"><i class="bi bi-printer-fill me-1"></i>Surat &amp; QR</a>
              <a class="btn btn-soft btn-sm" href="<?= url('verifikasi/?kode=' . urlencode($s['kode_verifikasi'])) ?>">Verifikasi</a>
              <?php if ($isStaff && $s['status'] === 'Aktif'): ?>
                <form method="post" action="<?= url('suratsakit/cabut/' . $s['id']) ?>" data-confirm="Cabut masa berlaku surat sakit ini?">
                  <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
                  <button class="btn btn-soft btn-sm text-danger" type="submit" title="Cabut"><i class="bi bi-x-circle"></i></button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6"><div class="empty-state"><i class="bi bi-file-earmark-x"></i>Belum ada surat keterangan sakit.</div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
