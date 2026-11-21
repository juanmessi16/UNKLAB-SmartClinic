<?php
require __DIR__ . '/../template/header.php';
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-people-fill text-gold me-2"></i>Manajemen Pengguna &amp; Hak Akses (RBAC)</h2>
    <p>Pengelolaan akun 5 peran pengguna UNKLAB SmartClinic: Super Admin, Kepala UKESMAS, Dokter/Perawat, Dosen, dan Mahasiswa.</p>
  </div>
  <button class="btn btn-navy" data-bs-toggle="modal" data-bs-target="#modalUser" id="btnAddUser"><i class="bi bi-person-plus-fill me-1"></i>Tambah Pengguna</button>
</div>

<div class="table-wrap">
  <div class="table-responsive">
    <table class="table table-modern">
      <thead><tr><th>ID</th><th>Username</th><th>Nama Lengkap</th><th>Email</th><th>Peran (Role)</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach ($data['users'] as $u): ?>
        <tr>
          <td class="mono">#<?= (int)$u['id'] ?></td>
          <td class="mono fw-bold text-navy"><?= e($u['username']) ?></td>
          <td class="fw-bold"><?= e($u['nama_lengkap']) ?></td>
          <td class="small"><?= e($u['email']) ?></td>
          <td><span class="badge badge-soft-primary"><?= e(role_label($u['role'])) ?></span></td>
          <td><span class="badge badge-soft-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($u['status']) ?></span></td>
          <td class="small text-muted-2"><?= $u['last_login'] ? e(tgl($u['last_login'], true)) : 'Belum pernah' ?></td>
          <td>
            <div class="d-flex gap-1">
              <button type="button" class="btn btn-soft btn-sm btn-edit-user" data-user='<?= e(json_encode($u)) ?>'><i class="bi bi-pencil"></i></button>
              <?php if ((int)$u['id'] !== (int)$data['user']['id']): ?>
                <form method="post" action="<?= url('pengguna/toggle/' . $u['id']) ?>">
                  <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
                  <button class="btn btn-soft btn-sm" type="submit" title="Aktif/Nonaktifkan"><i class="bi bi-power"></i></button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalUser" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= url('pengguna/simpan') ?>">
      <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
      <input type="hidden" name="id" id="uId" value="0">
      <div class="modal-header"><h5 class="modal-title fw-bold" id="uModalTitle">Tambah Pengguna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control mono" name="username" id="uUsername" required></div>
        <div class="mb-3"><label class="form-label">Nama Lengkap &amp; Gelar</label><input type="text" class="form-control" name="nama_lengkap" id="uNama" required></div>
        <div class="mb-3"><label class="form-label">Email Institusi</label><input type="email" class="form-control" name="email" id="uEmail" required></div>
        <div class="mb-3">
          <label class="form-label">Peran (Role RBAC)</label>
          <select class="form-select" name="role" id="uRole">
            <?php foreach ($data['roles'] as $r): ?><option value="<?= e($r) ?>"><?= e(role_label($r)) ?> (<?= e($r) ?>)</option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Password <span class="small text-muted-2">(Kosongkan saat edit jika tidak diubah)</span></label><input type="password" class="form-control" name="password" id="uPassword"></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-navy">Simpan Pengguna</button></div>
    </form>
  </div>
</div>

<?php
$data['extra_js'] = <<<'JS'
<script>
(function(){
  var m = new bootstrap.Modal(document.getElementById('modalUser'));
  document.getElementById('btnAddUser').addEventListener('click', function(){
    document.getElementById('uModalTitle').textContent = 'Tambah Pengguna Baru';
    document.getElementById('uId').value = 0;
    document.getElementById('uUsername').value = '';
    document.getElementById('uUsername').readOnly = false;
    document.getElementById('uNama').value = '';
    document.getElementById('uEmail').value = '';
    document.getElementById('uPassword').required = true;
  });
  document.querySelectorAll('.btn-edit-user').forEach(function(b){
    b.addEventListener('click', function(){
      var u = JSON.parse(b.dataset.user);
      document.getElementById('uModalTitle').textContent = 'Edit Pengguna — ' + u.username;
      document.getElementById('uId').value = u.id;
      document.getElementById('uUsername').value = u.username;
      document.getElementById('uUsername').readOnly = true;
      document.getElementById('uNama').value = u.nama_lengkap;
      document.getElementById('uEmail').value = u.email;
      document.getElementById('uRole').value = u.role;
      document.getElementById('uPassword').required = false;
      m.show();
    });
  });
})();
</script>
JS;
require __DIR__ . '/../template/footer.php';
