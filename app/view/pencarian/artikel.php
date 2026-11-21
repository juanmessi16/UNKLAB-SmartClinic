<?php
require __DIR__ . '/../template/header.php';
$a = $data['article'];
?>
<div class="container py-3" style="max-width:860px">
  <a href="<?= url('pencarian/') ?>" class="btn btn-soft btn-sm mb-3"><i class="bi bi-arrow-left me-1"></i>Kembali ke Pencarian AI</a>
  <div class="card-x p-4 p-lg-5">
    <span class="badge badge-soft-warning mb-2"><?= e($a['kategori']) ?></span>
    <h2 class="fw-800 mb-2"><?= e($a['judul']) ?></h2>
    <p class="lead text-muted-2"><?= e($a['ringkasan']) ?></p>
    <hr>
    <div class="fs-6 lh-lg pre-wrap"><?= e($a['isi']) ?></div>
    <hr class="my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="small text-muted-2">Kata kunci: <?= e($a['tags']) ?></div>
      <a href="<?= url('triase/') ?>" class="btn btn-gold btn-sm"><i class="bi bi-heart-pulse-fill me-1"></i>Alami gejala ini? Mulai Triase AI</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../template/footer.php'; ?>
