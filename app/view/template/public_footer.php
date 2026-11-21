<footer class="footer-public mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5">
        <div class="d-flex align-items-center gap-2 mb-2 text-navy fw-bold fs-5">
          <img src="<?= asset('img/unklab-logo.png') ?>" alt="Logo Universitas Klabat" style="width:36px;height:36px;object-fit:contain;flex-shrink:0;">
          <span>UNKLAB <span class="text-teal">SmartClinic</span></span>
        </div>
        <p class="small mb-0 text-muted-2">Portal layanan kesehatan kampus UKESMAS Universitas Klabat untuk konsultasi keluhan awal, antrean klinik terpadu, rekam medis elektronik, dan verifikasi surat keterangan sakit.</p>
      </div>
      <div class="col-6 col-lg-3">
        <div class="text-navy fw-bold mb-2 small text-uppercase" style="letter-spacing:.05em">Akses Cepat</div>
        <div class="d-grid gap-1 small">
          <a href="<?= url('login/') ?>">Masuk Sistem</a>
          <a href="<?= url('verifikasi/') ?>">Verifikasi Surat Sakit</a>
          <a href="<?= url('dokumentasi/') ?>">Dokumentasi &amp; Analisis Sistem</a>
        </div>
      </div>
      <div class="col-6 col-lg-4">
        <div class="text-navy fw-bold mb-2 small text-uppercase" style="letter-spacing:.05em">Kontak UKESMAS</div>
        <div class="small text-muted-2">Universitas Klabat, Airmadidi, Minahasa Utara<br>ukesmas@unklab.ac.id &middot; (0431) 891035 ext. 112</div>
      </div>
    </div>
    <hr class="my-4" style="border-color:var(--slate-200)">
    <div class="small text-muted-2">&copy; <?= date('Y') ?> UKESMAS Universitas Klabat. Informasi pada sistem ini bersifat edukatif dan bukan pengganti pemeriksaan medis langsung oleh dokter.</div>
  </div>
</footer>
<?php require __DIR__ . '/chat_widget.php'; ?>
<script>window.APP_BASE = <?= json_encode(rtrim(APP_PATH, '/')) ?>;</script>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>?v=<?= APP_VERSION ?>.3"></script>
<?= $data['extra_js'] ?? '' ?>
</body>
</html>
