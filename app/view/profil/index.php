<?php
require __DIR__ . '/../template/header.php';
$acc = $data['account'];
?>

<div class="container py-2" style="max-width:680px">
  <div class="page-head">
    <div>
      <h2><i class="bi bi-key-fill text-gold me-2"></i>Pengaturan Akun &amp; Keamanan</h2>
      <p>Kelola informasi akun dan perbarui password login Anda.</p>
    </div>
  </div>

  <div class="card-x mb-4">
    <div class="card-body-x d-flex align-items-center gap-3">
      <div class="avatar gold" style="width:56px;height:56px;font-size:1.3rem"><?= e(initials($acc['nama_lengkap'])) ?></div>
      <div>
        <div class="fw-bold fs-5 text-navy"><?= e($acc['nama_lengkap']) ?></div>
        <div class="small text-muted-2"><span class="mono"><?= e($acc['username']) ?></span> &middot; <?= e($acc['email']) ?> &middot; <span class="badge badge-soft-primary"><?= e(role_label($acc['role'])) ?></span></div>
      </div>
    </div>
  </div>

  <div class="card-x">
    <div class="card-head"><h6 class="section-title">Ubah Password Login</h6></div>
    <div class="card-body-x">
      <form method="post" action="<?= url('profil/ubah_password') ?>">
        <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
        <div class="mb-3"><label class="form-label">Password Saat Ini</label><input type="password" class="form-control" name="password_lama" required></div>
        <div class="mb-3"><label class="form-label">Password Baru (Minimal 6 karakter)</label><input type="password" class="form-control" name="password_baru" minlength="6" required></div>
        <div class="mb-3"><label class="form-label">Ulangi Password Baru</label><input type="password" class="form-control" name="konfirmasi_password" minlength="6" required></div>
        <button type="submit" class="btn btn-navy"><i class="bi bi-check2-circle me-1"></i>Simpan Password Baru</button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
