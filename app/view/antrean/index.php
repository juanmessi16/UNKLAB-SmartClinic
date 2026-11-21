<?php
require __DIR__ . '/../template/header.php';
$live    = $data['live'];
$isStaff = $data['is_staff'];
$f       = $data['filter'];
$myQ     = $data['my_active'];
$role    = $data['user']['role'] ?? ($_SESSION['user-role'] ?? '');
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-ticket-perforated-fill text-teal me-2"></i><?= $isStaff ? 'Manajemen Antrean Klinik (FIFO)' : 'Antrean Berobat Saya' ?></h2>
    <p>Sistem antrean klinik UKESMAS yang melayani pasien secara berurutan sesuai nomor antrean (First-In, First-Out: <strong>A-001, A-002, A-003</strong>).</p>
  </div>
  <div class="d-flex gap-2">
    <?php if ($isStaff): ?>
      <form method="post" action="<?= url('antrean/panggil_berikutnya') ?>">
        <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
        <button class="btn btn-teal" type="submit"><i class="bi bi-megaphone-fill me-1"></i>Panggil Nomor Berikutnya</button>
      </form>
    <?php endif; ?>
    <?php if ($role !== 'medstaff'): ?>
      <a href="<?= url($role === 'student' ? 'dashboard/' : 'triase/') ?>" class="btn btn-teal"><i class="bi bi-plus-circle me-1"></i>Konsultasi AI &amp; Ambil Antrean</a>
    <?php endif; ?>
  </div>
</div>

<!-- Papan Monitor Live -->
<div class="queue-board mb-4">
  <div class="row g-3 align-items-center">
    <div class="col-md-4">
      <div class="lbl"><span class="live-dot"></span>Nomor Sedang Dipanggil</div>
      <div class="num mono"><?= e($live['dipanggil'] ?? '—') ?></div>
    </div>
    <div class="col-md-3">
      <div class="lbl">Antrean Berikutnya</div>
      <div class="fw-800 fs-2 text-navy mono"><?= e($live['berikutnya'] ?? '—') ?></div>
    </div>
    <div class="col-md-5">
      <div class="row text-center">
        <div class="col-4"><div class="fw-800 fs-3 text-navy"><?= (int)$live['menunggu'] ?></div><div class="lbl">Menunggu</div></div>
        <div class="col-4"><div class="fw-800 fs-3 text-navy"><?= (int)$live['selesai'] ?></div><div class="lbl">Selesai</div></div>
        <div class="col-4"><div class="fw-800 fs-3 text-teal"><?= (int)$live['estimasi_menit'] ?> mnt</div><div class="lbl">Est. Tunggu</div></div>
      </div>
    </div>
  </div>
</div>

