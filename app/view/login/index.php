<?php
$csrf = $data['csrf_token'];
$googleReady = !empty($data['google_configured']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($data['title']) ?></title>
  <link rel="icon" type="image/png" href="<?= asset('img/unklab-logo.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=<?= APP_VERSION ?>.5">
  <style>
    body {
      background: radial-gradient(circle at 50% 12%, #E6FFFA 0%, #F8FAFC 45%, #F1F5F9 100%);
      min-height: 100vh;
      color: #0F172A;
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    }
    .login-page-wrap {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 2rem 1.25rem;
    }
    .login-card-box {
      width: 100%;
      max-width: 440px;
      background: #ffffff;
      border: 1px solid #E2E8F0;
      border-radius: 20px;
      padding: 2.25rem 2rem 1.75rem;
      box-shadow: 0 20px 45px -12px rgba(15, 23, 42, .09), 0 0 1px 1px rgba(15, 23, 42, .02);
      transition: box-shadow .2s ease;
    }
    .login-card-box:hover {
      box-shadow: 0 24px 50px -10px rgba(15, 118, 110, .12), 0 0 1px 1px rgba(15, 23, 42, .02);
    }
    .auth-divider {
      display: flex;
      align-items: center;
      gap: .75rem;
      margin: 1.15rem 0;
      color: #94A3B8;
      font-size: .78rem;
      font-weight: 600;
    }
    .auth-divider::before, .auth-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #E2E8F0;
    }
    .btn-google-unklab {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .65rem;
      width: 100%;
      padding: .65rem 1rem;
      border-radius: 12px;
      background: #ffffff;
      color: #1E293B;
      border: 1px solid #CBD5E1;
      font-weight: 600;
      font-size: .88rem;
      box-shadow: 0 1px 2px rgba(15,23,42,.04);
      transition: all .18s ease;
      text-decoration: none;
    }
    .btn-google-unklab:hover {
      background: #F8FAFC;
      border-color: #0F766E;
      color: #0F766E;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px -4px rgba(15, 118, 110, .18);
    }
    .demo-toggle-btn {
      color: #64748B;
      font-size: .78rem;
      font-weight: 600;
      background: transparent;
      border: none;
      padding: .35rem .75rem;
      border-radius: 999px;
      transition: all .15s ease;
    }
    .demo-toggle-btn:hover, .demo-toggle-btn[aria-expanded="true"] {
      color: #0F766E;
      background: #F0FDFA;
    }
    .demo-toggle-btn[aria-expanded="true"] .demo-toggle-ico {
      transform: rotate(180deg);
    }
    .demo-toggle-ico {
      transition: transform .2s ease;
    }
    .demo-pill-btn {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      padding: .35rem .75rem;
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      border-radius: 999px;
      font-size: .76rem;
      font-weight: 600;
      color: #334155;
      cursor: pointer;
      transition: all .15s ease;
      text-decoration: none;
    }
    .demo-pill-btn:hover {
      background: #F0FDFA;
      border-color: #5EEAD4;
      color: #0F766E;
      transform: translateY(-1px);
    }
    .demo-pill-btn.active {
      background: #0F766E;
      border-color: #0F766E;
      color: #FFFFFF;
    }
    .demo-pill-btn.active i {
      color: #FFFFFF !important;
    }
    .hover-teal:hover {
      color: #0F766E !important;
    }
  </style>
</head>
<body>

<div class="login-page-wrap">
  <div class="login-card-box">
    <!-- Brand Logo & Header -->
    <div class="text-center mb-3">
      <a href="<?= url('/') ?>" class="d-inline-flex flex-column align-items-center text-decoration-none">
        <img src="<?= asset('img/unklab-logo.png') ?>" alt="Logo Universitas Klabat" style="width:56px;height:56px;object-fit:contain;filter:drop-shadow(0 4px 8px rgba(15,118,110,.14))" class="mb-2">
        <div class="fw-800 text-navy" style="font-size:1.15rem;letter-spacing:-.02em">
          UNKLAB <span style="color:#0F766E">SmartClinic</span>
        </div>
        <div class="text-muted-2" style="font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;font-weight:700">
          UKESMAS Universitas Klabat
        </div>
      </a>
    </div>

    <div class="text-center mb-3 pb-2 border-bottom" style="border-color:#F1F5F9">
      <h1 class="fw-800 text-navy mb-1" style="font-size:1.35rem">Masuk ke SmartClinic</h1>
      <p class="text-muted-2 small mb-0" style="font-size:.82rem;line-height:1.45">
        Gunakan akun klinik Anda atau login menggunakan akun resmi Google UNKLAB.
      </p>
    </div>

    <!-- Alert Notifikasi Sesi & Validasi -->
    <?php if (!$data['db_ready']): ?>
      <div class="alert alert-warning small py-2"><i class="bi bi-database-exclamation me-1"></i>Database belum siap. <a href="<?= url('/') ?>" class="alert-link">Buka beranda</a> untuk inisialisasi otomatis.</div>
    <?php endif; ?>
    <?php if ($data['error']): ?>
      <div class="alert alert-danger small py-2 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-circle-fill fs-6 flex-shrink-0"></i>
        <div><?= e($data['error']) ?></div>
      </div>
    <?php endif; ?>
    <?php if ($data['notice']): ?>
      <div class="alert alert-info small py-2 d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill fs-6 flex-shrink-0"></i>
        <div><?= e($data['notice']) ?></div>
      </div>
    <?php endif; ?>

    <!-- Form Login Utama -->
    <form method="post" action="<?= url('login/authenticate') ?>" autocomplete="off" id="loginForm">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      
      <div class="mb-3">
        <label class="form-label fw-bold small text-navy" for="username">Username</label>
        <div class="input-group">
          <span class="input-group-text bg-white text-muted-2" style="border-radius:11px 0 0 11px;border-color:#CBD5E1">
            <i class="bi bi-person"></i>
          </span>
          <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required maxlength="80" style="border-radius:0 11px 11px 0;border-color:#CBD5E1;font-size:.9rem" autofocus>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold small text-navy" for="password">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-white text-muted-2" style="border-radius:11px 0 0 11px;border-color:#CBD5E1">
            <i class="bi bi-lock"></i>
          </span>
          <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required maxlength="100" style="border-radius:0;border-color:#CBD5E1;font-size:.9rem">
          <button class="btn btn-soft" type="button" id="togglePw" style="border-radius:0 11px 11px 0;border-color:#CBD5E1" aria-label="Tampilkan password">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <button class="btn btn-teal w-100 py-2 fs-6 fw-bold shadow-sm" type="submit" style="border-radius:11px">
        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
      </button>
    </form>

    <!-- Garis Pemisah & Tombol Google OAuth 2.0 -->
    <div class="auth-divider">atau</div>

    <a href="<?= url('login/google') ?>" class="btn-google-unklab" id="btnGoogleUnklab">
      <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
      </svg>
      <span>Login dengan Akun UNKLAB</span>
    </a>
    <div class="text-center small text-muted-2 mt-2" style="font-size:.76rem">
      Khusus mahasiswa menggunakan email <code>@student.unklab.ac.id</code> / <code>@unklab.ac.id</code>
    </div>

    <?php if (!$googleReady): ?>
      <div class="mt-3 p-2 px-3 rounded-3 small" style="background:#F0FDFA;border:1px solid #CCFBF1;color:#115E59;font-size:.76rem">
        <i class="bi bi-gear me-1"></i><strong>Info OAuth:</strong> Isi <code>GOOGLE_CLIENT_ID</code> &amp; <code>GOOGLE_CLIENT_SECRET</code> di <code>app/config/config.php</code>.<br>
        Redirect URI: <code class="mono" style="font-size:.72rem;word-break:break-all"><?= e($data['google_redirect']) ?></code>
      </div>
    <?php endif; ?>

    <!-- Akun Demo Ringkas di Bagian Bawah (Collapsible Tertutup Secara Default) -->
    <div class="mt-3 pt-3 border-top" style="border-color:#F1F5F9">
      <div class="text-center">
        <button class="demo-toggle-btn d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#demoCollapse" aria-expanded="false" aria-controls="demoCollapse">
          <i class="bi bi-person-badge text-teal"></i>
          <span>Akses Akun Demo (Klik untuk isi cepat)</span>
          <i class="bi bi-chevron-down demo-toggle-ico" style="font-size:.7rem"></i>
        </button>
      </div>
      <div class="collapse mt-2" id="demoCollapse">
        <div class="d-flex flex-wrap gap-2 justify-content-center pt-2">
          <?php foreach ($data['demo_accounts'] as $d): ?>
            <button type="button" class="demo-pill-btn" data-u="<?= e($d[0]) ?>" data-p="<?= e($d[1]) ?>" title="<?= e($d[3]) ?>">
              <i class="bi <?= e($d[4]) ?> text-teal"></i>
              <span><?= e($d[2]) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="text-center mt-2 small text-muted-2" style="font-size:.7rem">
          <i class="bi bi-info-circle me-1"></i>Klik peran untuk mengisi username &amp; password otomatis.
        </div>
      </div>
    </div>
  </div>

  <!-- Footer Hak Cipta Ringkas -->
  <div class="text-center mt-3 small text-muted-2" style="font-size:.78rem">
    &copy; <?= date('Y') ?> UKESMAS Universitas Klabat &middot; <?= e(APP_NAME) ?>
  </div>
</div>

<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script>
document.querySelectorAll('.demo-pill-btn').forEach(function (b) {
  b.addEventListener('click', function () {
    var u = document.getElementById('username');
    var p = document.getElementById('password');
    u.value = b.dataset.u;
    p.value = b.dataset.p;
    document.querySelectorAll('.demo-pill-btn').forEach(function (x) { x.classList.remove('active'); });
    b.classList.add('active');
    p.focus();
  });
});
document.getElementById('togglePw').addEventListener('click', function () {
  var p = document.getElementById('password');
  var ico = this.querySelector('i');
  if (p.type === 'password') {
    p.type = 'text';
    ico.className = 'bi bi-eye-slash';
  } else {
    p.type = 'password';
    ico.className = 'bi bi-eye';
  }
});
</script>
</body>
</html>
