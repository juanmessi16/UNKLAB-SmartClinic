<?php
require __DIR__ . '/../template/header.php';
$m = $data['med'];
?>
<div class="container py-3" style="max-width:760px">
  <a href="<?= url('pencarian/') ?>" class="btn btn-soft btn-sm mb-3"><i class="bi bi-arrow-left me-1"></i>Kembali ke Katalog</a>
  <div class="card-x p-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <span class="mono small text-muted-2"><?= e($m['kode']) ?> &middot; <?= e($m['kategori']) ?></span>
        <h3 class="fw-800 mb-1"><?= e($m['nama']) ?></h3>
      </div>
      <?= stock_badge($m['stok'], $m['stok_minimum']) ?>
    </div>
    <hr>
    <div class="row g-3 mb-3">
      <div class="col-4"><div class="vital-box"><div class="l">Stok Tersedia</div><div class="v"><?= (int)$m['stok'] ?> <?= e($m['satuan']) ?></div></div></div>
      <div class="col-4"><div class="vital-box"><div class="l">Lokasi Rak</div><div class="v"><?= e($m['lokasi_rak']) ?></div></div></div>
      <div class="col-4"><div class="vital-box"><div class="l">Kedaluwarsa</div><div class="v fs-6"><?= e(tgl($m['tanggal_kedaluwarsa'])) ?></div></div></div>
    </div>
    <div class="mb-3">
      <div class="fw-bold text-navy">Indikasi &amp; Kegunaan</div>
      <p class="mb-0"><?= e($m['indikasi']) ?></p>
    </div>
    <div class="alert alert-info small mb-0">
      <i class="bi bi-info-circle-fill me-1"></i>Pemberian obat di apotek UKESMAS dilakukan berdasarkan pemeriksaan dokter/perawat jaga. Gunakan menu <a href="<?= url('triase/') ?>" class="alert-link">Triase AI</a> untuk mengambil nomor antrean.
    </div>
  </div>
</div>
<?php require __DIR__ . '/../template/footer.php'; ?>
