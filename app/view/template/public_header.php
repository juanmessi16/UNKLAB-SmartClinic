<?php
$flash = $data['flash'] ?? null;
$pageTitle = $data['title'] ?? APP_NAME;
$loggedIn = !empty($_SESSION['user-id']);
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
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=<?= APP_VERSION ?>.5">
</head>
<body>
<nav class="navbar navbar-expand-lg public-nav py-2">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= url('/') ?>">
      <img src="<?= asset('img/unklab-logo.png') ?>" alt="Logo Universitas Klabat" style="width:36px;height:36px;object-fit:contain;flex-shrink:0;">
      <span class="text-navy">UNKLAB <span class="text-teal">SmartClinic</span></span>
    </a>
    <button class="navbar-toggler border-0 text-navy" type="button" data-bs-toggle="collapse" data-bs-target="#pubNav"><i class="bi bi-list fs-2"></i></button>
    <div class="collapse navbar-collapse" id="pubNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>#layanan">Layanan Klinik</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>#antrean">Antrean Live</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('verifikasi/') ?>">Verifikasi Surat</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('dokumentasi/') ?>">Dokumentasi</a></li>
        <li class="nav-item ms-lg-2">
          <?php if ($loggedIn): ?>
            <a class="btn btn-teal btn-sm px-3" href="<?= url('dashboard/') ?>"><i class="bi bi-grid-1x2-fill me-1"></i>Dashboard</a>
          <?php else: ?>
            <a class="btn btn-teal btn-sm px-3" href="<?= url('login/') ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</a>
          <?php endif; ?>
        </li>
      </ul>
    </div>
  </div>
</nav>
<?php if ($flash): ?>
  <div class="container mt-3"><div class="alert alert-<?= e($flash['type']) ?> alert-dismissible alert-auto fade show shadow-sm"><?= e($flash['text']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div></div>
<?php endif; ?>
