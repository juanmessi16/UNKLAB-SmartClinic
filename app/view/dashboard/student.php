<?php
require __DIR__ . '/../template/header.php';
$u    = $data['user'];
$live = $data['live_queue'];
$pat  = $data['patient_profile'];
$myQ  = $data['my_active_queue'];
$pos  = (int)$data['my_queue_pos'];
?>

<!-- Banner Sapaan Klinis Bersih + Indikator Antrean Live UKESMAS -->
<div class="welcome-banner mb-3" style="padding:1.1rem 1.4rem">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge badge-soft-primary"><i class="bi bi-heart-pulse me-1"></i>Layanan Pasien Mahasiswa</span>
      </div>
      <h4 class="fw-800 mb-1" style="font-size:1.3rem">Halo, <?= e($u['nama']) ?>!</h4>
      <div class="small text-muted-2 mb-0">Sampaikan keluhan kesehatan Anda untuk mendapatkan saran penanganan awal dan nomor antrean klinik UKESMAS.</div>
    </div>

    <!-- Kotak Indikator Antrean Live UKESMAS (Kontras Tinggi & Mudah Dibaca) -->
    <div class="live-indicator-box d-flex flex-wrap align-items-center gap-3">
      <div class="d-flex align-items-center gap-2">
        <span class="live-dot m-0"></span>
        <div>
          <div style="font-size:.66rem;text-transform:uppercase;letter-spacing:.08em;color:var(--slate-500);font-weight:700">Antrean Live UKESMAS</div>
          <div class="fw-800 mono text-teal" style="font-size:1.25rem;line-height:1.1" id="liveCalled"><?= e($live['dipanggil'] ?? '—') ?></div>
        </div>
      </div>
      <div class="vr d-none d-sm-block" style="color:var(--slate-200);opacity:1"></div>
      <div class="small" style="color:var(--slate-600);line-height:1.35">
        <div>Menunggu: <strong class="text-navy" id="liveWait"><?= (int)$live['menunggu'] ?></strong> &middot; Selesai: <strong class="text-navy" id="liveDone"><?= (int)$live['selesai'] ?></strong></div>
        <div class="text-clinical-blue fw-semibold" style="font-size:.76rem"><i class="bi bi-clock me-1"></i>Est. tunggu <strong id="liveEst"><?= (int)$live['estimasi_menit'] ?></strong> menit</div>
      </div>
    </div>
  </div>
</div>

<!-- 1 Baris Tipis Notifikasi Antrean Aktif Saya -->
<div id="activeQueueBanner">
<?php if ($myQ): ?>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 mb-3 rounded-3" style="background:var(--teal-50);border:1px solid var(--teal-200)">
    <div class="d-flex flex-wrap align-items-center gap-2 small">
      <span class="badge badge-soft-primary fw-800 mono fs-6 px-3"><?= e($myQ['queue_number']) ?></span>
      <span class="fw-bold text-navy">Antrean Aktif Saya</span>
      <?= queue_status_badge($myQ['status']) ?>
      <span class="text-muted-2">&middot; <?= $myQ['status'] === 'Dipanggil' ? 'Giliran Anda! Silakan masuk ruang periksa' : ($pos > 0 ? $pos . ' pasien di depan Anda' : 'Urutan berikutnya') ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= url('antrean/') ?>" class="btn btn-teal btn-sm py-1 px-3">Lihat Tiket</a>
      <form method="post" action="<?= url('antrean/batal/' . $myQ['id']) ?>" data-confirm="Batalkan nomor antrean aktif ini?">
        <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
        <button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none p-0 px-1">Batalkan</button>
      </form>
    </div>
  </div>
<?php endif; ?>
</div>

