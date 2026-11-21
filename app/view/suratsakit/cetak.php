<?php
require __DIR__ . '/../template/header.php';
$s  = $data['leave'];
$st = Emr_model::leaveState($s);
$verifyUrl = url('verifikasi/?kode=' . urlencode($s['kode_verifikasi']));
$qrUrl     = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($verifyUrl);
?>

<div class="page-head no-print">
  <div>
    <h2><i class="bi bi-printer-fill text-gold me-2"></i>Cetak Surat Keterangan Sakit Digital</h2>
    <p>Kode Verifikasi: <strong class="mono"><?= e($s['kode_verifikasi']) ?></strong></p>
  </div>
  <div class="d-flex gap-2 page-head-actions">
    <button type="button" class="btn btn-gold flex-fill" onclick="window.print()"><i class="bi bi-printer-fill me-1"></i>Cetak / Simpan PDF</button>
    <a href="<?= url('suratsakit/') ?>" class="btn btn-soft flex-fill text-center"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
  </div>
</div>

<div class="letter">
  <div class="kop">
    <img src="<?= asset('img/unklab-logo.png') ?>" alt="Logo Universitas Klabat" class="kop-logo" style="width:68px;height:68px;object-fit:contain;flex-shrink:0;">
    <div class="flex-grow-1 kop-body">
      <div class="fw-800 fs-4 text-navy kop-title">UNIT KESEHATAN MAHASISWA (UKESMAS)</div>
      <div class="fw-bold text-navy kop-subtitle">UNIVERSITAS KLABAT (UNKLAB)</div>
      <div class="small text-muted-2 kop-address">Jl. Arnold Mononutu, Airmadidi, Minahasa Utara, Sulawesi Utara 95371 &middot; Telp. (0431) 891035 ext. 112</div>
    </div>
    <div class="text-end kop-stamp">
      <span class="stamp-valid"><?= e(strtoupper($st[0])) ?></span>
    </div>
  </div>

  <div class="text-center my-4 letter-heading">
    <h4 class="fw-800 text-decoration-underline mb-1 letter-doc-title">SURAT KETERANGAN SAKIT</h4>
    <div class="mono small">Nomor / Kode Validasi: <strong><?= e($s['kode_verifikasi']) ?></strong></div>
  </div>

  <p>Yang bertanda tangan di bawah ini, Dokter Pemeriksa pada Klinik UKESMAS Universitas Klabat, menerangkan bahwa:</p>

  <table class="my-3 ms-md-3 letter-identitas-table">
    <tr><td class="label-col">Nama Lengkap</td><td class="sep-col">:</td><td class="val-col"><strong><?= e($s['nama']) ?></strong></td></tr>
    <tr><td class="label-col">NIM / NIP</td><td class="sep-col">:</td><td class="val-col"><span class="mono"><?= e($s['nim_nip']) ?></span></td></tr>
    <tr><td class="label-col">Jenis Kelamin</td><td class="sep-col">:</td><td class="val-col"><?= $s['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td></tr>
  </table>

  <p>Berdasarkan hasil pemeriksaan medis di Klinik UKESMAS pada tanggal <strong><?= e(tgl($s['created_at'])) ?></strong>, yang bersangkutan dinyatakan perlu diberikan istirahat medis selama <strong><?= (int)$s['jumlah_hari'] ?> (<?= (int)$s['jumlah_hari'] ?>) hari</strong>, terhitung mulai tanggal <strong><?= e(tgl($s['tanggal_mulai'])) ?></strong> sampai dengan tanggal <strong><?= e(tgl($s['tanggal_selesai'])) ?></strong>.</p>

  <p class="small text-muted-2"><em>Demi menjaga kerahasiaan rekam medis pasien, rincian diagnosis klinis disimpan di dalam Electronic Medical Record (EMR) UKESMAS dan telah diverifikasi oleh dokter pemeriksa.</em></p>

  <div class="row align-items-center mt-4 mt-md-5 pt-2 g-3">
    <div class="col-md-7">
      <div class="verify-box d-flex align-items-center gap-3">
        <img src="<?= e($qrUrl) ?>" alt="QR Verifikasi" width="105" height="105" class="verify-qr" style="border-radius:8px;border:1px solid #cbd5e1;flex-shrink:0" onerror="this.style.display='none'">
        <div class="small verify-text" style="min-width:0;word-wrap:break-word;overflow-wrap:break-word">
          <div class="fw-bold text-navy"><i class="bi bi-patch-check-fill text-success me-1"></i>Verifikasi Keaslian Dosen</div>
          <div class="mono fw-bold fs-6 my-1"><?= e($s['kode_verifikasi']) ?></div>
          <div class="text-muted-2">Dosen dapat memindai QR atau memasukkan kode di:<br><span class="mono verify-url" style="word-break:break-all"><?= e(url('verifikasi/')) ?></span></div>
        </div>
      </div>
    </div>
    <div class="col-md-5 text-center">
      <div>Airmadidi, <?= e(tgl($s['created_at'])) ?></div>
      <div class="small text-muted-2 mb-4">Dokter Pemeriksa UKESMAS</div>
      <div class="my-3"><span class="badge badge-soft-success"><i class="bi bi-shield-check me-1"></i>Ditandatangani Secara Elektronik</span></div>
      <div class="fw-bold text-navy text-decoration-underline"><?= e($s['dokter'] ?: 'dr. Daniel Pangemanan') ?></div>
      <div class="small text-muted-2">UKESMAS Universitas Klabat</div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
