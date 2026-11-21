<?php
require __DIR__ . '/../template/header.php';
$audit = $data['audit'];
?>

<div class="page-head">
  <div>
    <h2><i class="bi bi-shield-lock-fill text-gold me-2"></i>Monitor Keamanan WAF &amp; Log Aktivitas</h2>
    <p>Inspeksi Web Application Firewall (Anti-SQLi, Anti-XSS, Path Traversal, RCE Guard), sesi pengguna aktif, dan riwayat <code>aktivitas_log</code>.</p>
  </div>
  <span class="badge badge-soft-success fs-6"><i class="bi bi-shield-check me-1"></i>Status WAF: <?= e($audit['overall_status']) ?> (Skor <?= (int)$audit['score'] ?>/<?= (int)$audit['max_score'] ?>)</span>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card-x h-100">
      <div class="card-head"><h6 class="section-title"><i class="bi bi-shield-shaded text-gold me-1"></i>Postur Hardening Runtime &amp; WAF</h6></div>
      <div class="card-body-x">
        <div class="d-grid gap-2">
          <?php foreach ($audit['checks'] as $chk): ?>
            <div class="p-2 px-3 rounded-3 border d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-bold text-navy small"><?= e($chk['name']) ?></div>
                <div class="small text-muted-2"><?= e($chk['current']) ?></div>
              </div>
              <span class="badge badge-soft-<?= $chk['status'] === 'PASS' ? 'success' : 'warning' ?>"><?= e($chk['status']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card-x h-100">
      <div class="card-head"><h6 class="section-title"><i class="bi bi-laptop text-gold me-1"></i>Sesi Pengguna Aktif (15 Menit Terakhir)</h6></div>
      <div class="card-body-x">
        <div class="table-responsive">
          <table class="table table-modern">
            <thead><tr><th>Pengguna</th><th>Peran</th><th>Perangkat &amp; IP</th><th>Sisa Idle</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($data['sessions'] as $s): ?>
              <tr>
                <td class="fw-bold"><?= e($s['full_name']) ?></td>
                <td><span class="badge badge-soft-primary"><?= e($s['role']) ?></span></td>
                <td class="small"><?= e($s['device_type']) ?> &middot; <span class="mono"><?= e($s['ip_address']) ?></span></td>
                <td class="mono small"><?= e($s['idle_formatted']) ?></td>
                <td>
                  <?php if ($s['session_id'] !== session_id()): ?>
                    <form method="post" action="<?= url('keamanan/cabut_sesi/' . $s['id']) ?>">
                      <input type="hidden" name="csrf_token" value="<?= e($data['csrf_token']) ?>">
                      <button class="btn btn-soft btn-sm text-danger" type="submit">Cabut</button>
                    </form>
                  <?php else: ?>
                    <span class="badge badge-soft-success">Sesi Anda</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Log Aktivitas Pengguna -->
<div class="card-x mb-4">
  <div class="card-head"><h6 class="section-title"><i class="bi bi-list-check text-gold me-1"></i>Log Aktivitas Sistem (<code>aktivitas_log</code>)</h6></div>
  <div class="card-body-x">
    <div class="table-responsive">
      <table class="table table-modern">
        <thead><tr><th>Waktu</th><th>Username</th><th>Aktivitas</th><th>Detail</th><th>IP Address</th></tr></thead>
        <tbody>
          <?php foreach ($data['activities'] as $a): ?>
          <tr>
            <td class="small"><?= e(tgl($a['created_at'], true)) ?></td>
            <td class="mono fw-bold"><?= e($a['username']) ?></td>
            <td><span class="badge badge-soft-info"><?= e($a['aktivitas']) ?></span></td>
            <td class="small"><?= e($a['detail']) ?></td>
            <td class="mono small"><?= e($a['ip_address']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Insiden WAF -->
<div class="card-x">
  <div class="card-head"><h6 class="section-title"><i class="bi bi-bug-fill text-danger me-1"></i>Insiden yang Diblokir WAF (<code>security_incidents</code>)</h6></div>
  <div class="card-body-x">
    <?php if ($data['incidents']): ?>
      <div class="table-responsive">
        <table class="table table-modern">
          <thead><tr><th>Waktu</th><th>Vektor Ancaman</th><th>IP Sumber</th><th>URI</th><th>Tindakan</th></tr></thead>
          <tbody>
            <?php foreach ($data['incidents'] as $inc): ?>
            <tr>
              <td class="small"><?= e(tgl($inc['created_at'], true)) ?></td>
              <td><span class="badge badge-soft-danger"><?= e($inc['incident_type']) ?></span></td>
              <td class="mono small"><?= e($inc['source_ip']) ?></td>
              <td class="mono small"><?= e($inc['request_uri']) ?></td>
              <td><span class="badge badge-soft-secondary"><?= e($inc['action_taken']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state"><i class="bi bi-shield-check"></i>Tidak ada serangan SQLi/XSS/RCE yang terdeteksi.</div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