<!-- Panel Utama 2 Kolom Clean Clinical UI -->
<div class="row g-3 align-items-stretch">
  <!-- Kolom Kiri: Form Konsultasi Keluhan -->
  <div class="col-lg-6 d-flex">
    <div class="consult-card w-100">
      <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
        <h5 class="section-title mb-0"><i class="bi bi-clipboard2-pulse-fill text-teal me-2"></i>Konsultasi Gejala</h5>
        <span class="ai-badge"><i class="bi bi-heart-pulse"></i>Asisten Medis</span>
      </div>

      <form id="studentTriageForm" class="d-flex flex-column flex-grow-1" autocomplete="off">
        <input type="hidden" id="patientIdInput" value="<?= (int)($pat['id'] ?? 0) ?>">

        <div class="mb-3">
          <label class="form-label mb-2" for="complaintInput">Apa yang sedang Anda rasakan?</label>
          <textarea class="form-control" id="complaintInput" name="complaint" rows="4" style="border-radius:12px;resize:none" placeholder="Ceritakan keluhan Anda secara singkat, misalnya demam sejak 2 hari, batuk, atau nyeri ulu hati..." required></textarea>
        </div>

        <div class="mb-3">
          <div class="small text-muted-2 mb-2">Contoh keluhan umum:</div>
          <div class="chip-group">
            <?php foreach ($data['samples'] as $s): ?>
              <button type="button" class="chip sample-btn" data-text="<?= e($s[1]) ?>"><?= e($s[0]) ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mt-auto pt-2 d-flex align-items-center gap-2">
          <button type="submit" class="btn btn-teal px-4 py-2 flex-grow-1" id="btnAnalyze" style="min-height:44px">
            <i class="bi bi-send-check-fill me-2"></i>Konsultasi Keluhan
          </button>
          <button type="button" class="btn btn-outline-clinical px-3 py-2" id="btnReset" style="min-height:44px">Reset</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Kolom Kanan: Hasil Analisis & Pengambilan Antrean -->
  <div class="col-lg-6 d-flex">
    <div class="consult-card w-100">
      <!-- Loading State -->
      <div id="triageLoading" class="d-none my-auto py-3">
        <div class="d-flex align-items-center gap-2 mb-3 text-navy fw-semibold small">
          <span class="spinner-border spinner-border-sm text-teal"></span>
          <span>Menganalisis keluhan kesehatan Anda…</span>
        </div>
        <div class="skeleton mb-3" style="height:52px"></div>
        <div class="skeleton mb-3" style="height:76px"></div>
        <div class="skeleton" style="height:68px"></div>
      </div>

      <!-- Empty State Bersih & Klinis -->
      <div id="triageEmpty" class="my-auto text-center py-4 px-3">
        <div class="stat-icon teal mx-auto mb-3" style="width:54px;height:54px;font-size:1.45rem"><i class="bi bi-clipboard2-pulse"></i></div>
        <h5 class="fw-800 mb-2">Hasil Analisis &amp; Antrean</h5>
        <p class="text-muted-2 small mx-auto mb-0" style="max-width:340px">Panduan penanganan awal, ringkasan anamnesis untuk dokter, dan tombol pengambilan nomor antrean UKESMAS akan tampil di sini.</p>
      </div>

      <!-- Result State -->
      <div id="triageResult" class="d-none d-flex flex-column flex-grow-1">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
          <div>
            <span class="small text-muted-2 me-1">Estimasi Kategori:</span>
            <span class="badge badge-soft-primary fs-6" id="resCat">—</span>
          </div>
          <span class="ai-badge" id="resSource"><i class="bi bi-heart-pulse"></i>Analisis Medis</span>
        </div>

        <div class="mb-3">
          <div class="small fw-bold text-navy mb-2"><i class="bi bi-bandaid text-teal me-1"></i>Saran Penanganan Awal</div>
          <ol class="step-list small mb-0" id="resFirstAid"></ol>
        </div>

        <div class="p-3 rounded-3 mb-3" style="background:var(--slate-50);border:1px solid var(--slate-200)">
          <div class="small fw-bold text-navy mb-1"><i class="bi bi-journal-medical text-teal me-1"></i>Ringkasan Anamnesis untuk Dokter</div>
          <div class="small text-muted-2 lh-base" id="resAnamnesis">—</div>
        </div>

        <!-- Konfirmasi Instan Nomor Antrean -->
        <div id="queueConfirmBox" class="queue-confirm-box mb-3 d-none">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <div class="small fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>Nomor Antrean Diterbitkan</div>
              <div class="fw-800 mono text-navy fs-3 lh-sm" id="confirmedNumber">A-000</div>
              <div class="small text-muted-2" id="confirmedPos">Silakan menunggu nomor Anda dipanggil.</div>
            </div>
            <a href="<?= url('antrean/') ?>" class="btn btn-teal btn-sm">Lihat Tiket</a>
          </div>
        </div>

        <!-- Tombol CTA Ambil Nomor Antrean UKESMAS -->
        <div class="mt-auto pt-2" id="queueActionWrap">
          <?php if ($myQ): ?>
            <a href="<?= url('antrean/') ?>" class="btn btn-teal w-100 py-2 fw-bold">
              <i class="bi bi-ticket-perforated-fill me-2"></i>Lihat Nomor Antrean Aktif Saya (<?= e($myQ['queue_number']) ?>)
            </a>
          <?php else: ?>
            <form method="post" action="<?= url('triase/ambil_antrean') ?>" id="queueSubmitForm">
              <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
              <input type="hidden" name="patient_id" id="qPatientId" value="<?= (int)($pat['id'] ?? 0) ?>">
              <input type="hidden" name="complaint" id="qComplaint" value="">
              <input type="hidden" name="triage_json" id="qTriageJson" value="">
              <button type="submit" class="btn btn-teal btn-lg w-100 py-2 fw-bold" id="btnTakeQueue" style="min-height:46px">
                <i class="bi bi-ticket-perforated-fill me-2"></i>Ambil Nomor Antrean UKESMAS
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$data['extra_js'] = <<<'JS'
<script>
(function(){
  var form = document.getElementById('studentTriageForm');
  var input = document.getElementById('complaintInput');
  var pidEl = document.getElementById('patientIdInput');
  var btnAnalyze = document.getElementById('btnAnalyze');
  var boxEmpty = document.getElementById('triageEmpty');
  var boxLoad = document.getElementById('triageLoading');
  var boxRes = document.getElementById('triageResult');

  function renderResult(t, complaint) {
    boxEmpty.classList.add('d-none');
    boxLoad.classList.add('d-none');
    boxRes.classList.remove('d-none');

    document.getElementById('resCat').textContent = t.kategori || 'Keluhan Umum';
    var src = document.getElementById('resSource');
    src.className = 'ai-badge ' + (t.source === 'gemini' ? '' : 'fallback');
    src.innerHTML = '<i class="bi bi-' + (t.source === 'gemini' ? 'heart-pulse' : 'clipboard2-check') + '"></i>' + (t.source === 'gemini' ? 'Asisten Medis (Gemini)' : 'Panduan Klinis');

    var fa = document.getElementById('resFirstAid');
    fa.innerHTML = '';
    (t.saran_awal || t.first_aid || []).slice(0, 4).forEach(function(step){
      var li = document.createElement('li');
      li.textContent = step;
      fa.appendChild(li);
    });

    document.getElementById('resAnamnesis').textContent = t.ringkasan_dokter || t.anamnesis_summary || '';

    var qPid = document.getElementById('qPatientId');
    var qComp = document.getElementById('qComplaint');
    var qJson = document.getElementById('qTriageJson');
    if (qPid) qPid.value = pidEl ? pidEl.value : 0;
    if (qComp) qComp.value = complaint;
    if (qJson) qJson.value = JSON.stringify(t);
  }

  form.addEventListener('submit', function(e){
    e.preventDefault();
    var c = input.value.trim();
    if (c.length < 5) {
      alert('Tuliskan keluhan Anda secara lebih jelas (minimal 5 karakter).');
      return;
    }
    btnAnalyze.disabled = true;
    boxEmpty.classList.add('d-none');
    boxRes.classList.add('d-none');
    boxLoad.classList.remove('d-none');

    SC.post('ai/triage', { complaint: c, patient_id: pidEl ? pidEl.value : 0 }).then(function(r){
      btnAnalyze.disabled = false;
      boxLoad.classList.add('d-none');
      if (r && r.success && r.triage) {
        renderResult(r.triage, c);
      } else {
        boxEmpty.classList.remove('d-none');
        alert((r && r.message) || 'Gagal menganalisis keluhan.');
      }
    }).catch(function(){
      btnAnalyze.disabled = false;
      boxLoad.classList.add('d-none');
      boxEmpty.classList.remove('d-none');
      alert('Terjadi gangguan koneksi. Silakan coba lagi.');
    });
  });

  var qForm = document.getElementById('queueSubmitForm');
  if (qForm) {
    qForm.addEventListener('submit', function(e){
      e.preventDefault();
      var btn = document.getElementById('btnTakeQueue');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menerbitkan Nomor…';
      SC.post('triase/ambil_antrean', {
        patient_id: document.getElementById('qPatientId').value,
        complaint: document.getElementById('qComplaint').value,
        triage_json: document.getElementById('qTriageJson').value
      }).then(function(r){
        if (r && r.success) {
          document.getElementById('queueActionWrap').classList.add('d-none');
          document.getElementById('queueConfirmBox').classList.remove('d-none');
          document.getElementById('confirmedNumber').textContent = r.queue_number;
          document.getElementById('confirmedPos').textContent = r.position > 0
            ? r.position + ' pasien di depan Anda. Silakan menunggu panggilan.'
            : 'Anda berada di urutan berikutnya.';
          if (r.live) {
            var lw = document.getElementById('liveWait'); if (lw) lw.textContent = r.live.menunggu;
            var le = document.getElementById('liveEst'); if (le) le.textContent = r.live.estimasi_menit;
            var lc = document.getElementById('liveCalled'); if (lc) lc.textContent = r.live.dipanggil || '—';
          }
          var ban = document.getElementById('activeQueueBanner');
          if (ban) {
            ban.innerHTML = '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 mb-3 rounded-3" style="background:var(--teal-50);border:1px solid var(--teal-200)">'
              + '<div class="d-flex flex-wrap align-items-center gap-2 small"><span class="badge badge-soft-primary fw-800 mono fs-6 px-3">' + SC.esc(r.queue_number) + '</span>'
              + '<span class="fw-bold text-navy">Antrean Aktif Saya</span><span class="badge badge-soft-warning">Menunggu</span>'
              + '<span class="text-muted-2">&middot; ' + (r.position > 0 ? r.position + ' pasien di depan Anda' : 'Urutan berikutnya') + '</span></div>'
              + '<a href="' + SC.base + '/antrean/" class="btn btn-teal btn-sm py-1 px-3">Lihat Tiket</a></div>';
          }
        } else {
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-ticket-perforated-fill me-2"></i>Ambil Nomor Antrean UKESMAS';
          alert((r && r.message) || 'Gagal mengambil nomor antrean.');
        }
      }).catch(function(){
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-ticket-perforated-fill me-2"></i>Ambil Nomor Antrean UKESMAS';
        alert('Koneksi bermasalah. Silakan coba lagi.');
      });
    });
  }

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
