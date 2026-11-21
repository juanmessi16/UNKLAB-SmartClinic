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
    <form method="get" action="<?= url('verifikasi/') ?>" class="d-flex flex-wrap gap-2">
      <input type="text" class="form-control form-control-lg mono flex-grow-1" name="kode" value="<?= e($kode) ?>" placeholder="Contoh: SKS-9F63-88B7" required>
      <button class="btn btn-navy btn-lg px-4" type="submit"><i class="bi bi-patch-check-fill me-1"></i>Verifikasi Sekarang</button>
    </form>
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

<?php require __DIR__ . '/../template/footer.php'; ?>
