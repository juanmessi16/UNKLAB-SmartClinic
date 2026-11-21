<?php
require __DIR__ . '/../template/header.php';
$pat     = $data['patient'];
$isStaff = $data['is_staff'];
$activeQ = $data['active_q'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-heart-pulse-fill text-gold me-2"></i>Konsultasi AI &amp; Ambil Antrean</h2>
    <p>Tuliskan keluhan kesehatan Anda dengan bahasa sehari-hari. Gemini AI Assistant akan memberikan saran penanganan awal dan menyiapkan ringkasan keluhan untuk dokter UKESMAS.</p>
  </div>
  <?php if ($activeQ): ?>
    <a href="<?= url('antrean/') ?>" class="btn btn-gold"><i class="bi bi-ticket-perforated-fill me-1"></i>Antrean Aktif: <?= e($activeQ['queue_number']) ?> (<?= e($activeQ['status']) ?>)</a>
  <?php endif; ?>
</div>

<div class="row g-4 align-items-stretch">
  <!-- Kolom Kiri: Form Konsultasi -->
  <div class="col-lg-6 d-flex">
    <div class="consult-card w-100">
      <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
        <div>
          <h5 class="section-title mb-1"><i class="bi bi-clipboard2-pulse-fill text-teal me-2"></i>Formulir Keluhan Pasien</h5>
          <div class="section-sub">Jelaskan gejala, lama keluhan, suhu tubuh bila demam, dan kondisi penyerta</div>
        </div>
        <span class="ai-badge flex-shrink-0"><i class="bi bi-heart-pulse"></i>Asisten Medis</span>
      </div>

      <?php if ($pat): ?>
        <div class="patient-strip mb-4">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar gold" style="width:38px;height:38px;font-size:.85rem"><?= e(initials($pat['nama'])) ?></div>
            <div>
              <div class="fw-bold text-navy lh-sm"><?= e($pat['nama']) ?></div>
              <div class="mono small text-muted-2">NIM / NIP: <?= e($pat['nim_nip']) ?></div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <form id="triageForm" class="d-flex flex-column flex-grow-1" autocomplete="off">
        <?php if ($isStaff && $data['patients']): ?>
          <div class="mb-3">
            <label class="form-label">Pilih Pasien (Mode Petugas)</label>
            <select class="form-select" id="patientSelect" name="patient_id">
              <?php foreach ($data['patients'] as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= ($pat && $pat['id'] == $p['id']) ? 'selected' : '' ?>><?= e($p['nama']) ?> — <?= e($p['nim_nip']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php else: ?>
          <input type="hidden" id="patientSelect" name="patient_id" value="<?= (int)($pat['id'] ?? 0) ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label" for="complaintInput">Keluhan yang Dirasakan</label>
          <textarea class="form-control" id="complaintInput" name="complaint" rows="4" placeholder="Contoh: Saya mengalami demam sejak 2 hari lalu suhu 38.5°C, disertai batuk berdahak, tenggorokan sakit saat menelan, dan badan terasa lemas..." required></textarea>
        </div>

        <div class="mb-4">
          <div class="small fw-bold text-muted-2 mb-2"><i class="bi bi-lightning-charge-fill text-gold me-1"></i>Atau pilih contoh keluhan populer:</div>
          <div class="chip-group">
            <?php foreach ($data['samples'] as $s): ?>
              <button type="button" class="chip sample-btn" data-text="<?= e($s[1]) ?>"><?= e($s[0]) ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mt-auto pt-2 d-flex align-items-stretch gap-2">
          <button type="submit" class="btn btn-teal flex-grow-1 py-2 px-4" id="btnAnalyze" style="min-height:46px;border-radius:12px">
            <i class="bi bi-send-check-fill me-2"></i>Konsultasi &amp; Analisis Gejala
          </button>
          <button type="button" class="btn btn-outline-clinical px-3" id="btnReset" style="min-height:46px;border-radius:12px">Bersihkan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Kolom Kanan: Hasil Analisis AI -->
  <div class="col-lg-6 d-flex">
    <div class="consult-card w-100">
      <div id="triageLoading" class="d-none my-auto">
        <div class="skeleton mb-3" style="height:76px"></div>
        <div class="skeleton mb-2" style="height:22px;width:55%"></div>
        <div class="skeleton mb-3" style="height:86px"></div>
        <div class="skeleton" style="height:92px"></div>
      </div>

      <div id="triageEmpty" class="my-auto py-3 text-center">
        <div class="stat-icon teal mx-auto mb-3" style="width:60px;height:60px;font-size:1.6rem"><i class="bi bi-clipboard2-pulse"></i></div>
        <h5 class="fw-800 mb-2">Hasil Analisis &amp; Pengambilan Antrean</h5>
        <p class="text-muted-2 small mx-auto mb-0" style="max-width:400px">Tuliskan keluhan di sebelah kiri atau pilih contoh keluhan populer, lalu klik <strong>Konsultasi &amp; Analisis Gejala</strong>.</p>
      </div>

      <div id="triageResult" class="d-none d-flex flex-column flex-grow-1">
        <div class="ai-result-header mb-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <span class="small fw-bold text-uppercase text-muted-2" style="letter-spacing:.08em">Hasil Konsultasi</span>
            <span class="ai-badge" id="resSource"><i class="bi bi-heart-pulse"></i>Asisten Medis</span>
          </div>
          <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="badge badge-soft-primary fs-6"><i class="bi bi-tag-fill me-1"></i>Estimasi Kategori: <strong id="resCat">—</strong></span>
            <span class="badge badge-soft-secondary">Keyakinan: <strong id="resConf">—</strong>%</span>
          </div>
        </div>

        <div class="p-3 rounded-4 mb-3" style="background:var(--slate-50);border:1px solid #e2e8f0">
          <div class="small fw-bold text-navy mb-1"><i class="bi bi-clipboard2-pulse-fill text-teal me-1"></i>Ringkasan Keluhan untuk Dokter</div>
          <div class="small text-muted-2" id="resAnamnesis">—</div>
        </div>

        <div class="mb-3">
          <h6 class="fw-bold text-navy mb-2"><i class="bi bi-bandaid-fill text-teal me-1"></i>Saran Penanganan &amp; Istirahat Awal</h6>
          <ol class="step-list small" id="resFirstAid"></ol>
        </div>

        <div class="p-3 rounded-4 mb-4 small" style="background:var(--teal-50);border:1px solid var(--teal-100);color:var(--teal-800)">
          <i class="bi bi-info-circle-fill text-teal me-1"></i><span id="resAction">—</span>
        </div>

        <form method="post" action="<?= url('triase/ambil_antrean') ?>" class="mt-auto">
          <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
          <input type="hidden" name="patient_id" id="qPatientId" value="<?= (int)($pat['id'] ?? 0) ?>">
          <input type="hidden" name="complaint" id="qComplaint" value="">
          <input type="hidden" name="triage_json" id="qTriageJson" value="">
          <button type="submit" class="btn btn-gold btn-lg w-100 py-3 fw-800 shadow-sm" style="border-radius:14px">
            <i class="bi bi-ticket-perforated-fill me-2"></i>Ambil Nomor Antrean Sekarang
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$data['extra_js'] = <<<'JS'
<script>
(function(){
  var form = document.getElementById('triageForm');
  var input = document.getElementById('complaintInput');
  var pSel = document.getElementById('patientSelect');
  var boxEmpty = document.getElementById('triageEmpty');
  var boxLoad = document.getElementById('triageLoading');
  var boxRes = document.getElementById('triageResult');

  function renderResult(t, complaint) {
    boxEmpty.classList.add('d-none');
    boxLoad.classList.add('d-none');
    boxRes.classList.remove('d-none');

    document.getElementById('resCat').textContent = t.kategori || 'Keluhan Umum';
    document.getElementById('resConf').textContent = t.confidence || 82;

    var src = document.getElementById('resSource');
    src.className = 'ai-badge ' + (t.source === 'gemini' ? '' : 'fallback');
    src.innerHTML = '<i class="bi bi-' + (t.source === 'gemini' ? 'heart-pulse' : 'clipboard2-check') + '"></i>' + (t.source === 'gemini' ? 'Asisten Medis (' + (t.model || 'Gemini') + ')' : 'Panduan Klinis');

    var fa = document.getElementById('resFirstAid');
    fa.innerHTML = '';
    (t.saran_awal || t.first_aid || []).forEach(function(step){
      var li = document.createElement('li');
      li.textContent = step;
      fa.appendChild(li);
    });

    document.getElementById('resAnamnesis').textContent = t.ringkasan_dokter || t.anamnesis_summary || '';
    document.getElementById('resAction').textContent = t.catatan || 'Silakan ambil nomor antrean untuk pemeriksaan langsung oleh dokter UKESMAS.';

    document.getElementById('qPatientId').value = pSel ? pSel.value : 0;
    document.getElementById('qComplaint').value = complaint;
    document.getElementById('qTriageJson').value = JSON.stringify(t);
  }

  form.addEventListener('submit', function(e){
    e.preventDefault();
    var c = input.value.trim();
    if (!c) return;
    boxEmpty.classList.add('d-none');
    boxRes.classList.add('d-none');
    boxLoad.classList.remove('d-none');

    SC.post('ai/triage', { complaint: c, patient_id: pSel ? pSel.value : 0 }).then(function(r){
      boxLoad.classList.add('d-none');
      if (r && r.success && r.triage) {
        renderResult(r.triage, c);
      } else {
        boxEmpty.classList.remove('d-none');
        alert((r && r.message) || 'Gagal menganalisis keluhan.');
      }
    }).catch(function(){
      boxLoad.classList.add('d-none');
      boxEmpty.classList.remove('d-none');
      alert('Terjadi gangguan koneksi.');
    });
  });

  document.querySelectorAll('.sample-btn').forEach(function(b){
    b.addEventListener('click', function(){
      input.value = b.dataset.text;
      form.dispatchEvent(new Event('submit'));
    });
  });

  document.getElementById('btnReset').addEventListener('click', function(){
    input.value = '';
    boxRes.classList.add('d-none');
    boxEmpty.classList.remove('d-none');
  });
})();
</script>
JS;
require __DIR__ . '/../template/footer.php';
