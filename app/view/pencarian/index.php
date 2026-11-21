<?php
require __DIR__ . '/../template/header.php';
$q   = $data['query'];
$res = $data['search_res'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-search text-teal me-2"></i>Pencarian Panduan Kesehatan &amp; Katalog Obat</h2>
    <p>Cari panduan P3K, artikel kesehatan kampus, dan katalog obat apotek UKESMAS menggunakan kalimat bahasa sehari-hari.</p>
  </div>
</div>

<div class="card-x p-4 mb-4">
  <form method="get" action="<?= url('pencarian/') ?>" class="d-flex flex-wrap gap-2 mb-3">
    <input type="text" class="form-control form-control-lg flex-grow-1" name="q" value="<?= e($q) ?>" placeholder="Contoh: &ldquo;perut perih karena telat makan saat ujian, obat apa yang cocok?&rdquo;" required>
    <button class="btn btn-teal btn-lg px-4" type="submit"><i class="bi bi-search me-1"></i>Cari Informasi</button>
  </form>
  <div class="d-flex flex-wrap gap-2">
    <?php foreach ([
      'perut perih dan mual saat ujian',
      'teman sekamar batuk pilek demam',
      'obat alergi gatal udang yang tidak bikin ngantuk',
      'pertolongan pertama kaki terkilir saat futsal',
      'cara mendapatkan surat sakit untuk izin kuliah',
      'diare lemas mencegah dehidrasi',
    ] as $ex): ?>
      <a class="chip" href="<?= url('pencarian/?q=' . urlencode($ex)) ?>"><i class="bi bi-search text-gold"></i><?= e($ex) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($res): ?>
<div class="card-x p-4 mb-4">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <div class="small text-muted-2 fw-bold text-uppercase">Jawaban &amp; Rekomendasi Kontekstual AI</div>
      <div class="fw-bold text-navy fs-6 mt-1"><?= e($res['answer']) ?></div>
    </div>
    <span class="ai-badge <?= ($res['source'] ?? '') === 'gemini' ? '' : 'fallback' ?>">
      <i class="bi bi-heart-pulse"></i><?= ($res['source'] ?? '') === 'gemini' ? 'Analisis Medis (Gemini)' : 'Katalog Klinik UKESMAS' ?>
    </span>
  </div>
  <?php if (!empty($res['results'])): ?>
    <div class="d-grid gap-2">
      <?php foreach ($res['results'] as $it): ?>
        <a class="result-item" href="<?= e($it['url']) ?>">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="fw-bold text-navy"><?= e($it['title']) ?></div>
            <span class="badge badge-soft-<?= $it['type'] === 'obat' ? 'primary' : 'warning' ?>"><?= $it['type'] === 'obat' ? 'Katalog Obat' : 'Artikel Kesehatan' ?></span>
          </div>
          <div class="small text-muted-2 mt-1"><?= e($it['snippet']) ?>…</div>
          <div class="d-flex align-items-center gap-2 mt-2">
            <div class="score-bar flex-grow-1"><span style="width:<?= min(100, (int)$it['score']) ?>%"></span></div>
            <span class="small fw-bold"><?= (int)$it['score'] ?>% relevan</span>
          </div>
          <div class="small text-muted-2 mt-1"><i class="bi bi-lightbulb text-gold me-1"></i><?= e($it['reason']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state">Tidak ditemukan dokumen yang cocok.</div>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card-x h-100">
      <div class="card-head"><h6 class="section-title"><i class="bi bi-journal-medical text-gold me-1"></i>Panduan Kesehatan Kampus &amp; P3K</h6></div>
      <div class="card-body-x">
        <div class="d-grid gap-2">
          <?php foreach ($data['articles'] as $a): ?>
            <a class="result-item" href="<?= url('pencarian/artikel/' . $a['id']) ?>">
              <div class="d-flex justify-content-between">
                <div class="fw-bold text-navy"><?= e($a['judul']) ?></div>
                <span class="badge badge-soft-warning"><?= e($a['kategori']) ?></span>
              </div>
              <div class="small text-muted-2 mt-1"><?= e($a['ringkasan']) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-x h-100">
      <div class="card-head"><h6 class="section-title"><i class="bi bi-capsule text-gold me-1"></i>Katalog Ketersediaan Obat UKESMAS</h6></div>
      <div class="card-body-x">
        <div class="d-grid gap-2">
          <?php foreach ($data['medicines'] as $m): ?>
            <a class="result-item" href="<?= url('pencarian/obat/' . $m['id']) ?>">
              <div class="d-flex justify-content-between align-items-center">
                <div class="fw-bold text-navy"><?= e($m['nama']) ?></div>
                <?= stock_badge($m['stok'], $m['stok_minimum']) ?>
              </div>
              <div class="small text-muted-2"><?= e($m['kategori']) ?> &middot; Stok: <?= (int)$m['stok'] ?> <?= e($m['satuan']) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
