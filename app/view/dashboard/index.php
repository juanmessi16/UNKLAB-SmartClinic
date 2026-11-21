<?php
require __DIR__ . '/../template/header.php';
$u       = $data['user'];
$role    = $u['role'];
$ov      = $data['overview'];
$live    = $data['live_queue'];
$gem     = $data['gemini_status'];
$pat     = $data['patient_profile'];
$myQ     = $data['my_active_queue'];
$quota   = $data['my_quota'];
$isStaff = in_array($role, ['superadmin', 'clinicadmin', 'medstaff']);
?>

<!-- Welcome Banner Role-Based (Clean Clinical Theme) -->
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
      <h2>Halo, <?= e($u['nama']) ?>!</h2>
      <?php if ($role === 'student'): ?>
        <p>Merasa kurang enak badan? Gunakan <strong>Konsultasi Keluhan</strong> untuk mendapatkan saran penanganan awal dan nomor antrean UKESMAS.</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('triase/') ?>" class="btn btn-teal"><i class="bi bi-heart-pulse-fill me-1"></i>Konsultasi &amp; Ambil Antrean</a>
          <a href="<?= url('suratsakit/') ?>" class="btn btn-outline-clinical"><i class="bi bi-file-earmark-medical me-1"></i>Surat Sakit Saya</a>
          <button type="button" class="btn btn-outline-clinical" data-open-chat><i class="bi bi-heart-pulse me-1"></i>Konsultasi AI 24 Jam</button>
        </div>
      <?php elseif ($role === 'lecturer'): ?>
        <p>Verifikasi keaslian Surat Keterangan Sakit mahasiswa secara instan menggunakan kode unik SKS, atau gunakan layanan pemeriksaan kesehatan dosen.</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('verifikasi/') ?>" class="btn btn-teal"><i class="bi bi-patch-check-fill me-1"></i>Verifikasi Surat Sakit Mahasiswa</a>
          <a href="<?= url('triase/') ?>" class="btn btn-outline-clinical"><i class="bi bi-heart-pulse me-1"></i>Konsultasi &amp; Berobat</a>
        </div>
      <?php elseif ($role === 'medstaff'): ?>
        <p>Terdapat <strong><?= (int)$ov['antrean_aktif'] ?> antrean aktif</strong> hari ini. Panggil pasien berikutnya sesuai urutan antrean (FIFO) dan catat EMR beserta resep digital.</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('antrean/') ?>" class="btn btn-teal"><i class="bi bi-megaphone-fill me-1"></i>Kelola &amp; Panggil Antrean</a>
          <a href="<?= url('emr/') ?>" class="btn btn-outline-clinical"><i class="bi bi-journal-medical me-1"></i>Rekam Medis (EMR)</a>
        </div>
      <?php elseif ($role === 'clinicadmin'): ?>
        <p>Pantau tren kunjungan kampus, kelola <strong><?= (int)$ov['obat_kritis'] ?> obat berstatus stok kritis</strong>, dan jalankan proyeksi kebutuhan stok apotek.</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('analitik/') ?>" class="btn btn-teal"><i class="bi bi-graph-up-arrow me-1"></i>Analitik &amp; Proyeksi Stok</a>
          <a href="<?= url('obat/') ?>" class="btn btn-outline-clinical"><i class="bi bi-capsule-pill me-1"></i>Inventaris Obat</a>
          <a href="<?= url('laporan/') ?>" class="btn btn-outline-clinical"><i class="bi bi-bar-chart-line me-1"></i>Laporan Klinik</a>
        </div>
      <?php elseif ($role === 'medstaff'): ?>
        <a class="qa-btn" href="<?= url('antrean/') ?>"><i class="bi bi-megaphone-fill"></i><span>Panggil Antrean</span></a>
        <a class="qa-btn" href="<?= url('emr/') ?>"><i class="bi bi-journal-medical"></i><span>Rekam Medis (EMR)</span></a>
        <a class="qa-btn" href="<?= url('suratsakit/') ?>"><i class="bi bi-file-earmark-medical-fill"></i><span>Surat Sakit Digital</span></a>
        <a class="qa-btn" href="<?= url('obat/') ?>"><i class="bi bi-capsule-pill"></i><span>Stok Obat &amp; Apotek</span></a>
        <a class="qa-btn" href="<?= url('jadwal/') ?>"><i class="bi bi-calendar2-week-fill"></i><span>Jadwal Dokter</span></a>
        <a class="qa-btn" href="<?= url('verifikasi/') ?>"><i class="bi bi-patch-check-fill"></i><span>Verifikasi SKS</span></a>
      <?php else: ?>
        <p>Panel kendali penuh UNKLAB SmartClinic: pantau operasional klinik, analitik kunjungan &amp; stok obat, manajemen pengguna 5 role, dan perlindungan WAF.</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('analitik/') ?>" class="btn btn-teal"><i class="bi bi-graph-up-arrow me-1"></i>Analitik &amp; Forecasting</a>
          <a href="<?= url('pengaturan/') ?>" class="btn btn-outline-clinical"><i class="bi bi-sliders me-1"></i>Pengaturan Sistem</a>
          <a href="<?= url('keamanan/') ?>" class="btn btn-outline-clinical"><i class="bi bi-shield-lock me-1"></i>Monitor WAF</a>
        </div>
      <?php endif; ?>
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

