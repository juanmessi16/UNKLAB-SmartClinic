<?php
require __DIR__ . '/../template/header.php';
$f = $data['filter'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-person-vcard-fill text-teal me-2"></i>Data Pasien Kampus</h2>
    <p>Daftar mahasiswa dan dosen terdaftar serta frekuensi kunjungan UKESMAS.</p>
  </div>
</div>

<!-- Filter Bar -->
<form class="filter-bar" method="get" action="<?= url('pasien/') ?>">
  <div class="flex-grow-1" style="min-width:260px">
    <label class="form-label">Cari Nama / NIM / NIP</label>
    <input type="text" class="form-control" name="q" value="<?= e($f['q']) ?>" placeholder="Ketik nama atau NIM...">
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-teal" type="submit"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
    <a class="btn btn-soft" href="<?= url('pasien/') ?>">Reset</a>
  </div>
</form>

<div class="table-wrap">
  <div class="table-responsive">
    <table class="table table-modern">
      <thead>
        <tr>
          <th>NIM / NIP</th>
          <th>Nama Pasien</th>
          <th>Total Kunjungan</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($data['patients']): foreach ($data['patients'] as $p): ?>
        <tr>
          <td class="mono fw-bold"><?= e($p['nim_nip']) ?></td>
          <td>
            <div class="fw-bold text-navy"><?= e($p['nama']) ?></div>
            <div class="small text-muted-2"><?= e(ucfirst($p['tipe'])) ?> &middot; <?= $p['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
          </td>
          <td>
            <button type="button" class="btn btn-outline-clinical btn-sm px-2 py-1 btn-view-visits"
                    data-id="<?= (int)$p['id'] ?>"
                    data-nama="<?= e($p['nama']) ?>"
                    data-nim="<?= e($p['nim_nip']) ?>"
                    data-total="<?= (int)$p['total_kunjungan'] ?>"
                    title="Klik untuk melihat seluruh riwayat tanggal kunjungan">
              <i class="bi bi-calendar2-check-fill me-1 text-teal"></i><strong><?= (int)$p['total_kunjungan'] ?>x Kunjungan</strong>
            </button>
            <div class="mt-1">
              <a href="javascript:void(0)" class="small text-teal text-decoration-none fw-semibold btn-view-visits"
                 data-id="<?= (int)$p['id'] ?>"
                 data-nama="<?= e($p['nama']) ?>"
                 data-nim="<?= e($p['nim_nip']) ?>"
                 data-total="<?= (int)$p['total_kunjungan'] ?>">
                <i class="bi bi-clock-history me-1"></i>Lihat semua tanggal &rarr;
              </a>
            </div>
            <div class="small text-muted-2 mt-1" style="font-size:.78rem">
              <?= $p['kunjungan_terakhir'] ? ('Terakhir: ' . e(tgl($p['kunjungan_terakhir']))) : 'Belum ada riwayat' ?>
            </div>
          </td>
          <td>
            <a class="btn btn-soft btn-sm" href="<?= url('pasien/detail/' . $p['id']) ?>"><i class="bi bi-eye me-1"></i>Rekam Medis</a>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="4"><div class="empty-state"><i class="bi bi-people"></i>Tidak ada pasien yang cocok dengan pencarian.</div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Rincian Tanggal Kunjungan Pasien -->
<div class="modal fade" id="modalVisits" tabindex="-1" aria-labelledby="modalVisitsTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content" style="border-radius:16px;border:1px solid #CCFBF1;overflow:hidden">
      <div class="modal-header py-3 px-4" style="background:#F0FDFA;border-bottom:1px solid #CCFBF1">
        <div>
          <h5 class="modal-title fw-bold text-navy mb-0" id="modalVisitsTitle">
            <i class="bi bi-calendar-check-fill text-teal me-2"></i>Riwayat Tanggal Kunjungan Pasien
          </h5>
          <div class="small text-muted-2 mt-1">Daftar seluruh rekam kunjungan pemeriksaan medis di UKESMAS Universitas Klabat</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Patient Summary Box -->
        <div class="p-3 rounded-3 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="background:#F8FAFC;border:1px solid #E2E8F0">
          <div>
            <div class="fw-bold fs-5 text-navy" id="mvPatientName">—</div>
            <div class="small text-muted-2">NIM / NIP: <strong class="text-teal mono" id="mvPatientNim">—</strong></div>
          </div>
          <div>
            <span class="badge badge-soft-primary fs-6 px-3 py-2 fw-800" id="mvPatientTotal">0 Kunjungan</span>
          </div>
        </div>

        <!-- Loading State -->
        <div id="mvLoading" class="text-center py-4">
          <div class="spinner-border text-teal" role="status"></div>
          <div class="small text-muted-2 mt-2">Memuat seluruh rincian tanggal kunjungan...</div>
        </div>

        <!-- Error State -->
        <div id="mvError" class="alert alert-danger py-2 px-3 small d-none" role="alert"></div>

        <!-- Visits Table Container -->
        <div id="mvTableWrap" class="table-responsive d-none">
          <table class="table table-modern table-sm mb-0">
            <thead>
              <tr>
                <th style="width:45px">No</th>
                <th>Tanggal &amp; Waktu Kunjungan</th>
                <th>Diagnosis &amp; Kategori</th>
                <th>Dokter Pemeriksa</th>
                <th class="text-center" style="width:90px">Aksi</th>
              </tr>
            </thead>
            <tbody id="mvTableBody"></tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div id="mvEmpty" class="empty-state py-4 d-none">
          <i class="bi bi-calendar-x"></i>
          <div>Belum ada riwayat kunjungan yang tercatat untuk pasien ini.</div>
        </div>
      </div>
      <div class="modal-footer py-2 px-4" style="background:#FAFBFB;border-top:1px solid #E2E8F0">
        <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?php
$data['extra_js'] = <<<'JS'
<script>
(function(){
  var modalEl = document.getElementById('modalVisits');
  if (!modalEl) return;
  var bsModal = new bootstrap.Modal(modalEl);

  var nameEl     = document.getElementById('mvPatientName');
  var nimEl      = document.getElementById('mvPatientNim');
  var totalEl    = document.getElementById('mvPatientTotal');
  var loadingEl  = document.getElementById('mvLoading');
  var errorEl    = document.getElementById('mvError');
  var tableWrap  = document.getElementById('mvTableWrap');
  var tableBody  = document.getElementById('mvTableBody');
  var emptyEl    = document.getElementById('mvEmpty');

  function openVisitsModal(id, nama, nim, total) {
    nameEl.textContent = nama || 'Pasien';
    nimEl.textContent  = nim || '—';
    totalEl.textContent = (total || 0) + ' Kunjungan';

    loadingEl.classList.remove('d-none');
    errorEl.classList.add('d-none');
    tableWrap.classList.add('d-none');
    emptyEl.classList.add('d-none');
    tableBody.innerHTML = '';

    bsModal.show();

    var base = (window.APP_BASE || '').replace(/\/$/, '');
    fetch(base + '/pasien/riwayat_kunjungan/' + encodeURIComponent(id), {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      credentials: 'same-origin'
    })
      .then(function(r){ return r.json(); })
      .then(function(res){
        loadingEl.classList.add('d-none');
        if (!res || !res.success) {
          errorEl.textContent = (res && res.message) || 'Gagal mengambil riwayat kunjungan.';
          errorEl.classList.remove('d-none');
          return;
        }

        if (res.patient) {
          nameEl.textContent = res.patient.nama;
          nimEl.textContent  = res.patient.nim_nip;
          totalEl.textContent = res.patient.total + ' Kunjungan';
        }

        var list = res.visits || [];
        if (list.length === 0) {
          emptyEl.classList.remove('d-none');
          return;
        }

        var html = '';
        list.forEach(function(v){
          var emrUrl = base + '/emr/detail/' + v.id;
          var qBadge = v.queue_number ? ('<span class="badge badge-soft-info mono me-1">' + v.queue_number + '</span>') : '';
          var icd = v.kode_icd ? (' <span class="mono text-muted-2 small">(' + v.kode_icd + ')</span>') : '';
          html += '<tr>' +
            '<td class="text-muted-2 small">' + v.no + '</td>' +
            '<td><div class="fw-bold text-navy">' + qBadge + v.tanggal + '</div></td>' +
            '<td><div class="fw-semibold">' + v.diagnosis + icd + '</div><span class="badge badge-soft-primary" style="font-size:.72rem">' + v.kategori + '</span></td>' +
            '<td class="small text-muted-2">' + (v.dokter || 'Dokter UKESMAS') + '</td>' +
            '<td class="text-center"><a href="' + emrUrl + '" class="btn btn-soft btn-sm py-1 px-2" title="Buka EMR"><i class="bi bi-eye"></i> EMR</a></td>' +
          '</tr>';
        });

        tableBody.innerHTML = html;
        tableWrap.classList.remove('d-none');
      })
      .catch(function(){
        loadingEl.classList.add('d-none');
        errorEl.textContent = 'Koneksi bermasalah saat mengambil data kunjungan.';
        errorEl.classList.remove('d-none');
      });
  }

  document.querySelectorAll('.btn-view-visits').forEach(function(b){
    b.addEventListener('click', function(e){
      e.preventDefault();
      var id = b.dataset.id;
      var nama = b.dataset.nama;
      var nim = b.dataset.nim;
      var total = b.dataset.total;
      openVisitsModal(id, nama, nim, total);
    });
  });
})();
</script>
JS;
?>

<?php require __DIR__ . '/../template/footer.php'; ?>
