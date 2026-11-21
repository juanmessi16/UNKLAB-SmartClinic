<?php
require __DIR__ . '/../template/header.php';
$c  = $data['counts'];
$f  = $data['filter'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-capsule-pill text-teal me-2"></i>Stok Obat &amp; Apotek UKESMAS</h2>
    <p>Informasi ketersediaan stok obat, lokasi penyimpanan rak, pemantauan batas stok minimum, dan tanggal kedaluwarsa.</p>
  </div>
  <?php if (!empty($data['can_manage'])): ?>
  <div class="d-flex gap-2">
    <button class="btn btn-teal" data-bs-toggle="modal" data-bs-target="#modalRestock"><i class="bi bi-box-arrow-in-down me-1"></i>Catat Restock Obat</button>
    <button class="btn btn-outline-clinical" data-bs-toggle="modal" data-bs-target="#modalMed" id="btnNewMed"><i class="bi bi-plus-lg me-1"></i>Tambah Obat Baru</button>
  </div>
  <?php endif; ?>
</div>

<!-- 3 Stat Cards Inventaris (Proporsional col-md-4) -->
<div class="row g-3 mb-4">
  <div class="col-md-4 col-sm-6">
    <div class="card-x stat-card">
      <div class="d-flex justify-content-between">
        <div><div class="stat-label">Total Item Obat</div><div class="stat-value mt-1"><?= (int)$c['total'] ?></div><div class="small text-muted-2">Katalog aktif</div></div>
        <div class="stat-icon blue"><i class="bi bi-capsule"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-sm-6">
    <div class="card-x stat-card">
      <div class="d-flex justify-content-between">
        <div><div class="stat-label">Stok Kritis / Habis</div><div class="stat-value mt-1 text-danger"><?= (int)$c['kritis'] ?></div><div class="small text-muted-2">Di bawah batas minimum</div></div>
        <div class="stat-icon rose"><i class="bi bi-exclamation-triangle-fill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-sm-12">
    <div class="card-x stat-card">
      <div class="d-flex justify-content-between">
        <div><div class="stat-label">Kedaluwarsa / &le;45 Hr</div><div class="stat-value mt-1 text-warning"><?= (int)($c['kedaluwarsa'] + $c['segera_kedaluwarsa']) ?></div><div class="small text-muted-2"><?= (int)$c['kedaluwarsa'] ?> telah lewat tanggal</div></div>
        <div class="stat-icon gold"><i class="bi bi-calendar2-x-fill"></i></div>
      </div>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<form class="filter-bar" method="get" action="<?= url('obat/') ?>">
  <div class="flex-grow-1" style="min-width:210px">
    <label class="form-label">Cari Nama / Kode / Indikasi</label>
    <input type="text" class="form-control" name="q" value="<?= e($f['q']) ?>" placeholder="Contoh: Paracetamol, Antasida, demam...">
  </div>
  <div>
    <label class="form-label">Kategori</label>
    <select class="form-select" name="kategori">
      <option value="">Semua Kategori</option>
      <?php foreach ($data['categories'] as $cat): ?>
        <option value="<?= e($cat) ?>" <?= $f['kategori'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="form-label">Status Stok / ED</label>
    <select class="form-select" name="status">
      <option value="">Semua</option>
      <option value="kritis" <?= $f['status'] === 'kritis' ? 'selected' : '' ?>>Stok Kritis (&le; Minimum)</option>
      <option value="aman" <?= $f['status'] === 'aman' ? 'selected' : '' ?>>Stok Aman</option>
      <option value="kedaluwarsa" <?= $f['status'] === 'kedaluwarsa' ? 'selected' : '' ?>>Kedaluwarsa / Segera ED</option>
    </select>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-navy" type="submit"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
    <a class="btn btn-soft" href="<?= url('obat/') ?>">Reset</a>
  </div>
</form>

<!-- Tabel Obat -->
<div class="table-wrap mb-4">
  <div class="table-responsive">
    <table class="table table-modern">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Nama Obat &amp; Indikasi</th>
          <th>Kategori &amp; Rak</th>
          <th>Stok Saat Ini</th>
          <th>Status</th>
          <th>Kedaluwarsa</th>
          <?php if (!empty($data['can_manage'])): ?>
            <th>Aksi</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if ($data['medicines']): foreach ($data['medicines'] as $m): ?>
        <tr>
          <td class="mono fw-bold"><?= e($m['kode']) ?></td>
          <td style="max-width:300px">
            <div class="fw-bold text-navy"><?= e($m['nama']) ?></div>
            <div class="small text-muted-2"><?= e(mb_strimwidth($m['indikasi'], 0, 90, '…')) ?></div>
          </td>
          <td>
            <div class="fw-semibold"><?= e($m['kategori']) ?></div>
            <div class="small text-muted-2"><i class="bi bi-archive me-1"></i><?= e($m['lokasi_rak']) ?> &middot; <?= e(rupiah($m['harga'])) ?></div>
          </td>
          <td>
            <div class="fw-800 fs-6 <?= $m['stok'] <= $m['stok_minimum'] ? 'text-danger' : 'text-navy' ?>">
              <?= (int)$m['stok'] ?> <span class="small fw-normal text-muted-2"><?= e($m['satuan']) ?></span>
            </div>
            <div class="small text-muted-2">Min: <?= (int)$m['stok_minimum'] ?></div>
          </td>
          <td><?= stock_badge($m['stok'], $m['stok_minimum']) ?></td>
          <td><?= expiry_badge($m['tanggal_kedaluwarsa']) ?></td>
          <?php if (!empty($data['can_manage'])): ?>
          <td>
            <div class="d-flex gap-1">
              <button type="button" class="btn btn-teal btn-sm btn-quick-restock"
                data-id="<?= (int)$m['id'] ?>" data-nama="<?= e($m['nama']) ?>">
                <i class="bi bi-plus-circle"></i> Restock
              </button>
              <button type="button" class="btn btn-soft btn-sm btn-edit-med"
                data-med='<?= e(json_encode($m)) ?>'>
                <i class="bi bi-pencil"></i>
              </button>
            </div>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="<?= !empty($data['can_manage']) ? 7 : 6 ?>"><div class="empty-state"><i class="bi bi-capsule"></i>Tidak ada obat yang cocok.</div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (!empty($data['can_manage'])): ?>
<!-- Log Mutasi Stok Terbaru (Khusus Administrator Klinik) -->
<div class="card-x">
  <div class="card-head">
    <div>
      <h6 class="section-title"><i class="bi bi-arrow-left-right text-teal me-1"></i>Riwayat Mutasi Stok Obat Terbaru (<code>medicine_stock_logs</code>)</h6>
      <div class="section-sub">Mencatat seluruh pengeluaran resep EMR otomatis, restock masuk, dan penyesuaian stok opname</div>
    </div>
  </div>
  <div class="card-body-x">
    <div class="table-responsive">
      <table class="table table-modern">
        <thead><tr><th>Waktu</th><th>Obat</th><th>Tipe</th><th>Jumlah</th><th>Saldo (Sebelum &rarr; Sesudah)</th><th>Keterangan</th><th>Petugas</th></tr></thead>
        <tbody>
          <?php foreach ($data['logs'] as $l): ?>
          <tr>
            <td class="small"><?= e(tgl($l['created_at'], true)) ?></td>
            <td class="fw-bold text-navy"><?= e($l['obat']) ?></td>
            <td>
              <?php if ($l['tipe'] === 'Masuk'): ?>
                <span class="badge badge-soft-success">+ Masuk</span>
              <?php elseif ($l['tipe'] === 'Keluar'): ?>
                <span class="badge badge-soft-danger">- Keluar</span>
              <?php else: ?>
                <span class="badge badge-soft-info">Opname</span>
              <?php endif; ?>
            </td>
            <td class="fw-bold"><?= (int)$l['jumlah'] ?> <?= e($l['satuan']) ?></td>
            <td class="mono small"><?= (int)$l['stok_sebelum'] ?> &rarr; <strong><?= (int)$l['stok_sesudah'] ?></strong></td>
            <td class="small"><?= e($l['keterangan']) ?></td>
            <td class="small text-muted-2"><?= e($l['petugas'] ?: 'Sistem') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Restock Obat -->
<div class="modal fade" id="modalRestock" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= url('obat/restock') ?>">
      <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-box-arrow-in-down text-teal me-2"></i>Pencatatan Restock Obat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Pilih Obat</label>
          <select class="form-select" name="medicine_id" id="restockMedId" required>
            <?php foreach ($data['medicines'] as $m): ?>
              <option value="<?= (int)$m['id'] ?>"><?= e($m['nama']) ?> (Stok saat ini: <?= (int)$m['stok'] ?> <?= e($m['satuan']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Jumlah Masuk</label>
            <input type="number" class="form-control" name="jumlah" value="100" min="1" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Perbarui Tgl Kedaluwarsa (Opsional)</label>
            <input type="date" class="form-control" name="tanggal_kedaluwarsa">
          </div>
          <div class="col-12">
            <label class="form-label">Keterangan / Sumber Pengadaan</label>
            <input type="text" class="form-control" name="keterangan" value="Restock pengadaan rutin apotek UKESMAS" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-teal"><i class="bi bi-check2-circle me-1"></i>Simpan Restock</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Tambah / Edit Obat -->
<div class="modal fade" id="modalMed" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" action="<?= url('obat/simpan') ?>">
      <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
      <input type="hidden" name="id" id="medId" value="0">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="medModalTitle">Tambah Obat / Alat Kesehatan Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nama Obat &amp; Dosis</label>
            <input type="text" class="form-control" name="nama" id="medNama" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Kategori Farmakologi</label>
            <input type="text" class="form-control" name="kategori" id="medKategori" value="Analgesik & Antipiretik" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Satuan</label>
            <input type="text" class="form-control" name="satuan" id="medSatuan" value="Tablet" required>
          </div>
          <div class="col-md-3" id="wrapStokAwal">
            <label class="form-label">Stok Awal</label>
            <input type="number" class="form-control" name="stok_awal" value="100" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label">Batas Stok Minimum</label>
            <input type="number" class="form-control" name="stok_minimum" id="medMin" value="50" min="1" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Harga Satuan (Rp)</label>
            <input type="number" class="form-control" name="harga" id="medHarga" value="500" min="0">
          </div>
          <div class="col-md-6">
            <label class="form-label">Tanggal Kedaluwarsa</label>
            <input type="date" class="form-control" name="tanggal_kedaluwarsa" id="medExp">
          </div>
          <div class="col-md-6">
            <label class="form-label">Lokasi Penyimpanan Rak</label>
            <input type="text" class="form-control" name="lokasi_rak" id="medRak" value="Rak A-1">
          </div>
          <div class="col-12">
            <label class="form-label">Indikasi / Kegunaan Klinis</label>
            <textarea class="form-control" name="indikasi" id="medIndikasi" rows="2" required></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-teal"><i class="bi bi-save me-1"></i>Simpan Obat</button>
      </div>
    </form>
  </div>
</div>

<?php
$data['extra_js'] = <<<'JS'
<script>
(function(){
  var elRestock = document.getElementById('modalRestock');
  var elMed = document.getElementById('modalMed');
  if (!elRestock || !elMed) return;

  var mRestock = new bootstrap.Modal(elRestock);
  var mMed = new bootstrap.Modal(elMed);

  document.querySelectorAll('.btn-quick-restock').forEach(function(b){
    b.addEventListener('click', function(){
      document.getElementById('restockMedId').value = b.dataset.id;
      mRestock.show();
    });
  });

  var btnNew = document.getElementById('btnNewMed');
  if (btnNew) {
    btnNew.addEventListener('click', function(){
      document.getElementById('medModalTitle').textContent = 'Tambah Obat Baru';
      document.getElementById('medId').value = 0;
      document.getElementById('medNama').value = '';
      document.getElementById('medIndikasi').value = '';
      document.getElementById('wrapStokAwal').classList.remove('d-none');
    });
  }

  document.querySelectorAll('.btn-edit-med').forEach(function(b){
    b.addEventListener('click', function(){
      var m = JSON.parse(b.dataset.med);
      document.getElementById('medModalTitle').textContent = 'Edit Obat — ' + m.nama;
      document.getElementById('medId').value = m.id;
      document.getElementById('medNama').value = m.nama;
      document.getElementById('medKategori').value = m.kategori;
      document.getElementById('medSatuan').value = m.satuan;
      document.getElementById('medMin').value = m.stok_minimum;
      document.getElementById('medHarga').value = m.harga;
      document.getElementById('medExp').value = m.tanggal_kedaluwarsa || '';
      document.getElementById('medRak').value = m.lokasi_rak || '';
      document.getElementById('medIndikasi').value = m.indikasi || '';
      document.getElementById('wrapStokAwal').classList.add('d-none');
      mMed.show();
    });
  });
})();
</script>
JS;
?>
<?php endif; ?>
<?php require __DIR__ . '/../template/footer.php'; ?>