<!-- 4 Stat Cards Utama -->
<?php if ($role === 'student'): ?>
<div class="row g-3 mb-4">
  <div class="col-md-4 col-sm-6">
    <div class="card-x stat-card hover-lift">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Antrean Aktif Saya</div>
          <div class="stat-value mt-1"><?= $myQ ? e($myQ['queue_number']) : '—' ?></div>
          <div class="small text-muted-2 mt-1"><?= $myQ ? e($myQ['status']) . ' · ' . ($data['my_queue_pos'] > 0 ? $data['my_queue_pos'] . ' orang di depan' : 'Segera ke klinik') : 'Belum mengambil antrean' ?></div>
        </div>
        <div class="stat-icon blue"><i class="bi bi-ticket-perforated-fill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-sm-6">
    <div class="card-x stat-card hover-lift">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Riwayat Kunjungan</div>
          <div class="stat-value mt-1"><?= count($data['my_records']) ?></div>
          <div class="small text-muted-2 mt-1">Rekam medis tercatat</div>
        </div>
        <div class="stat-icon emerald"><i class="bi bi-clipboard2-pulse-fill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-sm-12">
    <div class="card-x stat-card hover-lift">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Sisa Kuota Izin Sakit</div>
          <div class="stat-value mt-1"><?= (int)$quota['sisa'] ?> <span class="fs-6 fw-normal text-muted-2">/ <?= (int)$quota['kuota'] ?> hari</span></div>
          <div class="small text-muted-2 mt-1">Terpakai <?= (int)$quota['terpakai'] ?> hari semester ini</div>
        </div>
        <div class="stat-icon gold"><i class="bi bi-file-earmark-medical-fill"></i></div>
      </div>
    </div>
  </div>
</div>
<?php else: ?>
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card-x stat-card hover-lift">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Kunjungan Hari Ini</div>
          <div class="stat-value mt-1"><?= (int)$ov['kunjungan_hari_ini'] ?></div>
          <div class="small text-muted-2 mt-1"><i class="bi bi-calendar-week me-1"></i>7 hari terakhir: <strong><?= (int)$ov['kunjungan_7_hari'] ?></strong> pasien</div>
        </div>
        <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card-x stat-card hover-lift">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Antrean Aktif</div>
          <div class="stat-value mt-1"><?= (int)$ov['antrean_aktif'] ?></div>
          <div class="small text-muted-2 mt-1">Dipanggil: <strong><?= e($live['dipanggil'] ?? '—') ?></strong></div>
        </div>
        <div class="stat-icon gold"><i class="bi bi-ticket-perforated-fill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card-x stat-card hover-lift">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Stok Obat Kritis</div>
          <div class="stat-value mt-1 <?= $ov['obat_kritis'] > 0 ? 'text-danger' : '' ?>"><?= (int)$ov['obat_kritis'] ?></div>
          <div class="small text-muted-2 mt-1"><a href="<?= url('obat/?status=kritis') ?>">Perlu restock segera &rarr;</a></div>
        </div>
        <div class="stat-icon rose"><i class="bi bi-capsule-pill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card-x stat-card hover-lift">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Surat Sakit Aktif</div>
          <div class="stat-value mt-1"><?= (int)$ov['surat_aktif'] ?></div>
          <div class="small text-muted-2 mt-1">Total EMR: <strong><?= (int)$ov['total_rekam'] ?></strong> rekam</div>
        </div>
        <div class="stat-icon emerald"><i class="bi bi-patch-check-fill"></i></div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Tombol Aksi Cepat -->
