<?php
require __DIR__ . '/../template/header.php';
$f   = $data['filter'];
$rep = $data['report'];
?>

<div class="page-head no-print">
  <div>
    <h2><i class="bi bi-bar-chart-line-fill text-teal me-2"></i>Laporan Operasional Klinik UKESMAS</h2>
    <p>Rekapitulasi kunjungan pasien, kategori penyakit, dan pemakaian obat periode <strong><?= e(tgl($f['dari'])) ?></strong> s/d <strong><?= e(tgl($f['sampai'])) ?></strong>.</p>
  </div>
  <button type="button" class="btn btn-teal" onclick="window.print()"><i class="bi bi-printer-fill me-1"></i>Cetak Laporan</button>
</div>

<form class="filter-bar no-print" method="get" action="<?= url('laporan/') ?>">
  <div>
    <label class="form-label">Dari Tanggal</label>
    <input type="date" class="form-control" name="dari" value="<?= e($f['dari']) ?>">
  </div>
  <div>
    <label class="form-label">Sampai Tanggal</label>
    <input type="date" class="form-control" name="sampai" value="<?= e($f['sampai']) ?>">
  </div>
  <div>
    <label class="form-label">Kategori Penyakit</label>
    <select class="form-select" name="kategori">
      <option value="">Semua Kategori</option>
      <?php foreach ($data['categories'] as $c): ?>
        <option value="<?= e($c) ?>" <?= $f['kategori'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-teal" type="submit"><i class="bi bi-funnel-fill me-1"></i>Terapkan</button>
    <a class="btn btn-soft" href="<?= url('laporan/') ?>">14 Hari Terakhir</a>
  </div>
</form>

<div class="card-x mb-4">
  <div class="card-head"><h6 class="section-title"><i class="bi bi-clipboard2-pulse text-teal me-2"></i>Rekap per Kategori Penyakit</h6></div>
  <div class="card-body-x">
    <div class="row g-3">
      <?php if (!empty($rep['by_kategori'])): foreach ($rep['by_kategori'] as $row): ?>
        <div class="col-md-4 col-sm-6">
          <div class="p-3 rounded-3 border d-flex justify-content-between align-items-center" style="background:#F8FAFC;border-color:#E2E8F0">
            <span class="fw-semibold text-navy"><?= e($row['k']) ?></span>
            <span class="badge badge-soft-primary fs-6"><?= (int)$row['n'] ?> kasus</span>
          </div>
        </div>
      <?php endforeach; else: ?>
        <div class="col-12 text-muted-2 small">Tidak ada data kategori penyakit pada rentang tanggal ini.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="table-wrap">
  <div class="table-responsive">
    <table class="table table-modern">
      <thead><tr><th>Tanggal</th><th>NIM/NIP</th><th>Nama Pasien</th><th>Diagnosis</th><th>Kategori</th><th>Obat</th><th>Surat</th></tr></thead>
      <tbody>
        <?php if ($rep['rows']): foreach ($rep['rows'] as $r): ?>
        <tr>
          <td class="small"><?= e(tgl($r['visit_date'], true)) ?></td>
          <td class="mono small"><?= e($r['nim_nip']) ?></td>
          <td class="fw-bold text-navy"><?= e($r['nama']) ?></td>
          <td><?= e($r['diagnosis']) ?></td>
          <td><span class="badge badge-soft-primary"><?= e($r['kategori_penyakit']) ?></span></td>
          <td><?= (int)$r['item_obat'] ?> unit</td>
          <td><?= $r['surat'] ? '<span class="badge badge-soft-success">Ya</span>' : '-' ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7"><div class="empty-state">Tidak ada kunjungan pada periode tersebut.</div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