<?php if (!$isStaff && $data['user']['role'] === 'student'): ?>
<!-- Tampilan Khusus Mahasiswa: Terpusat, Lega & Fokus pada Tiket Antrean Hari Ini -->
<div class="mx-auto" style="max-width:680px">
  <?php if ($myQ): ?>
    <div class="ticket p-4 p-md-5 shadow-sm" style="border-color:#cbd5e1;border-radius:22px">
      <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
        <span class="badge badge-soft-primary px-3 py-2"><i class="bi bi-ticket-perforated-fill me-1"></i>Tiket Antrean UKESMAS Hari Ini</span>
      </div>
      <div class="tn my-2 mono" style="font-size:3.6rem;letter-spacing:-.02em"><?= e($myQ['queue_number']) ?></div>
      <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
        <?= category_badge($myQ['triage_category'] ?: 'Keluhan Umum') ?>
        <?= queue_status_badge($myQ['status']) ?>
      </div>
      <p class="text-muted-2 mb-3 mx-auto" style="max-width:520px">&ldquo;<?= e($myQ['complaint']) ?>&rdquo;</p>
      <div class="p-3 rounded-4 mb-4 mx-auto" style="max-width:520px;background:<?= $myQ['status'] === 'Dipanggil' ? '#ecfdf5' : 'var(--slate-50)' ?>;border:1px solid <?= $myQ['status'] === 'Dipanggil' ? '#6ee7b7' : '#e2e8f0' ?>">
        <?php if ($myQ['status'] === 'Dipanggil'): ?>
          <div class="fw-bold text-success"><i class="bi bi-megaphone-fill me-2"></i>Nomor Anda sedang dipanggil! Silakan masuk ke ruang periksa UKESMAS.</div>
        <?php else: ?>
          <div class="fw-semibold text-navy"><i class="bi bi-people-fill text-gold me-2"></i>Posisi antrean: <strong><?= (int)$data['my_pos'] ?> pasien</strong> di depan Anda</div>
        <?php endif; ?>
      </div>
      <form method="post" action="<?= url('antrean/batal/' . $myQ['id']) ?>" data-confirm="Batalkan nomor antrean ini?">
        <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
        <button class="btn btn-outline-danger px-4" type="submit"><i class="bi bi-x-circle me-1"></i>Batalkan Antrean</button>
      </form>
    </div>
  <?php else: ?>
    <div class="card-x p-4 p-md-5 text-center">
      <div class="stat-icon blue mx-auto mb-3" style="width:64px;height:64px;font-size:1.7rem"><i class="bi bi-ticket-perforated-fill"></i></div>
      <h5 class="fw-800 mb-2">Belum Ada Nomor Antrean Hari Ini</h5>
      <p class="text-muted-2 small mx-auto mb-4" style="max-width:420px">Anda belum mengambil nomor antrean UKESMAS hari ini. Mulai konsultasi keluhan dengan AI terlebih dahulu untuk mendapatkan saran awal dan nomor antrean.</p>
      <a href="<?= url('dashboard/') ?>" class="btn btn-teal btn-lg px-4 fw-bold"><i class="bi bi-heart-pulse-fill me-2"></i>Konsultasi &amp; Ambil Antrean</a>
    </div>
  <?php endif; ?>
</div>