<div class="card-x mb-4">
  <div class="card-head">
    <div>
      <h6 class="section-title"><i class="bi bi-lightning-charge-fill text-gold me-1"></i>Aksi Cepat</h6>
      <div class="section-sub">Pintasan fitur utama sesuai peran Anda</div>
    </div>
  </div>
  <div class="card-body-x">
    <div class="quick-actions">
      <?php if ($role === 'student'): ?>
        <a class="qa-btn" href="<?= url('triase/') ?>"><i class="bi bi-heart-pulse-fill"></i><span>Triase AI Gejala</span></a>
        <a class="qa-btn" href="<?= url('antrean/') ?>"><i class="bi bi-ticket-perforated-fill"></i><span>Antrean Saya</span></a>
        <a class="qa-btn" href="<?= url('emr/riwayat') ?>"><i class="bi bi-clipboard2-pulse-fill"></i><span>Riwayat Medis</span></a>
        <a class="qa-btn" href="<?= url('suratsakit/') ?>"><i class="bi bi-file-earmark-medical-fill"></i><span>Surat Sakit</span></a>
        <a class="qa-btn" href="<?= url('pasien/profil') ?>"><i class="bi bi-person-heart"></i><span>Profil Kesehatan</span></a>
        <a class="qa-btn" href="<?= url('pencarian/') ?>"><i class="bi bi-search"></i><span>Cari Obat &amp; P3K</span></a>
      <?php elseif ($role === 'lecturer'): ?>
        <a class="qa-btn" href="<?= url('verifikasi/') ?>"><i class="bi bi-qr-code-scan"></i><span>Verifikasi Surat Sakit</span></a>
        <a class="qa-btn" href="<?= url('triase/') ?>"><i class="bi bi-heart-pulse-fill"></i><span>Konsultasi AI</span></a>
        <a class="qa-btn" href="<?= url('antrean/') ?>"><i class="bi bi-ticket-perforated-fill"></i><span>Antrean Berobat</span></a>
        <a class="qa-btn" href="<?= url('emr/riwayat') ?>"><i class="bi bi-clipboard2-pulse-fill"></i><span>Riwayat Medis</span></a>
        <a class="qa-btn" href="<?= url('jadwal/') ?>"><i class="bi bi-calendar2-week-fill"></i><span>Jadwal Dokter</span></a>
        <a class="qa-btn" href="<?= url('dokumentasi/') ?>"><i class="bi bi-diagram-3-fill"></i><span>Dokumentasi Sistem</span></a>
      <?php elseif ($role === 'medstaff'): ?>
        <a class="qa-btn" href="<?= url('antrean/') ?>"><i class="bi bi-megaphone-fill"></i><span>Panggil Antrean</span></a>
        <a class="qa-btn" href="<?= url('emr/') ?>"><i class="bi bi-journal-medical"></i><span>Rekam Medis (EMR)</span></a>
        <a class="qa-btn" href="<?= url('suratsakit/') ?>"><i class="bi bi-file-earmark-medical-fill"></i><span>Surat Sakit Digital</span></a>
        <a class="qa-btn" href="<?= url('obat/') ?>"><i class="bi bi-capsule-pill"></i><span>Stok Obat &amp; Apotek</span></a>
        <a class="qa-btn" href="<?= url('jadwal/') ?>"><i class="bi bi-calendar2-week-fill"></i><span>Jadwal Dokter</span></a>
        <a class="qa-btn" href="<?= url('verifikasi/') ?>"><i class="bi bi-patch-check-fill"></i><span>Verifikasi SKS</span></a>
      <?php else: ?>
        <a class="qa-btn" href="<?= url('antrean/') ?>"><i class="bi bi-megaphone-fill"></i><span>Panggil Antrean</span></a>
        <a class="qa-btn" href="<?= url('emr/') ?>"><i class="bi bi-journal-medical"></i><span>Rekam Medis (EMR)</span></a>
        <a class="qa-btn" href="<?= url('obat/') ?>"><i class="bi bi-capsule-pill"></i><span>Stok &amp; Restock Obat</span></a>
        <a class="qa-btn" href="<?= url('suratsakit/') ?>"><i class="bi bi-file-earmark-medical-fill"></i><span>Surat Sakit Digital</span></a>
        <a class="qa-btn" href="<?= url('verifikasi/') ?>"><i class="bi bi-patch-check-fill"></i><span>Verifikasi Kode SKS</span></a>
        <a class="qa-btn" href="<?= url('analitik/') ?>"><i class="bi bi-graph-up-arrow"></i><span>AI Forecasting</span></a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($role === 'student'): ?>
