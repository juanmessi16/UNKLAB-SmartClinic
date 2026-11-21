<?php
require __DIR__ . '/../template/header.php';
$st  = $data['settings'];
$gem = $data['gemini'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-sliders text-gold me-2"></i>Pengaturan Sistem &amp; Konfigurasi Google Gemini AI</h2>
    <p>Atur parameter operasional klinik UKESMAS serta API Key dan model Google Gemini AI.</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <form class="card-x" method="post" action="<?= url('pengaturan/simpan') ?>">
      <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
      <div class="card-head">
        <h6 class="section-title"><i class="bi bi-cpu text-teal me-1"></i>Konfigurasi Google Gemini AI</h6>
        <span class="ai-badge <?= $gem['configured'] ? '' : 'fallback' ?>">
          <?= $gem['configured'] ? 'Aktif (' . e($gem['key_hint']) . ')' : 'Mode Intelligent Fallback Aktif' ?>
        </span>
      </div>
      <div class="card-body-x">
        <div class="mb-3">
          <label class="form-label">Google Gemini API Key</label>
          <input type="password" class="form-control mono" name="gemini_api_key" placeholder="<?= $gem['key_hint'] ? 'Tersimpan: ' . e($gem['key_hint']) . ' (isi untuk mengganti)' : 'Masukkan API Key dari Google AI Studio (AIzaSy...)' ?>">
          <div class="form-text">Kosongkan bila tidak ingin mengubah key yang sudah tersimpan. Jika dikosongkan/limit, sistem otomatis menggunakan <em>Intelligent Contextual Fallback</em> agar demo berjalan mulus tanpa error.</div>
        </div>
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Model Gemini</label>
            <select class="form-select mono" name="gemini_model">
              <?php foreach (['gemini-3.5-flash-lite', 'gemini-flash-lite-latest', 'gemini-3.5-flash', 'gemini-3.8-flash', 'gemini-flash-latest'] as $m): ?>
                <option value="<?= $m ?>" <?= ($st['gemini_model'] ?? 'gemini-3.5-flash-lite') === $m ? 'selected' : '' ?>><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status Integrasi Gemini</label>
            <select class="form-select" name="gemini_enabled">
              <option value="1" <?= ($st['gemini_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Aktif (Gunakan REST API Gemini)</option>
              <option value="0" <?= ($st['gemini_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Nonaktif (Pakai Contextual Fallback)</option>
            </select>
          </div>
        </div>

        <hr>
        <h6 class="section-title mb-3"><i class="bi bi-hospital text-gold me-1"></i>Parameter Operasional UKESMAS</h6>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nama Aplikasi</label><input type="text" class="form-control" name="app_name" value="<?= e($st['app_name'] ?? APP_NAME) ?>"></div>
          <div class="col-md-6"><label class="form-label">Instansi</label><input type="text" class="form-control" name="instansi" value="<?= e($st['instansi'] ?? '') ?>"></div>
          <div class="col-md-8"><label class="form-label">Jam Operasional Klinik</label><input type="text" class="form-control" name="jam_operasional" value="<?= e($st['jam_operasional'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Kuota Izin Sakit / Semester (Hari)</label><input type="number" class="form-control" name="kuota_izin_sakit" value="<?= (int)($st['kuota_izin_sakit'] ?? 10) ?>"></div>
          <div class="col-md-6"><label class="form-label">Email Layanan</label><input type="email" class="form-control" name="kontak_email" value="<?= e($st['kontak_email'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Telepon Darurat UKESMAS</label><input type="text" class="form-control" name="kontak_telp" value="<?= e($st['kontak_telp'] ?? '') ?>"></div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-navy"><i class="bi bi-check2-circle me-1"></i>Simpan Pengaturan</button>
        </div>
      </div>
    </form>
  </div>

  <div class="col-lg-5">
    <div class="card-x">
      <div class="card-head"><h6 class="section-title"><i class="bi bi-cpu text-gold me-1"></i>Statistik Pemanggilan Modul AI (<code>ai_analytics_logs</code>)</h6></div>
      <div class="card-body-x">
        <?php if ($data['ai_usage']): ?>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead><tr><th>Fitur AI</th><th>Sumber Mesin</th><th>Total Eksekusi</th></tr></thead>
              <tbody>
                <?php foreach ($data['ai_usage'] as $u): ?>
                <tr>
                  <td class="fw-bold text-navy"><?= e($u['jenis']) ?></td>
                  <td><span class="badge badge-soft-<?= $u['sumber'] === 'gemini' ? 'primary' : 'secondary' ?>"><?= e($u['sumber']) ?></span></td>
                  <td class="fw-bold"><?= (int)$u['n'] ?>x</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