<?php elseif (!$isStaff): ?>
<!-- Tampilan Dosen -->
<div class="row g-4">
  <div class="col-lg-5">
    <?php if ($myQ): ?>
      <div class="ticket">
        <span class="badge badge-soft-primary mb-2">Tiket Antrean UKESMAS Hari Ini</span>
        <div class="tn my-2"><?= e($myQ['queue_number']) ?></div>
        <div class="mb-2"><?= category_badge($myQ['triage_category'] ?: 'Keluhan Umum') ?> <?= queue_status_badge($myQ['status']) ?></div>
        <p class="small text-muted-2 mb-2">&ldquo;<?= e($myQ['complaint']) ?>&rdquo;</p>
        <div class="p-2 rounded-3 mb-3" style="background:var(--slate-50)">
          <?php if ($myQ['status'] === 'Dipanggil'): ?>
            <div class="fw-bold text-success"><i class="bi bi-megaphone-fill me-1"></i>Nomor Anda sedang dipanggil! Silakan masuk ke ruang periksa.</div>
          <?php else: ?>
            <div class="small">Posisi antrean: <strong><?= (int)$data['my_pos'] ?> pasien</strong> di depan Anda</div>
          <?php endif; ?>
        </div>
        <form method="post" action="<?= url('antrean/batal/' . $myQ['id']) ?>" data-confirm="Batalkan nomor antrean ini?">
          <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
          <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-x-circle me-1"></i>Batalkan Antrean</button>
        </form>
      </div>
    <?php else: ?>
      <div class="card-x p-4 text-center">
        <div class="stat-icon blue mx-auto mb-3"><i class="bi bi-ticket-perforated"></i></div>
        <h6>Anda Belum Memiliki Antrean Aktif Hari Ini</h6>
        <p class="small text-muted-2">Lakukan konsultasi keluhan dengan AI untuk mendapatkan saran penanganan awal dan mengambil nomor antrean UKESMAS.</p>
        <a href="<?= url($data['user']['role'] === 'student' ? 'dashboard/' : 'triase/') ?>" class="btn btn-gold"><i class="bi bi-heart-pulse-fill me-1"></i>Konsultasi AI &amp; Ambil Nomor</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-7">
    <div class="card-x">
      <div class="card-head"><h6 class="section-title">Riwayat Pengambilan Antrean Saya</h6></div>
      <div class="card-body-x">
        <?php if ($data['my_history']): ?>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead><tr><th>Tanggal</th><th>Nomor</th><th>Keluhan</th><th>Kategori AI</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($data['my_history'] as $h): ?>
                <tr>
                  <td class="small"><?= e(tgl($h['queue_date'])) ?></td>
                  <td class="mono fw-bold"><?= e($h['queue_number']) ?></td>
                  <td class="small"><?= e(mb_strimwidth($h['complaint'], 0, 60, '…')) ?></td>
                  <td><?= category_badge($h['triage_category'] ?: 'Umum') ?></td>
                  <td><?= queue_status_badge($h['status']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state"><i class="bi bi-inbox"></i>Belum ada riwayat antrean.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- Tampilan Petugas Medis / Admin -->
<form class="filter-bar" method="get" action="<?= url('antrean/') ?>">
  <div>
    <label class="form-label">Tanggal Antrean</label>
    <input type="date" class="form-control" name="tanggal" value="<?= e($f['tanggal']) ?>">
  </div>
  <div>
    <label class="form-label">Status</label>
    <select class="form-select" name="status">
      <option value="">Semua Status</option>
      <?php foreach (['Menunggu', 'Dipanggil', 'Selesai', 'Dibatalkan'] as $st): ?>
        <option value="<?= $st ?>" <?= $f['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-navy" type="submit"><i class="bi bi-funnel-fill me-1"></i>Tampilkan</button>
    <a class="btn btn-soft" href="<?= url('antrean/') ?>">Hari Ini</a>
  </div>
</form>

<div class="table-wrap">
  <div class="table-responsive">
    <table class="table table-modern">
      <thead>
        <tr>
          <th>Nomor</th>
          <th>Pasien &amp; NIM</th>
          <th>Keluhan &amp; Ringkasan AI</th>
          <th>Kategori AI</th>
          <th>Status</th>
          <th>Aksi Petugas</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($data['queues']): foreach ($data['queues'] as $q):
          $tj = json_decode($q['triage_json'] ?? '', true) ?: [];
          $ringkasan = $tj['ringkasan_dokter'] ?? ($tj['anamnesis_summary'] ?? '');
        ?>
        <tr>
          <td>
            <div class="mono fw-800 fs-5 text-navy"><?= e($q['queue_number']) ?></div>
            <div class="small text-muted-2"><?= e(date('H:i', strtotime($q['created_at']))) ?></div>
          </td>
          <td>
            <div class="fw-bold text-navy"><?= e($q['nama']) ?></div>
            <div class="small text-muted-2">NIM: <?= e($q['nim_nip']) ?></div>
          </td>
          <td style="max-width:340px">
            <div class="fw-semibold"><?= e($q['complaint']) ?></div>
            <?php if ($ringkasan !== ''): ?>
              <div class="small text-muted-2 mt-1"><i class="bi bi-robot text-gold me-1"></i><?= e(mb_strimwidth($ringkasan, 0, 140, '…')) ?></div>
            <?php endif; ?>
          </td>
          <td><?= category_badge($q['triage_category'] ?: ($tj['kategori'] ?? 'Umum')) ?></td>
          <td><?= queue_status_badge($q['status']) ?></td>
          <td>
            <div class="d-flex flex-wrap gap-1">
              <?php if ($q['status'] === 'Menunggu'): ?>
                <form method="post" action="<?= url('antrean/panggil/' . $q['id']) ?>">
                  <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
                  <button class="btn btn-soft btn-sm" type="submit" title="Panggil"><i class="bi bi-megaphone-fill"></i> Panggil</button>
                </form>
              <?php endif; ?>
              <?php if (in_array($q['status'], ['Menunggu', 'Dipanggil', 'Diperiksa'])): ?>
                <a class="btn btn-gold btn-sm" href="<?= url('emr/periksa/' . $q['id']) ?>"><i class="bi bi-clipboard2-pulse-fill"></i> Periksa &amp; EMR</a>
                <form method="post" action="<?= url('antrean/batal/' . $q['id']) ?>" data-confirm="Batalkan antrean ini?">
                  <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
                  <button class="btn btn-soft btn-sm text-danger" type="submit" title="Batalkan"><i class="bi bi-x-lg"></i></button>
                </form>
              <?php else: ?>
                <a class="btn btn-soft btn-sm" href="<?= url('pasien/detail/' . $q['patient_id']) ?>">Riwayat</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6"><div class="empty-state"><i class="bi bi-calendar-x"></i>Tidak ada antrean untuk filter tanggal/status tersebut.</div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../template/footer.php'; ?>