<!-- Panel khusus Mahasiswa -->
<div class="row g-4 mb-4">
  <div class="col-lg-5">
    <div class="card-x h-100">
      <div class="card-head">
        <h6 class="section-title"><i class="bi bi-person-vcard text-gold me-1"></i>Ringkasan Profil Kesehatan</h6>
        <a href="<?= url('pasien/profil') ?>" class="btn btn-soft btn-sm">Ubah</a>
      </div>
      <div class="card-body-x">
        <?php if ($pat): ?>
          <div class="mb-3"><span class="text-muted-2 small">Nomor Induk Mahasiswa (NIM)</span><div class="fw-bold fs-5 text-navy"><?= e($pat['nim_nip']) ?></div></div>
          <hr>
          <div class="d-flex justify-content-between small mb-1"><span>Kuota Izin Sakit Semester</span><strong><?= (int)$quota['terpakai'] ?> / <?= (int)$quota['kuota'] ?> hari</strong></div>
          <div class="progress"><div class="progress-bar bg-warning" style="width:<?= min(100, round($quota['terpakai']*100/max(1,$quota['kuota']))) ?>%"></div></div>
        <?php else: ?>
          <div class="empty-state"><i class="bi bi-person-exclamation"></i>Profil pasien belum terhubung.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card-x h-100">
      <div class="card-head">
        <h6 class="section-title"><i class="bi bi-clipboard2-pulse text-gold me-1"></i>Riwayat Pemeriksaan &amp; Surat Sakit</h6>
        <a href="<?= url('emr/riwayat') ?>" class="btn btn-soft btn-sm">Lihat Semua</a>
      </div>
      <div class="card-body-x">
        <?php if ($data['my_records']): ?>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead><tr><th>Tanggal</th><th>Diagnosis</th><th>Tanda Vital</th><th>Aksi</th></tr></thead>
              <tbody>
                <?php foreach ($data['my_records'] as $r): ?>
                <tr>
                  <td><?= e(tgl($r['visit_date'], true)) ?></td>
                  <td><div class="fw-bold text-navy"><?= e($r['diagnosis']) ?></div><span class="badge badge-soft-primary"><?= e($r['kategori_penyakit']) ?></span></td>
                  <td class="small"><?= e($r['tekanan_sistolik']) ?>/<?= e($r['tekanan_diastolik']) ?> mmHg &middot; <?= e($r['suhu']) ?>&deg;C</td>
                  <td><a class="btn btn-soft btn-sm" href="<?= url('emr/detail/' . $r['id']) ?>">Detail</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php elseif ($role === 'medstaff'): ?>
        <a class="qa-btn" href="<?= url('antrean/') ?>"><i class="bi bi-megaphone-fill"></i><span>Panggil Antrean</span></a>
        <a class="qa-btn" href="<?= url('emr/') ?>"><i class="bi bi-journal-medical"></i><span>Rekam Medis (EMR)</span></a>
        <a class="qa-btn" href="<?= url('suratsakit/') ?>"><i class="bi bi-file-earmark-medical-fill"></i><span>Surat Sakit Digital</span></a>
        <a class="qa-btn" href="<?= url('obat/') ?>"><i class="bi bi-capsule-pill"></i><span>Stok Obat &amp; Apotek</span></a>
        <a class="qa-btn" href="<?= url('jadwal/') ?>"><i class="bi bi-calendar2-week-fill"></i><span>Jadwal Dokter</span></a>
        <a class="qa-btn" href="<?= url('verifikasi/') ?>"><i class="bi bi-patch-check-fill"></i><span>Verifikasi SKS</span></a>
      <?php else: ?>
          <div class="empty-state"><i class="bi bi-journal-x"></i>Belum ada riwayat pemeriksaan.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($role === 'lecturer'): ?>
