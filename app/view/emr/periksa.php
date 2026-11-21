<?php
require __DIR__ . '/../template/header.php';
$q  = $data['queue'];
$tj = $data['triage'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-clipboard2-pulse-fill text-gold me-2"></i>Pemeriksaan Medis &amp; Input EMR</h2>
    <p>Antrean <strong><?= e($q['queue_number']) ?></strong> &mdash; <?= e($q['nama']) ?> (<?= e($q['nim_nip']) ?>)</p>
  </div>
  <a href="<?= url('antrean/') ?>" class="btn btn-soft"><i class="bi bi-arrow-left me-1"></i>Kembali ke Antrean</a>
</div>

<div class="row g-4">
  <!-- Kolom Kiri: Profil Pasien & Ringkasan Triase AI -->
  <div class="col-lg-4">
    <div class="card-x mb-3">
      <div class="card-head">
        <h6 class="section-title"><i class="bi bi-person-vcard text-gold me-1"></i>Data Pasien</h6>
        <?= category_badge($q['triage_category'] ?: 'Keluhan Umum') ?>
      </div>
      <div class="card-body-x">
        <div class="fw-bold fs-5 text-navy"><?= e($q['nama']) ?></div>
        <div class="small text-muted-2 mb-3">NIM / NIP: <strong><?= e($q['nim_nip']) ?></strong></div>
        <?php if (!empty($q['penyakit_kronis'])): ?>
          <div class="alert alert-warning py-2 px-3 small mb-2">
            <strong>Komorbid / Kronis:</strong> <?= e($q['penyakit_kronis']) ?>
          </div>
        <?php endif; ?>

        <hr>
        <div class="small fw-bold text-navy mb-1"><i class="bi bi-clipboard2-pulse text-teal me-1"></i>Ringkasan Konsultasi Awal</div>
        <div class="p-3 rounded-3 small mb-2" style="background:var(--slate-50);border:1px solid var(--slate-200)">
          <div class="mb-1"><strong>Keluhan:</strong> <?= e($q['complaint']) ?></div>
          <div class="mb-1"><strong>Estimasi Kategori:</strong> <?= e($q['triage_category'] ?: ($tj['kategori'] ?? '-')) ?></div>
          <div><strong>Ringkasan untuk Dokter:</strong> <?= e($tj['ringkasan_dokter'] ?? ($tj['anamnesis_summary'] ?? '-')) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Kolom Kanan: Form Input EMR + Resep + Surat Sakit -->
  <div class="col-lg-8">
    <form method="post" action="<?= url('emr/simpan') ?>" id="emrForm">
      <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
      <input type="hidden" name="queue_id" value="<?= (int)$q['id'] ?>">

      <!-- 1. Tanda Vital -->
      <div class="card-x mb-3">
        <div class="card-head"><h6 class="section-title"><i class="bi bi-activity text-danger me-1"></i>1. Pemeriksaan Tanda Vital (Vital Signs)</h6></div>
        <div class="card-body-x">
          <div class="row g-3">
            <div class="col-6 col-md-3">
              <label class="form-label">TD Sistolik (mmHg)</label>
              <input type="number" class="form-control" name="tekanan_sistolik" value="120" min="60" max="250" required>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label">TD Diastolik (mmHg)</label>
              <input type="number" class="form-control" name="tekanan_diastolik" value="80" min="40" max="160" required>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label">Suhu Tubuh (&deg;C)</label>
              <input type="number" step="0.1" class="form-control" name="suhu" value="37.2" min="34" max="43" required>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label">Detak Jantung (bpm)</label>
              <input type="number" class="form-control" name="detak_jantung" value="82" min="40" max="200" required>
            </div>
            <div class="col-4">
              <label class="form-label">Respirasi (/menit)</label>
              <input type="number" class="form-control" name="respirasi" value="18" min="8" max="50">
            </div>
            <div class="col-4">
              <label class="form-label">Saturasi O2 (%)</label>
              <input type="number" class="form-control" name="saturasi_o2" value="98" min="70" max="100">
            </div>
            <div class="col-4">
              <label class="form-label">Berat Badan (kg)</label>
              <input type="number" step="0.1" class="form-control" name="berat_badan" value="58">
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Anamnesis & Diagnosis -->
      <div class="card-x mb-3">
        <div class="card-head flex-wrap gap-2">
          <div>
            <h6 class="section-title mb-0"><i class="bi bi-journal-medical text-teal me-1"></i>2. Anamnesis (SOAP), Diagnosis &amp; Tindakan Medis</h6>
            <div class="section-sub">Gunakan AI Clinical Assistant untuk menyusun draf SOAP otomatis dari keluhan &amp; tanda vital</div>
          </div>
          <button type="button" class="btn btn-teal btn-sm px-3 py-2" id="btnGenerateSoap">
            <i class="bi bi-clipboard2-pulse me-1"></i>Buat Draft SOAP dengan AI
          </button>
        </div>
        <div class="card-body-x">
          <div id="soapAiNotice" class="alert py-2 px-3 small d-none mb-3" style="background:var(--teal-50);border:1px solid var(--teal-200);color:var(--teal-700)" role="status"></div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Keluhan Utama</label>
              <input type="text" class="form-control" id="emrKeluhan" name="keluhan_utama" value="<?= e($q['complaint']) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Catatan Klinis / Draft SOAP (Subjective, Objective, Assessment, Plan — dapat disunting dokter)</label>
              <textarea class="form-control" id="emrAnamnesis" name="anamnesis" rows="5" style="line-height:1.55"><?= e($tj['ringkasan_dokter'] ?? ($tj['anamnesis_summary'] ?? ('Pasien mengeluh: ' . $q['complaint']))) ?></textarea>
            </div>
            <div class="col-md-5">
              <label class="form-label">Diagnosis Dokter</label>
              <input type="text" class="form-control" id="emrDiagnosis" name="diagnosis" placeholder="Contoh: Infeksi Saluran Pernapasan Akut (ISPA)" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Kode ICD-10</label>
              <input type="text" class="form-control mono" id="emrIcd" name="kode_icd" placeholder="J06.9 / K29.7">
            </div>
            <div class="col-md-4">
              <label class="form-label">Kategori Penyakit</label>
              <select class="form-select" id="emrKategori" name="kategori_penyakit">
                <?php foreach (['ISPA', 'Demam', 'Gastritis', 'Diare', 'Sakit Kepala', 'Cedera', 'Dermatitis', 'Lainnya'] as $cat): ?>
                  <option value="<?= $cat ?>" <?= ($q['triage_category'] === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-12">
              <label class="form-label">Tindakan / Rencana Penanganan &amp; Edukasi Medis (Plan)</label>
              <input type="text" class="form-control" id="emrTindakan" name="tindakan" value="Edukasi istirahat cukup, hidrasi, dan kontrol bila keluhan menetap >3 hari.">
            </div>
            <div class="col-12">
              <label class="form-label">Catatan Tambahan</label>
              <input type="text" class="form-control" name="catatan" placeholder="Opsional">
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Resep Digital (Otomatis Potong Stok) -->
      <div class="card-x mb-3">
        <div class="card-head">
          <div>
            <h6 class="section-title"><i class="bi bi-capsule-pill text-gold me-1"></i>3. Resep Obat Digital (Otomatis Memotong Stok Apotek)</h6>
            <div class="section-sub">Pilih obat dan jumlahnya; saldo obat di apotek akan berkurang secara otomatis</div>
          </div>
          <button type="button" class="btn btn-soft btn-sm" id="btnAddRx"><i class="bi bi-plus-lg me-1"></i>Tambah Baris Obat</button>
        </div>
        <div class="card-body-x">
          <div id="rxContainer" class="d-grid gap-2">
            <div class="row g-2 align-items-center rx-row">
              <div class="col-md-5">
                <select class="form-select" name="rx_medicine_id[]">
                  <option value="0">— Pilih Obat Apotek —</option>
                  <?php foreach ($data['medicines'] as $m): ?>
                    <option value="<?= (int)$m['id'] ?>"><?= e($m['nama']) ?> (Stok: <?= (int)$m['stok'] ?> <?= e($m['satuan']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <input type="number" class="form-control" name="rx_jumlah[]" placeholder="Jml" min="1" max="100" value="10">
              </div>
              <div class="col-md-4">
                <input type="text" class="form-control" name="rx_aturan[]" placeholder="Aturan pakai" value="3x1 sesudah makan">
              </div>
              <div class="col-md-1 text-end">
                <button type="button" class="btn btn-soft btn-sm text-danger btn-remove-rx"><i class="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Penerbitan Surat Keterangan Sakit -->
      <div class="card-x mb-4">
        <div class="card-head"><h6 class="section-title"><i class="bi bi-file-earmark-medical-fill text-gold me-1"></i>4. Penerbitan Surat Keterangan Sakit Digital (Opsional)</h6></div>
        <div class="card-body-x">
          <div class="row align-items-center g-3">
            <div class="col-md-5">
              <label class="form-label">Durasi Istirahat / Izin Sakit</label>
              <select class="form-select" name="jumlah_hari_izin">
                <option value="0">Tidak memerlukan surat sakit</option>
                <option value="1">1 Hari (Hari ini)</option>
                <option value="2">2 Hari</option>
                <option value="3">3 Hari</option>
                <option value="5">5 Hari</option>
              </select>
            </div>
            <div class="col-md-7 small text-muted-2">
              Jika dipilih &gt; 0 hari, sistem otomatis menerbitkan <strong>Surat Keterangan Sakit Digital</strong> dengan kode verifikasi unik (<code>SKS-XXXX-XXXX</code>) dan QR Code untuk dosen.
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-gold btn-lg px-4"><i class="bi bi-check2-circle me-2"></i>Simpan EMR, Potong Stok &amp; Selesaikan Pemeriksaan</button>
      </div>
    </form>
  </div>
</div>

<?php
$data['extra_js'] = <<<'JS'
<script>
(function(){
  var box = document.getElementById('rxContainer');
  var btn = document.getElementById('btnAddRx');
  btn.addEventListener('click', function(){
    var first = box.querySelector('.rx-row');
    var clone = first.cloneNode(true);
    clone.querySelector('select').selectedIndex = 0;
    clone.querySelector('input[type="number"]').value = 10;
    clone.querySelector('input[type="text"]').value = '3x1 sesudah makan';
    box.appendChild(clone);
  });
  box.addEventListener('click', function(e){
    var rm = e.target.closest('.btn-remove-rx');
    if (!rm) return;
    var rows = box.querySelectorAll('.rx-row');
    if (rows.length > 1) rm.closest('.rx-row').remove();
    else rm.closest('.rx-row').querySelector('select').selectedIndex = 0;
  });

  // AI Clinical Assistant: Buat Draft SOAP Otomatis
  var btnSoap = document.getElementById('btnGenerateSoap');
  var noticeEl = document.getElementById('soapAiNotice');
  var formEl = document.getElementById('emrForm');

  function valOf(name) {
    var el = formEl ? formEl.querySelector('[name="' + name + '"]') : null;
    return el ? el.value : '';
  }

  function flashField(el) {
    if (!el) return;
    el.style.transition = 'background-color .4s ease, border-color .4s ease';
    el.style.backgroundColor = '#f0fdfa';
    el.style.borderColor = '#0f766e';
    setTimeout(function(){
      el.style.backgroundColor = '';
      el.style.borderColor = '';
    }, 1800);
  }

  if (btnSoap && formEl) {
    btnSoap.addEventListener('click', function(){
      var keluhan = (valOf('keluhan_utama') || '').trim();
      if (keluhan.length < 3) {
        if (noticeEl) {
          noticeEl.className = 'alert alert-warning py-2 px-3 small mb-3';
          noticeEl.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Isi <strong>Keluhan Utama</strong> terlebih dahulu sebelum membuat draft SOAP.';
        }
        return;
      }

      btnSoap.disabled = true;
      btnSoap.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyusun Draft SOAP...';
      if (noticeEl) noticeEl.classList.add('d-none');

      SC.post('emr/generate_soap', {
        queue_id:          valOf('queue_id'),
        keluhan_utama:     keluhan,
        tekanan_sistolik:  valOf('tekanan_sistolik'),
        tekanan_diastolik: valOf('tekanan_diastolik'),
        suhu:              valOf('suhu'),
        detak_jantung:     valOf('detak_jantung'),
        respirasi:         valOf('respirasi'),
        saturasi_o2:       valOf('saturasi_o2'),
        berat_badan:       valOf('berat_badan')
      }).then(function(r){
        btnSoap.disabled = false;
        btnSoap.innerHTML = '<i class="bi bi-clipboard2-pulse me-1"></i>Buat Draft SOAP dengan AI';

        if (!r || !r.success || !r.soap) {
          if (noticeEl) {
            noticeEl.className = 'alert alert-danger py-2 px-3 small mb-3';
            noticeEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>' + SC.esc((r && r.message) || 'Gagal menyusun draft SOAP. Silakan coba lagi.');
          }
          return;
        }

        var s = r.soap;
        var fAnam = document.getElementById('emrAnamnesis');
        var fDiag = document.getElementById('emrDiagnosis');
        var fIcd  = document.getElementById('emrIcd');
        var fKat  = document.getElementById('emrKategori');
        var fTind = document.getElementById('emrTindakan');

        if (fAnam && s.soap_text) { fAnam.value = s.soap_text; flashField(fAnam); }
        if (fDiag && s.diagnosis_singkat) { fDiag.value = s.diagnosis_singkat; flashField(fDiag); }
        if (fIcd && s.kode_icd) { fIcd.value = s.kode_icd; flashField(fIcd); }
        if (fKat && s.kategori_penyakit) {
          for (var i = 0; i < fKat.options.length; i++) {
            if (fKat.options[i].value === s.kategori_penyakit) {
              fKat.selectedIndex = i;
              flashField(fKat);
              break;
            }
          }
        }
        if (fTind && s.plan) { fTind.value = s.plan; flashField(fTind); }

        if (noticeEl) {
          var srcLabel = s.source === 'gemini' ? 'Google Gemini AI (' + SC.esc(s.model || '') + ')' : 'AI Clinical Assistant';
          noticeEl.className = 'alert py-2 px-3 small mb-3';
          noticeEl.style.background = 'var(--teal-50)';
          noticeEl.style.border = '1px solid var(--teal-200)';
          noticeEl.style.color = 'var(--teal-700)';
          noticeEl.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i><strong>Draft SOAP berhasil disusun</strong> oleh ' + srcLabel + '. Silakan tinjau dan sunting hasil di bawah sesuai pemeriksaan klinis Anda sebelum menyimpan.';
        }
      }).catch(function(){
        btnSoap.disabled = false;
        btnSoap.innerHTML = '<i class="bi bi-clipboard2-pulse me-1"></i>Buat Draft SOAP dengan AI';
        if (noticeEl) {
          noticeEl.className = 'alert alert-danger py-2 px-3 small mb-3';
          noticeEl.innerHTML = '<i class="bi bi-wifi-off me-1"></i>Koneksi bermasalah saat menghubungi AI Clinical Assistant.';
        }
      });
    });
  }
})();
</script>
JS;
require __DIR__ . '/../template/footer.php';
