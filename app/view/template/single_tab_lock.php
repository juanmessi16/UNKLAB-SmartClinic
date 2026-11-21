<?php
/**
 * Single-Tab Session Lock Overlay & BroadcastChannel Logic - UNKLAB SmartClinic
 * Clean Clinical UI (Medical Teal)
 *
 * Mengunci duplikat tab untuk akun yang sama (user_id & role yang sama).
 * Mengabaikan sinyal kunci jika pengguna membuka akun/peran yang berbeda di tab lain.
 */
if (empty($_SESSION['user-id'])) {
    return;
}

$currentUserId       = (int)$_SESSION['user-id'];
$currentUserRole     = (string)($_SESSION['user-role'] ?? '');
$currentSessionToken = (string)($_SESSION['session_token'] ?? '');
?>
<!-- Overlay Kunci Sesi Tab Duplikat -->
<div id="scTabLockOverlay" class="d-none" style="position:fixed;inset:0;z-index:999999;background:rgba(15,23,42,.72);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:1.25rem;">
  <div class="card-x border-0 shadow-lg text-center" style="max-width:440px;width:100%;border-radius:18px;padding:2rem;background:#ffffff;box-shadow:0 25px 50px -12px rgba(15,23,42,.28);">
    <div style="width:68px;height:68px;border-radius:18px;background:var(--teal-50,#F0FDFA);border:1px solid var(--teal-200,#CCFBF1);color:var(--teal-700,#0F766E);display:flex;align-items:center;justify-content:center;font-size:1.85rem;margin:0 auto 1.25rem;">
      <i class="bi bi-shield-lock-fill"></i>
    </div>
    <h4 class="fw-800 text-navy mb-2" style="font-size:1.25rem;color:#0F172A">Sesi Aktif di Tab Lain</h4>
    <p class="text-muted-2 small mb-4" style="color:#64748B;line-height:1.55;font-size:.9rem">
      Akun ini sedang aktif di tab lain. Satu akun hanya dapat digunakan pada satu halaman/perangkat dalam satu waktu.
    </p>
    <div class="d-flex flex-column gap-2">
      <button type="button" class="btn btn-teal w-100 py-2 fs-6 fw-semibold" id="btnScClaimTab">
        <i class="bi bi-arrow-repeat me-2"></i>Gunakan Halaman Ini
      </button>
      <a href="<?= url('login/logout') ?>" class="btn btn-soft w-100 py-2 fs-6 fw-semibold text-danger" style="background:#FFF1F2;border:1px solid #FECDD3;color:#BE123C">
        <i class="bi bi-box-arrow-right me-2"></i>Keluar
      </a>
    </div>
  </div>
</div>

<script>
(function(){
  var currentUserId       = <?= (int)$currentUserId ?>;
  var currentUserRole     = <?= json_encode($currentUserRole) ?>;
  var currentSessionToken = <?= json_encode($currentSessionToken) ?>;
  if (!currentUserId) return;

  // ID unik per-tab dalam konteks halaman saat ini
  var myTabId = 'tab_' + Math.random().toString(36).substring(2, 10) + '_' + Date.now();
  var overlay = document.getElementById('scTabLockOverlay');
  var btnClaim = document.getElementById('btnScClaimTab');
  var isLocked = false;
  var channel = null;
  var accountSwitched = false;

  function showLock() {
    if (isLocked) return;
    isLocked = true;
    if (overlay) {
      overlay.classList.remove('d-none');
      overlay.style.display = 'flex';
    }
    document.body.style.overflow = 'hidden';
  }

  function hideLock() {
    if (!isLocked) return;
    isLocked = false;
    if (overlay) {
      overlay.classList.add('d-none');
      overlay.style.display = 'none';
    }
    document.body.style.overflow = '';
  }

  function makePayload(type) {
    return {
      type:          type,
      tabId:         myTabId,
      user_id:       currentUserId,
      role:          currentUserRole,
      session_token: currentSessionToken,
      time:          Date.now()
    };
  }

  function broadcast(type) {
    var payload = makePayload(type);
    if (channel) {
      try {
        channel.postMessage(payload);
      } catch (e) {}
    }
    try {
      localStorage.setItem('sc_tab_session_event', JSON.stringify(payload));
    } catch (e) {}
  }

  function takeOver() {
    hideLock();
    // Kirim sinyal TAKE_OVER dengan user_id, role, dan session_token
    broadcast('TAKE_OVER');
  }

  if (btnClaim) {
    btnClaim.addEventListener('click', function(e){
      e.preventDefault();
      takeOver();
    });
  }

  function handleMessage(msg) {
    if (!msg) return;

    var msgUserId = msg.user_id !== undefined ? msg.user_id : msg.userId;
    var msgRole   = msg.role || '';

    // ATURAN 1: Filter Ketat Single-Tab Lock
    // Tab lama HANYA boleh menampilkan modal "Sesi Aktif di Tab Lain"
    // jika pesan yang masuk memiliki user_id DAN role yang sama persis (dan tabId berbeda)
    if (msgUserId === currentUserId && msgRole === currentUserRole && msg.tabId !== myTabId) {
      if (msg.type === 'TAB_OPENED' || msg.type === 'TAKE_OVER' || msg.type === 'TAB_CLAIMED') {
        showLock();
      }
      return;
    }

    // ATURAN 2: Deteksi pergantian akun di browser yang sama
    // Jika user_id atau role berbeda: abaikan sinyal kunci tab (JANGAN tampilkan modal)
    // dan catat bahwa akun aktif di browser ini telah berganti ke pengguna lain.
    if (msgUserId !== currentUserId || (msgRole && msgRole !== currentUserRole)) {
      accountSwitched = true;
    }
  }

  // Jika akun telah berganti di tab lain, arahkan tab lama secara bersih ke dashboard akun baru saat diklik / difokuskan
  function redirectIfAccountSwitched(e) {
    if (accountSwitched) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      window.location.href = (window.APP_BASE || '') + '/dashboard/';
    }
  }

  document.addEventListener('click', function(e) {
    if (accountSwitched) {
      redirectIfAccountSwitched(e);
    }
  }, true);

  window.addEventListener('focus', function() {
    if (accountSwitched) {
      redirectIfAccountSwitched();
    }
  });

  // Komunikasi lintas tab dengan BroadcastChannel
  if (typeof BroadcastChannel !== 'undefined') {
    try {
      channel = new BroadcastChannel('unklab_smartclinic_session');
      channel.onmessage = function(ev) {
        handleMessage(ev.data);
      };

      // Beritahu tab lain bahwa tab baru dibuka untuk akun & role ini
      broadcast('TAB_OPENED');
    } catch (err) {
      channel = null;
    }
  }

  // Fallback sinkronisasi via window storage event
  window.addEventListener('storage', function(e) {
    if (e.key === 'sc_tab_session_event' && e.newValue) {
      try {
        var data = JSON.parse(e.newValue);
        handleMessage(data);
      } catch (ex) {}
    }
  });

  // Notifikasi saat tab ini ditutup
  window.addEventListener('beforeunload', function() {
    if (channel && !isLocked) {
      try {
        broadcast('TAB_CLOSED');
      } catch (e) {}
    }
  });
})();
</script>
