<?php
require __DIR__ . '/../template/header.php';
$agg = $data['aggregate'];
$fc  = $data['forecast'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-graph-up-arrow text-teal me-2"></i>AI Health Analytics &amp; Forecast</h2>
    <p>Ringkasan eksekutif dan peringatan dini farmasi serta epidemiologi berdasarkan data agregat 14 hari terakhir (<code><?= e($agg['periode']) ?></code>).</p>
  </div>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <span class="ai-badge <?= ($fc['source'] ?? '') === 'gemini' ? '' : 'fallback' ?>" id="fcBadge">
      <i class="bi bi-heart-pulse"></i><?= ($fc['source'] ?? '') === 'gemini' ? 'Google Gemini (' . e($fc['model'] ?? '') . ')' : 'Statistical & Clinical Forecaster' ?>
    </span>
    <button type="button" class="btn btn-teal" id="btnRunForecast">
      <i class="bi bi-arrow-repeat me-1"></i>Jalankan Analisis AI Terbaru
    </button>
  </div>
</div>

<div class="row g-4">
  <!-- Ringkasan Eksekutif Analitik UKESMAS (Full Width) -->
  <div class="col-12">
    <div class="card-x p-4" style="background:linear-gradient(135deg,var(--teal-50) 0%,#ffffff 100%);border:1px solid var(--teal-200)">
      <div class="d-flex align-items-start gap-3">
        <div class="stat-icon teal flex-shrink-0" style="width:50px;height:50px;font-size:1.35rem">
          <i class="bi bi-clipboard2-data-fill"></i>
        </div>
        <div class="flex-grow-1">
          <div class="small text-uppercase fw-bold text-teal mb-1" style="letter-spacing:.08em">Ringkasan Eksekutif Analitik UKESMAS</div>
          <p class="fs-6 mb-3 mt-1 text-navy" id="fcSummary" style="line-height:1.65"><?= e($fc['ringkasan'] ?? '') ?></p>
          <div class="d-flex flex-wrap gap-2" id="fcOps">
            <?php foreach (($fc['saran_operasional'] ?? []) as $op): ?>
              <span class="badge badge-soft-primary rounded-pill py-2 px-3">
                <i class="bi bi-check2-circle me-1"></i><?= e($op) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Peringatan Dini Farmasi & Epidemiologi (Full Width) -->
  <div class="col-12">
    <div class="card-x">
      <div class="card-head">
        <div>
          <h6 class="section-title"><i class="bi bi-bell-fill text-danger me-2"></i>Peringatan Dini Farmasi &amp; Epidemiologi</h6>
          <div class="section-sub">Deteksi otomatis stok obat kritis, kedaluwarsa, dan potensi peningkatan kasus klinis di kampus</div>
        </div>
      </div>
      <div class="card-body-x">
        <div class="d-grid gap-2" id="fcWarnings">
          <?php if (!empty($fc['peringatan'])): ?>
            <?php foreach ($fc['peringatan'] as $w): ?>
              <div class="p-3 rounded-3 small d-flex align-items-start gap-2" style="background:#fff1f2;border:1px solid #fecdd3;color:#be123c">
                <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
                <span><?= e($w) ?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="p-3 rounded-3 small d-flex align-items-center gap-2" style="background:var(--teal-50);border:1px solid var(--teal-200);color:var(--teal-700)">
              <i class="bi bi-shield-check fs-5"></i>
              <span>Tidak ada peringatan kritis farmasi maupun epidemiologi untuk periode saat ini.</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$data['extra_js'] = <<<'HTML'
<script>
(function(){
  var btn = document.getElementById('btnRunForecast');
  if (!btn) return;

  btn.addEventListener('click', function(){
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses dengan AI...';

    SC.post('ai/forecast', {}).then(function(r){
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Jalankan Analisis AI Terbaru';

      if (r && r.success && r.forecast) {
        var f = r.forecast;
        var sumEl = document.getElementById('fcSummary');
        if (sumEl) sumEl.textContent = f.ringkasan || '';

        var b = document.getElementById('fcBadge');
        if (b) {
          b.className = 'ai-badge ' + (f.source === 'gemini' ? '' : 'fallback');
          b.innerHTML = '<i class="bi bi-heart-pulse"></i>' + (f.source === 'gemini' ? 'Google Gemini (' + SC.esc(f.model || '') + ')' : 'Statistical & Clinical Forecaster');
        }

        var opsEl = document.getElementById('fcOps');
        if (opsEl && Array.isArray(f.saran_operasional)) {
          opsEl.innerHTML = f.saran_operasional.map(function(op){
            return '<span class="badge badge-soft-primary rounded-pill py-2 px-3"><i class="bi bi-check2-circle me-1"></i>' + SC.esc(op) + '</span>';
          }).join('');
        }

        var warnEl = document.getElementById('fcWarnings');
        if (warnEl) {
          if (Array.isArray(f.peringatan) && f.peringatan.length > 0) {
            warnEl.innerHTML = f.peringatan.map(function(w){
              return '<div class="p-3 rounded-3 small d-flex align-items-start gap-2" style="background:#fff1f2;border:1px solid #fecdd3;color:#be123c">'
                + '<i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>'
                + '<span>' + SC.esc(w) + '</span></div>';
            }).join('');
          } else {
            warnEl.innerHTML = '<div class="p-3 rounded-3 small d-flex align-items-center gap-2" style="background:var(--teal-50);border:1px solid var(--teal-200);color:var(--teal-700)">'
              + '<i class="bi bi-shield-check fs-5"></i>'
              + '<span>Tidak ada peringatan kritis farmasi maupun epidemiologi untuk periode saat ini.</span></div>';
          }
        }
      }
    }).catch(function(){
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Jalankan Analisis AI Terbaru';
    });
  });
})();
</script>
HTML;
require __DIR__ . '/../template/footer.php';
