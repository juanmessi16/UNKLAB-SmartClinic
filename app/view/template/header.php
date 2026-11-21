<?php
// Layout dashboard (sidebar + topbar). Pengunjung belum login otomatis memakai layout publik.
if (empty($_SESSION['user-id'])) {
    require __DIR__ . '/public_header.php';
    return;
}
$u = $data['user'] ?? ['nama' => $_SESSION['user-name'] ?? 'Pengguna', 'role' => $_SESSION['user-role'] ?? ''];
if (($u['nama'] ?? '') === 'Christian Mandagi') {
    $u['nama'] = 'Juan Messi Septhian Tulengkey';
    $_SESSION['user-name'] = 'Juan Messi Septhian Tulengkey';
}
$role = $u['role'];
$activeMenu = $data['active_menu'] ?? '';
$menus = sidebar_menu($role);
$pageTitle = $data['title'] ?? APP_NAME;
$notifCritical = 0; $waitCount = 0;
try {
    if (in_array($role, ['superadmin', 'clinicadmin', 'medstaff'])) {
        $notifCritical = (int)Database::shared()->fetchValue("SELECT COUNT(*) FROM medicines WHERE stok <= stok_minimum");
        $waitCount = (int)Database::shared()->fetchValue("SELECT COUNT(*) FROM appointments_queue WHERE queue_date = CURDATE() AND status = 'Menunggu'");
    }
} catch (Throwable $e) {}
$flash = $data['flash'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e($data['csrf_token'] ?? '') ?>">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" type="image/png" href="<?= asset('img/unklab-logo.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=<?= APP_VERSION ?>.6">
</head>
<body>
<div class="app-shell">
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <aside class="sidebar" id="sidebar">
    <?php $brandTarget = in_array($role, ['lecturer', 'dosen'], true) ? 'verifikasi/' : 'dashboard/'; ?>
    <a href="<?= url($brandTarget) ?>" class="sidebar-brand">
      <img src="<?= asset('img/unklab-logo.png') ?>" alt="Logo Universitas Klabat" class="brand-logo" style="width:38px;height:38px;object-fit:contain;flex-shrink:0;">
      <span><div class="brand-title">UNKLAB SmartClinic</div><div class="brand-sub">UKESMAS &middot; Klinik Kampus</div></span>
    </a>
    <nav class="pb-3 pt-1">
      <?php foreach ($menus as $section => $items): ?>
        <div class="nav-section"><?= e($section) ?></div>
        <?php foreach ($items as $it): ?>
          <a class="side-link <?= $activeMenu === $it[0] ? 'active' : '' ?>" href="<?= url($it[3] . '/') ?>">
            <i class="bi <?= e($it[2]) ?>"></i><span><?= e($it[1]) ?></span>
            <?php if ($it[0] === 'antrean' && $waitCount > 0): ?><span class="pill"><?= $waitCount ?></span><?php endif; ?>
            <?php if ($it[0] === 'obat' && $notifCritical > 0): ?><span class="pill"><?= $notifCritical ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <div class="nav-section">Akun</div>
      <a class="side-link" href="<?= url('/') ?>"><i class="bi bi-house-door"></i><span>Beranda Publik</span></a>
      <a class="side-link" href="<?= url('login/logout') ?>"><i class="bi bi-box-arrow-left"></i><span>Keluar</span></a>
    </nav>
  </aside>

  <div class="app-main">
    <header class="topbar">
      <div class="d-flex align-items-center">
        <button class="btn-icon d-lg-none" data-toggle-sidebar aria-label="Menu"><i class="bi bi-list fs-5"></i></button>
      </div>
      <div class="ms-auto d-flex align-items-center gap-2 gap-sm-3">
        <span class="d-none d-md-inline small text-muted-2"><i class="bi bi-calendar3 me-1 text-teal"></i><?= e(tgl(date('Y-m-d'))) ?></span>
        <?php if ($notifCritical > 0): ?>
          <a href="<?= url('obat/?status=kritis') ?>" class="btn-icon" title="<?= $notifCritical ?> obat stok kritis"><i class="bi bi-bell"></i><span class="dot"></span></a>
        <?php endif; ?>
        <div class="dropdown">
          <button class="topbar-user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu Pengguna">
            <?php if (!empty($u['avatar_url'])): ?>
              <img src="<?= e($u['avatar_url']) ?>" alt="<?= e($u['nama']) ?>" referrerpolicy="no-referrer"
                   class="topbar-avatar"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='grid'">
              <div class="avatar teal topbar-avatar" style="display:none"><?= e(initials($u['nama'])) ?></div>
            <?php else: ?>
              <div class="avatar teal topbar-avatar"><?= e(initials($u['nama'])) ?></div>
            <?php endif; ?>
            <div class="topbar-user-info text-start d-none d-sm-block">
              <div class="topbar-user-name text-truncate"><?= e($u['nama']) ?></div>
              <div class="topbar-user-role text-truncate"><?= e(role_label($role)) ?></div>
            </div>
            <i class="bi bi-chevron-down topbar-chevron d-none d-sm-inline ms-1"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border p-2" style="border-radius:12px;min-width:230px;border-color:var(--slate-200)">
            <li class="px-3 py-2">
              <div class="fw-bold text-navy text-truncate"><?= e($u['nama']) ?></div>
              <div class="small text-muted-2"><?= e(role_label($role)) ?> &bull; <?= e($u['email'] ?? '') ?></div>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item rounded-2 small py-2" href="<?= url('profil/') ?>"><i class="bi bi-key me-2 text-muted-2"></i>Ubah Password</a></li>
            <li><a class="dropdown-item rounded-2 small py-2 text-danger" href="<?= url('login/logout') ?>"><i class="bi bi-box-arrow-left me-2"></i>Keluar</a></li>
          </ul>
        </div>
      </div>
    </header>
    <main class="content">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible alert-auto fade show shadow-sm" role="alert">
          <?= e($flash['text']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
