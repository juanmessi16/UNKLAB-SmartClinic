<?php
require __DIR__ . '/../template/header.php';
$kode = $data['kode'];
$res  = $data['result'];
?>

<?php if (!empty($data['is_lecturer']) && !empty($data['live_queue']) && !empty($data['gemini_status'])):
  $u    = $data['user'] ?? ['nama' => $_SESSION['user-name'] ?? 'Dosen', 'role' => $_SESSION['user-role'] ?? 'lecturer'];
  $role = $u['role'];
  $live = $data['live_queue'];
  $gem  = $data['gemini_status'];
?>
<!-- Welcome Banner Khusus Role Dosen (Clean Clinical Theme) -->
<div class="welcome-banner mb-4">
  <div class="row align-items-center g-3">
    <div class="col-lg-8">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <span class="badge badge-soft-primary rounded-pill">
          <i class="bi bi-person-badge me-1"></i><?= e(role_label($role)) ?>
        </span>
        <span class="badge badge-soft-info rounded-pill">
          <i class="bi bi-heart-pulse me-1"></i>Mesin Klinis: <?= $gem['configured'] ? 'Google Gemini (' . e($gem['model']) . ')' : 'Contextual Clinical Engine' ?>
        </span>
      </div>
      <h2>Halo, <?= e($u['nama'] ?? 'Dosen') ?>!</h2>
      <p>Verifikasi keaslian Surat Keterangan Sakit mahasiswa secara instan menggunakan kode unik SKS, atau gunakan layanan pemeriksaan kesehatan dosen.</p>
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= url('triase/') ?>" class="btn btn-teal"><i class="bi bi-heart-pulse-fill me-1"></i>Konsultasi AI &amp; Antrean</a>
        <a href="<?= url('antrean/') ?>" class="btn btn-outline-clinical"><i class="bi bi-ticket-perforated-fill me-1"></i>Antrean Saya</a>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="live-indicator-box p-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="small text-uppercase fw-bold" style="letter-spacing:.08em;color:var(--slate-500);font-size:.7rem"><span class="live-dot"></span>Antrean Live Hari Ini</span>
          <span class="small text-muted-2 mono"><?= e($live['waktu']) ?></span>
        </div>
        <div class="d-flex align-items-baseline justify-content-between">
          <div>
            <div class="small text-muted-2">Sedang Dipanggil</div>
            <div class="fw-800 mono text-teal" style="font-size:2.2rem;line-height:1.05"><?= e($live['dipanggil'] ?? '—') ?></div>
          </div>
          <div class="text-end">
            <div class="small text-muted-2">Menunggu: <strong class="text-navy"><?= (int)$live['menunggu'] ?></strong></div>
            <div class="small text-muted-2">Selesai: <strong class="text-navy"><?= (int)$live['selesai'] ?></strong></div>
            <div class="small text-clinical-blue fw-semibold">Est. <?= (int)$live['estimasi_menit'] ?> menit</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="container py-3" style="max-width:880px">
  <div class="text-center mb-4">
    <span class="badge badge-soft-primary mb-2"><i class="bi bi-qr-code-scan me-1"></i>Portal Validasi Akademik</span>
    <h2 class="fw-800">Verifikasi Surat Keterangan Sakit</h2>
    <p class="text-muted-2">Masukkan kode unik yang tertera pada Surat Keterangan Sakit mahasiswa (format <code>SKS-XXXX-XXXX</code>) untuk memeriksa keabsahan dan masa berlakunya.</p>
  </div>

  <div class="card-x p-4 mb-4">
    <form method="get" action="<?= url('verifikasi/') ?>" id="verifyForm" class="d-flex flex-wrap gap-2 align-items-stretch">
      <input type="text" class="form-control form-control-lg mono flex-grow-1" id="inputKode" name="kode" value="<?= e($kode) ?>" placeholder="Contoh: SKS-9F63-88B7" required autocomplete="off">
      <button class="btn btn-navy btn-lg px-4" type="submit" id="btnVerifySubmit">
        <i class="bi bi-patch-check-fill me-1"></i>Verifikasi Sekarang
      </button>
      <button class="btn btn-outline-teal btn-lg px-3" type="button" id="btnToggleScanner" aria-expanded="false" aria-controls="scannerWrapper">
        <i class="bi bi-qr-code-scan me-1"></i><span>Scan Barcode / QR Kamera</span>
      </button>
    </form>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 pt-1">
      <div class="small text-muted-2">
        <i class="bi bi-camera me-1 text-teal"></i>Gunakan kamera HP/laptop untuk memindai QR Code atau Barcode pada lembar Surat Sakit.
      </div>
      <div>
        <input type="file" id="qrFileInput" accept="image/*" class="d-none">
        <button type="button" class="btn btn-soft btn-sm" id="btnUploadQr">
          <i class="bi bi-image me-1 text-teal"></i>Upload Gambar Barcode / QR
        </button>
      </div>
    </div>

    <!-- Area Feedback Hasil Scan / Error Kamera -->
    <div id="scannerFeedback" class="alert small py-2 px-3 mt-3 mb-0 d-none" role="status"></div>

    <!-- Area Preview Kamera Pemindai Barcode / QR -->
    <div id="scannerWrapper" class="scanner-wrapper mt-3 d-none">
      <div class="scanner-card">
        <div class="scanner-header">
          <div class="d-flex align-items-center gap-2">
            <span class="live-dot"></span>
            <span class="fw-bold small text-navy">Pemindai Barcode &amp; QR Kamera</span>
          </div>
          <div class="d-flex align-items-center gap-1">
            <button type="button" class="btn btn-soft btn-sm d-none" id="btnSwitchCamera" title="Ganti Kamera">
              <i class="bi bi-arrow-repeat"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-semibold" id="btnCloseScanner">
              <i class="bi bi-x-lg me-1"></i>Tutup Kamera
            </button>
          </div>
        </div>

        <div class="scanner-viewport-box">
          <div id="qrReader" class="qr-reader-viewport"></div>
          <div class="scanner-guide-overlay" id="scannerGuideOverlay">
            <div class="scanner-frame">
              <span class="scanner-laser"></span>
            </div>
          </div>
        </div>

        <div class="scanner-footer text-center small text-muted-2">
          Arahkan kode QR atau Barcode <code>SKS-XXXX-XXXX</code> ke dalam kotak pemindai.
        </div>
      </div>
    </div>
  </div>

  <?php if ($kode !== ''): ?>
    <?php if ($res):
      $st = Emr_model::leaveState($res);
    ?>
      <div class="card-x p-4 mb-4" style="border:2px solid <?= $res['status']==='Dicabut' ? '#f43f5e' : '#10b981' ?>">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-icon <?= $res['status']==='Dicabut' ? 'rose' : 'emerald' ?>" style="width:56px;height:56px;font-size:1.7rem">
              <i class="bi bi-<?= $res['status']==='Dicabut' ? 'x-octagon-fill' : 'patch-check-fill' ?>"></i>
            </div>
            <div>
              <div class="small text-uppercase fw-bold text-muted-2">Hasil Validasi Resmi UKESMAS UNKLAB</div>
              <h4 class="fw-800 mb-0"><?= $res['status']==='Dicabut' ? 'SURAT TELAH DICABUT' : 'SURAT KETERANGAN SAKIT TERVERIFIKASI ASLI' ?></h4>
            </div>
          </div>
          <span class="badge badge-soft-<?= $st[1] ?> fs-6"><?= e($st[0]) ?></span>
        </div>
        <hr>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="small text-muted-2">Nama Mahasiswa / Pasien</div>
            <div class="fw-bold fs-5 text-navy"><?= e($res['nama']) ?></div>
            <div class="mono small">NIM / NIP: <strong><?= e($res['nim_nip']) ?></strong></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-2">Masa Berlaku Istirahat Medis</div>
            <div class="fw-bold fs-5 text-navy"><?= e(tgl($res['tanggal_mulai'])) ?> &ndash; <?= e(tgl($res['tanggal_selesai'])) ?></div>
            <div class="small">Durasi: <strong><?= (int)$res['jumlah_hari'] ?> hari</strong></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-2">Dokter Pemeriksa</div>
            <div class="fw-semibold"><?= e($res['dokter'] ?: 'dr. Daniel Pangemanan') ?></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted-2">Waktu Penerbitan &amp; Kode</div>
            <div class="fw-semibold"><?= e(tgl($res['created_at'], true)) ?> &middot; <span class="mono"><?= e($res['kode_verifikasi']) ?></span></div>
          </div>
        </div>
        <?php if (!empty($_SESSION['user-id'])): ?>
          <div class="mt-3 pt-3 border-top text-end">
            <a href="<?= url('suratsakit/cetak/' . $res['id']) ?>" class="btn btn-gold btn-sm"><i class="bi bi-printer me-1"></i>Lihat Lembar Surat Resmi</a>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="card-x p-4 mb-4" style="border:2px solid #f43f5e">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon rose"><i class="bi bi-shield-fill-exclamation"></i></div>
          <div>
            <h5 class="fw-800 text-danger mb-1">Kode Tidak Ditemukan / Tidak Terdaftar</h5>
            <div class="small text-muted-2">Kode <strong class="mono"><?= e($kode) ?></strong> tidak tercatat di database UKESMAS Universitas Klabat. Pastikan penulisan kode sudah benar.</div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($data['recent']): ?>
    <div class="card-x">
      <div class="card-head"><h6 class="section-title">Contoh Kode Surat Sakit Aktif (Klik untuk Uji Cepat)</h6></div>
      <div class="card-body-x">
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($data['recent'] as $s): ?>
            <a class="chip mono" href="<?= url('verifikasi/?kode=' . urlencode($s['kode_verifikasi'])) ?>">
              <i class="bi bi-qr-code text-gold"></i><?= e($s['kode_verifikasi']) ?> <span class="text-muted-2">(<?= e($s['nama']) ?>)</span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Library Pemindai QR Code & Barcode 1D (html5-qrcode) -->
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
  var verifyForm       = document.getElementById('verifyForm');
  var inputKode        = document.getElementById('inputKode');
  var btnToggleScanner = document.getElementById('btnToggleScanner');
  var btnCloseScanner  = document.getElementById('btnCloseScanner');
  var btnSwitchCamera  = document.getElementById('btnSwitchCamera');
  var btnUploadQr      = document.getElementById('btnUploadQr');
  var qrFileInput      = document.getElementById('qrFileInput');
  var scannerWrapper   = document.getElementById('scannerWrapper');
  var scannerFeedback  = document.getElementById('scannerFeedback');

  var html5QrCode      = null;
  var isScanning       = false;
  var isProcessingScan = false;
  var camerasList      = [];
  var currentCamIdx    = 0;

  function showFeedback(type, htmlMessage) {
    if (!scannerFeedback) return;
    scannerFeedback.className = 'alert alert-' + type + ' small py-2 px-3 mt-3 mb-0 d-flex align-items-center gap-2';
    var icon = type === 'success' ? 'bi-check-circle-fill' : (type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');
    scannerFeedback.innerHTML = '<i class="bi ' + icon + ' fs-6 flex-shrink-0"></i><div>' + htmlMessage + '</div>';
    scannerFeedback.classList.remove('d-none');
  }

  function hideFeedback() {
    if (!scannerFeedback) return;
    scannerFeedback.classList.add('d-none');
  }

  /**
   * Ekstraksi kode SKS cerdas dari teks mentah hasil scan QR / Barcode:
   * - Mendukung kode langsung: "SKS-9F63-88B7"
   * - Mendukung URL lengkap: "https://.../verifikasi/?kode=SKS-9F63-88B7" atau "/verifikasi/SKS-9F63-88B7"
   */
  function extractSksCode(rawText) {
    if (!rawText) return '';
    var text = String(rawText).trim();

    // 1. Cari pola standar SKS-XXXX-XXXX di mana saja dalam teks/URL
    var sksMatch = text.match(/\b(SKS-[A-Z0-9]{3,10}-[A-Z0-9]{3,10})\b/i);
    if (sksMatch && sksMatch[1]) {
      return sksMatch[1].toUpperCase();
    }

    // 2. Jika berupa URL dengan parameter ?kode=...
    var paramMatch = text.match(/[?&]kode=([^&#\s]+)/i);
    if (paramMatch && paramMatch[1]) {
      try {
        return decodeURIComponent(paramMatch[1]).trim().toUpperCase();
      } catch (e) {
        return paramMatch[1].trim().toUpperCase();
      }
    }

    // 3. Jika berupa URL path /verifikasi/KODE
    var pathMatch = text.match(/\/verifikasi\/([A-Za-z0-9_-]{4,30})/i);
    if (pathMatch && pathMatch[1]) {
      return pathMatch[1].toUpperCase();
    }

    // 4. Fallback: kode bersih langsung
    return text.replace(/[^A-Za-z0-9_-]/g, '').toUpperCase() || text.toUpperCase();
  }

  function ensureQrLibrary(callback) {
    if (typeof Html5Qrcode !== 'undefined') {
      callback(true);
      return;
    }
    var script = document.createElement('script');
    script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
    script.onload = function () { callback(typeof Html5Qrcode !== 'undefined'); };
    script.onerror = function () { callback(false); };
    document.head.appendChild(script);
  }

  function handleDecodedCode(decodedText) {
    if (isProcessingScan) return;
    var code = extractSksCode(decodedText);
    if (!code) return;

    isProcessingScan = true;
    inputKode.value = code;

    showFeedback('success', '<strong>Barcode terbaca: <span class="mono">' + code + '</span></strong> &mdash; Memverifikasi keaslian surat...');

    stopScanner().finally(function () {
      setTimeout(function () {
        verifyForm.submit();
      }, 300);
    });
  }

  function stopScanner() {
    if (!html5QrCode || !isScanning) {
      scannerWrapper.classList.add('d-none');
      btnToggleScanner.setAttribute('aria-expanded', 'false');
      isScanning = false;
      return Promise.resolve();
    }
    return html5QrCode.stop().then(function () {
      isScanning = false;
      scannerWrapper.classList.add('d-none');
      btnToggleScanner.setAttribute('aria-expanded', 'false');
    }).catch(function () {
      isScanning = false;
      scannerWrapper.classList.add('d-none');
      btnToggleScanner.setAttribute('aria-expanded', 'false');
    });
  }

  function startScannerWithCamera(cameraConfig) {
    if (!html5QrCode) {
      html5QrCode = new Html5Qrcode('qrReader');
    }
    var config = {
      fps: 12,
      qrbox: { width: 220, height: 220 },
      aspectRatio: 1.0
    };
    return html5QrCode.start(
      cameraConfig,
      config,
      function (decodedText) {
        handleDecodedCode(decodedText);
      },
      function () {
        // Frame scan sedang berjalan
      }
    ).then(function () {
      isScanning = true;
      isProcessingScan = false;
    });
  }

  function openScanner() {
    hideFeedback();
    ensureQrLibrary(function (loaded) {
      if (!loaded) {
        showFeedback('danger', 'Gagal memuat modul pemindai kamera. Periksa koneksi internet Anda atau masukkan kode SKS secara manual.');
        return;
      }

      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showFeedback('danger', 'Browser Anda tidak mendukung akses kamera langsung (pastikan mengakses melalui <strong>HTTPS</strong> atau <strong>localhost</strong>), atau gunakan tombol <strong>Upload Gambar Barcode / QR</strong>.');
        return;
      }

      scannerWrapper.classList.remove('d-none');
      btnToggleScanner.setAttribute('aria-expanded', 'true');

      // Utamakan kamera belakang (environment) untuk HP, fallback ke kamera depan / webcam laptop
      startScannerWithCamera({ facingMode: 'environment' }).then(function () {
        Html5Qrcode.getCameras().then(function (devices) {
          if (devices && devices.length > 1) {
            camerasList = devices;
            btnSwitchCamera.classList.remove('d-none');
          }
        }).catch(function () {});
      }).catch(function () {
        // Fallback: coba kamera default / webcam laptop
        Html5Qrcode.getCameras().then(function (devices) {
          if (!devices || devices.length === 0) {
            scannerWrapper.classList.add('d-none');
            showFeedback('danger', 'Tidak ditemukan perangkat kamera pada perangkat Anda. Silakan gunakan fitur <strong>Upload Gambar Barcode / QR</strong> atau ketik kode SKS secara manual.');
            return;
          }
          camerasList = devices;
          if (devices.length > 1) {
            btnSwitchCamera.classList.remove('d-none');
          }
          currentCamIdx = devices.length - 1;
          startScannerWithCamera(devices[currentCamIdx].id).catch(function () {
            scannerWrapper.classList.add('d-none');
            showFeedback('danger', 'Akses kamera ditolak oleh browser. Izinkan akses kamera pada ikon kunci di address bar browser Anda, atau gunakan <strong>Upload Gambar Barcode / QR</strong>.');
          });
        }).catch(function () {
          scannerWrapper.classList.add('d-none');
          showFeedback('danger', 'Izin kamera ditolak atau kamera sedang digunakan aplikasi lain. Silakan izinkan kamera di pengaturan browser atau gunakan tombol <strong>Upload Gambar Barcode / QR</strong>.');
        });
      });
    });
  }

  if (btnToggleScanner) {
    btnToggleScanner.addEventListener('click', function () {
      if (isScanning) {
        stopScanner();
      } else {
        openScanner();
      }
    });
  }

  if (btnCloseScanner) {
    btnCloseScanner.addEventListener('click', function () {
      stopScanner();
    });
  }

  if (btnSwitchCamera) {
    btnSwitchCamera.addEventListener('click', function () {
      if (!camerasList || camerasList.length < 2) return;
      currentCamIdx = (currentCamIdx + 1) % camerasList.length;
      var nextCamId = camerasList[currentCamIdx].id;
      stopScanner().then(function () {
        scannerWrapper.classList.remove('d-none');
        startScannerWithCamera(nextCamId);
      });
    });
  }

  if (btnUploadQr && qrFileInput) {
    btnUploadQr.addEventListener('click', function () {
      qrFileInput.value = '';
      qrFileInput.click();
    });

    qrFileInput.addEventListener('change', function (e) {
      var file = e.target.files && e.target.files[0];
      if (!file) return;

      hideFeedback();
      ensureQrLibrary(function (loaded) {
        if (!loaded) {
          showFeedback('danger', 'Gagal memuat modul pemindai. Silakan ketik kode SKS secara manual.');
          return;
        }
        showFeedback('info', 'Memindai gambar Barcode / QR Code...');
        stopScanner().then(function () {
          if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('qrReader');
          }
          html5QrCode.scanFile(file, false).then(function (decodedText) {
            isProcessingScan = false;
            handleDecodedCode(decodedText);
          }).catch(function () {
            showFeedback('danger', 'Tidak dapat menemukan Barcode atau QR Code yang jelas pada gambar tersebut. Pastikan gambar terang dan kode QR tidak terpotong.');
          });
        });
      });
    });
  }
})();
</script>

<?php require __DIR__ . '/../template/footer.php'; ?>