<!-- Panel cepat Verifikasi Surat Sakit untuk Dosen -->
<div class="row g-4 mb-4">
  <div class="col-lg-5">
    <div class="card-x h-100">
      <div class="card-head"><h6 class="section-title"><i class="bi bi-patch-check-fill text-gold me-1"></i>Cek Cepat Kode Surat Sakit</h6></div>
      <div class="card-body-x">
        <p class="small text-muted-2">Masukkan kode verifikasi yang tertera pada Surat Keterangan Sakit mahasiswa (contoh: <code>SKS-....</code>).</p>
        <form action="<?= url('verifikasi/') ?>" method="get" class="d-flex gap-2">
          <input type="text" name="kode" class="form-control mono" placeholder="SKS-XXXX-XXXX" required>
          <button class="btn btn-navy" type="submit"><i class="bi bi-search me-1"></i>Verifikasi</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card-x h-100">
      <div class="card-head"><h6 class="section-title"><i class="bi bi-file-earmark-medical text-gold me-1"></i>Surat Keterangan Sakit Terbaru Diterbitkan</h6></div>
      <div class="card-body-x">
        <div class="table-responsive">
          <table class="table table-modern">
            <thead><tr><th>Kode</th><th>Mahasiswa</th><th>Periode Izin</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($data['recent_leaves'] as $s): ?>
              <tr>
                <td class="mono fw-bold"><?= e($s['kode_verifikasi']) ?></td>
                <td><?= e($s['nama']) ?><div class="small text-muted-2"><?= e($s['nim_nip']) ?></div></td>
                <td class="small"><?= e(tgl($s['tanggal_mulai'])) ?> &ndash; <?= e(tgl($s['tanggal_selesai'])) ?> (<?= (int)$s['jumlah_hari'] ?> hr)</td>
                <td><a href="<?= url('verifikasi/?kode=' . urlencode($s['kode_verifikasi'])) ?>" class="btn btn-soft btn-sm">Cek</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Grafik Analitik (Chart.js) -->
<div class="row g-4 mb-4">
  <div class="col-lg-7">
    <div class="card-x h-100">
      <div class="card-head">
        <div>
          <h6 class="section-title"><i class="bi bi-activity text-gold me-1"></i>Tren Kunjungan Pasien</h6>
          <div class="section-sub">Perbandingan kunjungan harian (14 hari terakhir) &amp; bulanan (6 bulan)</div>
        </div>
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" class="btn btn-navy" id="btnDaily">14 Hari</button>
          <button type="button" class="btn btn-soft" id="btnMonthly">6 Bulan</button>
        </div>
      </div>
      <div class="card-body-x">
        <div class="chart-box"><canvas id="chartTrend"></canvas></div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-x h-100">
      <div class="card-head">
        <div>
          <h6 class="section-title"><i class="bi bi-pie-chart-fill text-gold me-1"></i>Distribusi Kategori Penyakit</h6>
          <div class="section-sub">14 hari terakhir (lonjakan ISPA, Demam &amp; Gastritis)</div>
        </div>
      </div>
      <div class="card-body-x">
        <div class="chart-box"><canvas id="chartDisease"></canvas></div>
      </div>
    </div>
  </div>
</div>

<!-- Peringatan Stok Obat Kritis -->
<div class="card-x mb-4">
  <div class="card-head">
    <div>
      <h6 class="section-title"><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Peringatan Stok Obat Kritis</h6>
      <div class="section-sub">Obat di bawah batas minimum apotek UKESMAS</div>
    </div>
    <?php if ($isStaff): ?><a href="<?= url('obat/') ?>" class="btn btn-soft btn-sm">Kelola Obat</a><?php endif; ?>
  </div>
  <div class="card-body-x">
    <?php if ($data['critical_meds']): ?>
      <div class="row g-3">
        <?php foreach ($data['critical_meds'] as $m):
          $pct = min(100, round(($m['stok'] / max(1, $m['stok_minimum'])) * 100));
        ?>
          <div class="col-md-4 col-sm-6">
            <div class="p-2 px-3 rounded-3 border d-flex align-items-center justify-content-between gap-2 h-100" style="background:#F8FAFC">
              <div class="flex-grow-1">
                <div class="fw-bold text-navy"><?= e($m['nama']) ?> <span class="text-muted-2 small">(<?= e($m['kode']) ?>)</span></div>
                <div class="progress mt-1" style="height:6px"><div class="progress-bar bg-danger" style="width:<?= $pct ?>%"></div></div>
              </div>
              <div class="text-end ps-2">
                <div class="fw-800 text-danger"><?= (int)$m['stok'] ?> <span class="small fw-normal text-muted-2">/ min <?= (int)$m['stok_minimum'] ?> <?= e($m['satuan']) ?></span></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state py-3"><i class="bi bi-check2-circle text-success"></i>Seluruh stok obat berada di atas batas minimum.</div>
    <?php endif; ?>
  </div>
</div>

