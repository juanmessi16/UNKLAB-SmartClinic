<?php
$ready = $data['ready'] ?? true;
$loggedIn = !empty($_SESSION['user-id']);
$userRole = $_SESSION['user-role'] ?? '';
$dashUrl = in_array($userRole, ['lecturer', 'dosen'], true) ? url('verifikasi/') : url('dashboard/');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($data['title'] ?? 'UNKLAB SmartClinic - UKESMAS Universitas Klabat') ?></title>
  <link rel="icon" type="image/png" href="<?= asset('img/unklab-logo.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=<?= APP_VERSION ?>.4">
  <style>
    html, body {
      height: 100%;
      margin: 0;
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      color: #0F172A;
      background: radial-gradient(circle at 50% 15%, #E6FFFA 0%, #F8FAFC 48%, #F1F5F9 100%);
    }
    .cover-shell {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      padding: 2rem 1.25rem 1.25rem;
      position: relative;
      overflow-x: hidden;
    }
    .cover-glow-1 {
      position: fixed;
      top: -120px;
      left: 50%;
      transform: translateX(-50%);
      width: 680px;
      height: 360px;
      background: radial-gradient(ellipse, rgba(15, 118, 110, 0.08) 0%, transparent 70%);
      pointer-events: none;
      z-index: 0;
    }
    .cover-main {
      position: relative;
      z-index: 1;
      max-width: 720px;
      width: 100%;
      margin: auto 0;
      text-align: center;
      padding: 1rem 0;
    }
    .cover-logo {
      width: 86px;
      height: 86px;
      object-fit: contain;
      filter: drop-shadow(0 8px 18px rgba(15, 118, 110, 0.16));
      transition: transform .25s ease;
    }
    .cover-logo:hover {
      transform: scale(1.04);
    }
    .cover-badge {
      display: inline-flex;
      align-items: center;
      gap: .45rem;
      background: #F0FDFA;
      border: 1px solid #CCFBF1;
      color: #0F766E;
      padding: .42rem .95rem;
      border-radius: 999px;
      font-size: .78rem;
      font-weight: 700;
      letter-spacing: .04em;
      text-transform: uppercase;
    }
    .cover-title {
      font-weight: 800;
      font-size: clamp(1.85rem, 4.2vw, 2.75rem);
      line-height: 1.2;
      letter-spacing: -0.025em;
      color: #0F172A;
    }
    .cover-title .accent {
      color: #0F766E;
    }
    .cover-desc {
      font-size: clamp(0.95rem, 1.5vw, 1.08rem);
      line-height: 1.65;
      color: #475569;
      max-width: 590px;
      margin: 0 auto;
    }
    .btn-cover-login {
      background: #0F766E;
      border: none;
      color: #ffffff;
      padding: .85rem 2.4rem;
      border-radius: 14px;
      font-weight: 700;
      font-size: 1.05rem;
      box-shadow: 0 10px 24px -6px rgba(15, 118, 110, 0.38);
      transition: all .2s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: .65rem;
    }
    .btn-cover-login:hover {
      background: #115E59;
      color: #ffffff;
      transform: translateY(-2px);
      box-shadow: 0 14px 28px -6px rgba(15, 118, 110, 0.45);
    }
    .cover-sublink {
      color: #0F766E;
      font-weight: 600;
      font-size: .88rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      padding: .35rem .75rem;
      border-radius: 8px;
      transition: background .15s ease;
    }
    .cover-sublink:hover {
      background: #F0FDFA;
      color: #115E59;
    }
    .cover-foot {
      position: relative;
      z-index: 1;
      font-size: .78rem;
      color: #64748B;
      text-align: center;
      padding-top: 1rem;
    }
  </style>
</head>
<body>

<div class="cover-shell">
  <div class="cover-glow-1"></div>

  <main class="cover-main">
    <?php if (!$ready): ?>
      <div class="alert alert-warning text-start small shadow-sm mb-4 mx-auto" style="max-width:560px;border-radius:14px;">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
          <div>
            <strong><i class="bi bi-database-exclamation me-1"></i>Database <code>db_project</code> belum siap</strong>
            <div class="text-muted-2" style="font-size:.78rem">
              <?= !empty($data['db_diagnostics']['is_connected']) ? 'MySQL terhubung, namun skema SmartClinic belum diimpor.' : 'Tidak dapat terhubung ke MySQL.' ?>
            </div>
          </div>
          <?php if (!empty($data['db_diagnostics']['is_connected'])): ?>
            <form method="post" action="<?= url('setup/init') ?>">
              <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token'] ?? '') ?>">
              <button class="btn btn-teal btn-sm fw-bold">Inisialisasi Otomatis</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Logo Resmi UNKLAB -->
    <div class="mb-3">
      <img src="<?= asset('img/unklab-logo.png') ?>" alt="Logo Universitas Klabat" class="cover-logo">
    </div>

    <!-- Badge Unit Kesehatan -->
    <div class="mb-3">
      <span class="cover-badge">
        <i class="bi bi-hospital-fill"></i>
        <span>UKESMAS Universitas Klabat</span>
      </span>
    </div>

    <!-- Headline Utama -->
    <h1 class="cover-title mb-3">
      <span class="accent">UNKLAB SmartClinic</span> &mdash; Layanan Kesehatan Kampus Terpadu Berbasis AI
    </h1>

    <!-- Subjudul Deskripsi Singkat -->
    <p class="cover-desc mb-4 pb-1">
      Sistem layanan kesehatan modern untuk sivitas akademika Universitas Klabat. Nikmati kemudahan konsultasi keluhan awal berbasis AI 24 jam, sistem antrean online FIFO, rekam medis elektronik (EMR), serta verifikasi surat keterangan sakit digital.
    </p>

    <!-- Tombol Aksi Utama ke /login -->
    <div class="mb-2">
      <a href="<?= url('login/') ?>" class="btn-cover-login">
        <i class="bi bi-box-arrow-in-right fs-5"></i>
        <span>Masuk ke SmartClinic</span>
      </a>
    </div>

    <?php if ($loggedIn): ?>
      <div class="d-flex justify-content-center align-items-center mt-2">
        <a href="<?= $dashUrl ?>" class="cover-sublink" style="color:#475569">
          <i class="bi bi-grid-1x2-fill"></i>
          <span>Kembali ke Dashboard</span>
        </a>
      </div>
    <?php endif; ?>
  </main>

  <footer class="cover-foot">
    &copy; <?= date('Y') ?> UKESMAS Universitas Klabat &middot; <?= e(APP_NAME) ?>
  </footer>
</div>

<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
