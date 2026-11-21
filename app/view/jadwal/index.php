<?php
require __DIR__ . '/../template/header.php';
$canEdit = $data['can_edit'];
$today   = $data['hari_ini'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-calendar2-week-fill text-gold me-2"></i>Jadwal Dokter &amp; Perawat Jaga UKESMAS</h2>
    <p>Jadwal operasional pelayanan medis klinik kampus Universitas Klabat. Hari ini: <strong><?= e($today) ?></strong>.</p>
  </div>
  <?php if ($canEdit): ?>
    <button class="btn btn-navy" data-bs-toggle="modal" data-bs-target="#modalSched"><i class="bi bi-plus-lg me-1"></i>Tambah Jadwal Jaga</button>
  <?php endif; ?>
</div>

<div class="table-wrap">
  <div class="table-responsive">
    <table class="table table-modern">
      <thead><tr><th>Hari</th><th>Jam Jaga (WITA)</th><th>Nama Tenaga Medis</th><th>Peran</th><th>Ruang Layanan</th><?php if ($canEdit): ?><th>Aksi</th><?php endif; ?></tr></thead>
      <tbody>
        <?php foreach ($data['schedules'] as $s): ?>
        <tr class="<?= $s['hari'] === $today ? 'table-warning' : '' ?>">
          <td class="fw-bold text-navy">
            <?= e($s['hari']) ?>
            <?php if ($s['hari'] === $today): ?><span class="badge badge-soft-success ms-1">Hari Ini</span><?php endif; ?>
          </td>
          <td class="mono"><?= e($s['jam_mulai']) ?> &ndash; <?= e($s['jam_selesai']) ?></td>
          <td class="fw-bold"><?= e($s['nama_tenaga']) ?></td>
          <td><span class="badge badge-soft-primary"><?= e($s['peran']) ?></span></td>
          <td><?= e($s['lokasi']) ?></td>
          <?php if ($canEdit): ?>
          <td>
            <form method="post" action="<?= url('jadwal/hapus/' . $s['id']) ?>" data-confirm="Hapus jadwal ini?">
              <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
              <button class="btn btn-soft btn-sm text-danger" type="submit"><i class="bi bi-trash"></i></button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="modalSched" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= url('jadwal/simpan') ?>">
      <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
      <div class="modal-header"><h5 class="modal-title fw-bold">Tambah Jadwal Jaga</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Hari</label>
            <select class="form-select" name="hari">
              <?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h): ?><option value="<?= $h ?>"><?= $h ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Jam Mulai</label><input type="text" class="form-control mono" name="jam_mulai" value="08:00" required></div>
          <div class="col-md-4"><label class="form-label">Jam Selesai</label><input type="text" class="form-control mono" name="jam_selesai" value="16:00" required></div>
          <div class="col-12"><label class="form-label">Nama Dokter / Perawat</label><input type="text" class="form-control" name="nama_tenaga" required></div>
          <div class="col-md-6"><label class="form-label">Peran</label><input type="text" class="form-control" name="peran" value="Dokter Umum" required></div>
          <div class="col-md-6"><label class="form-label">Ruangan</label><input type="text" class="form-control" name="lokasi" value="Ruang Periksa 1" required></div>
        </div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-navy">Simpan</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../template/footer.php'; ?>