<?php if ($isStaff): ?>
<!-- Antrean Aktif & Kunjungan Terakhir untuk Petugas Medis / Admin -->
<div class="row g-4">
  <div class="col-lg-6">
    <div class="card-x h-100">
      <div class="card-head">
        <div>
          <h6 class="section-title"><i class="bi bi-people-fill text-teal me-1"></i>Antrean Aktif Hari Ini</h6>
          <div class="section-sub">Diurutkan berurutan sesuai nomor antrean (FIFO)</div>
        </div>
        <a href="<?= url('antrean/') ?>" class="btn btn-teal btn-sm">Buka Antrean</a>
      </div>
      <div class="card-body-x">
        <?php if ($data['active_queue']): ?>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead><tr><th>Nomor</th><th>Pasien</th><th>Kategori AI</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody>
                <?php foreach ($data['active_queue'] as $q): ?>
                <tr>
                  <td class="mono fw-800 text-navy"><?= e($q['queue_number']) ?></td>
                  <td>
                    <div class="fw-bold"><?= e($q['nama']) ?></div>
                    <div class="small text-muted-2 text-truncate" style="max-width:190px"><?= e($q['complaint']) ?></div>
                  </td>
                  <td><?= category_badge($q['triage_category'] ?: 'Umum') ?></td>
                  <td><?= queue_status_badge($q['status']) ?></td>
                  <td><a href="<?= url('emr/periksa/' . $q['id']) ?>" class="btn btn-teal btn-sm">Periksa</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state"><i class="bi bi-inbox"></i>Tidak ada pasien yang sedang mengantre.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card-x h-100">
      <div class="card-head">
        <div>
          <h6 class="section-title"><i class="bi bi-clock-history text-teal me-1"></i>Pemeriksaan Terakhir</h6>
          <div class="section-sub">Rekam medis yang baru diselesaikan</div>
        </div>
        <a href="<?= url('emr/') ?>" class="btn btn-soft btn-sm">Semua EMR</a>
      </div>
      <div class="card-body-x">
        <div class="table-responsive">
          <table class="table table-modern">
            <thead><tr><th>Waktu</th><th>Pasien</th><th>Diagnosis</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($data['recent_visits'] as $v): ?>
              <tr>
                <td class="small"><?= e(tgl($v['visit_date'], true)) ?></td>
                <td><div class="fw-bold"><?= e($v['nama']) ?></div><div class="small text-muted-2">NIM: <?= e($v['nim_nip']) ?></div></td>
                <td><div class="fw-semibold text-navy"><?= e($v['diagnosis']) ?></div><span class="badge badge-soft-primary"><?= e($v['kategori_penyakit']) ?></span></td>
                <td><a href="<?= url('emr/detail/' . $v['id']) ?>" class="btn btn-soft btn-sm">Detail</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$dailyJson   = json_encode($data['daily_trend']);
$monthlyJson = json_encode($data['monthly_trend']);
$distJson    = json_encode($data['disease_dist']);

$data['extra_js'] = <<<HTML
<script>
(function(){
  if (typeof Chart === 'undefined') return;
  var daily = $dailyJson, monthly = $monthlyJson, dist = $distJson;
  var palette = ['#0f766e','#0284c7','#059669','#d97706','#0d9488','#6366f1','#dc2626','#64748b'];

  // 1. Trend chart
  var ctxT = document.getElementById('chartTrend');
  var chartT = ctxT ? new Chart(ctxT, {
    type: 'line',
    data: {
      labels: daily.labels,
      datasets: [{
        label: 'Kunjungan Pasien',
        data: daily.values,
        borderColor: '#0f766e',
        backgroundColor: 'rgba(15,118,110,.1)',
        tension: .35,
        fill: true,
        pointBackgroundColor: '#0284c7',
        pointRadius: 4
      }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  }) : null;

  var bD = document.getElementById('btnDaily'), bM = document.getElementById('btnMonthly');
  if (bD && bM && chartT) {
    bD.addEventListener('click', function(){
      bD.className = 'btn btn-teal'; bM.className = 'btn btn-soft';
      chartT.data.labels = daily.labels; chartT.data.datasets[0].data = daily.values; chartT.update();
    });
    bM.addEventListener('click', function(){
      bM.className = 'btn btn-teal'; bD.className = 'btn btn-soft';
      chartT.data.labels = monthly.labels; chartT.data.datasets[0].data = monthly.values; chartT.update();
    });
  }

  // 2. Disease doughnut
  var ctxD = document.getElementById('chartDisease');
  if (ctxD) {
    new Chart(ctxD, {
      type: 'doughnut',
      data: { labels: dist.labels, datasets: [{ data: dist.values, backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } }, cutout: '62%' }
    });
  }
})();
</script>
HTML;
require __DIR__ . '/../template/footer.php';
