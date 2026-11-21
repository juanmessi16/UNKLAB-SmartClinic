<?php
/**
 * Notifikasi Panggilan Antrean Persisten + Voice Announcement (Khusus Mahasiswa & Dosen)
 * Hanya dimuat jika pengguna login dengan role 'student' atau 'lecturer'.
 */
$qRole = $_SESSION['user-role'] ?? '';
if (!in_array($qRole, ['student', 'lecturer'], true)) {
    return;
}
?>
<style>
  .qcall-modal .modal-content {
    border: 1px solid #CCFBF1;
    border-radius: 18px;
    box-shadow: 0 24px 60px -12px rgba(15, 23, 42, .22);
    overflow: hidden;
  }
  .qcall-header {
    background: linear-gradient(135deg, #F0FDFA 0%, #FFFFFF 100%);
    border-bottom: 1px solid #CCFBF1;
    padding: 1.5rem 1.5rem 1.25rem;
    text-align: center;
  }
  .qcall-icon-wrap {
    width: 68px;
    height: 68px;
    margin: 0 auto .9rem;
    border-radius: 50%;
    background: #0F766E;
    color: #FFFFFF;
    display: grid;
    place-items: center;
    font-size: 1.85rem;
    position: relative;
    box-shadow: 0 8px 20px rgba(15, 118, 110, .28);
  }
  .qcall-icon-wrap::before,
  .qcall-icon-wrap::after {
    content: "";
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    border: 2px solid rgba(15, 118, 110, .38);
    animation: qcallPulse 1.8s cubic-bezier(.2, .6, .35, 1) infinite;
  }
  .qcall-icon-wrap::after {
    animation-delay: .9s;
  }
  @keyframes qcallPulse {
    0%   { transform: scale(.95); opacity: .9; }
    100% { transform: scale(1.48); opacity: 0; }
  }
  .qcall-number-box {
    background: #F0FDFA;
    border: 1.5px solid #99F6E4;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    margin: 1rem 0;
    text-align: center;
  }
  .qcall-number-label {
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #0F766E;
    margin-bottom: .25rem;
  }
  .qcall-number-val {
    font-family: 'JetBrains Mono', monospace;
    font-size: 2.75rem;
    font-weight: 800;
    line-height: 1.1;
    color: #0F766E;
    letter-spacing: .04em;
  }
  .qcall-cat-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    margin-top: .5rem;
    padding: .25rem .75rem;
    border-radius: 999px;
    background: #FFFFFF;
    border: 1px solid #CCFBF1;
    color: #0F766E;
    font-size: .78rem;
    font-weight: 600;
  }
  .qcall-sound-btn {
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155;
    font-size: .82rem;
    font-weight: 600;
    border-radius: 10px;
    padding: .42rem .9rem;
    transition: all .15s ease;
  }
  .qcall-sound-btn:hover {
    background: #F0FDFA;
    border-color: #99F6E4;
    color: #0F766E;
  }
</style>

<!-- Modal Notifikasi Panggilan Antrean Persisten (Tanpa Tombol Close X) -->
<div class="modal fade qcall-modal" id="queueCallModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="queueCallTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="qcall-header">
        <div class="qcall-icon-wrap" aria-hidden="true">
          <i class="bi bi-megaphone-fill"></i>
        </div>
        <span class="badge rounded-pill mb-2" style="background:#CCFBF1;color:#0F766E;font-weight:700;padding:.35rem .8rem">
          <i class="bi bi-broadcast me-1"></i>Panggilan Ruang Periksa UKESMAS
        </span>
        <h5 class="fw-bold mb-0" id="queueCallTitle" style="color:#0F172A">Giliran Anda Telah Tiba!</h5>
      </div>
      <div class="modal-body px-4 pt-3 pb-4 text-center">
        <div class="qcall-number-box">
          <div class="qcall-number-label">Nomor Antrean Dipanggil</div>
          <div class="qcall-number-val" id="qcallNumber">A-001</div>
          <div>
            <span class="qcall-cat-badge" id="qcallCategoryWrap">
              <i class="bi bi-clipboard2-pulse"></i>
              <span id="qcallCategory">Pemeriksaan Umum</span>
            </span>
          </div>
        </div>

        <p class="mb-3" style="color:#334155;font-size:.94rem;line-height:1.55">
          <strong>Giliran Anda!</strong> Nomor antrean Anda telah dipanggil oleh Dokter/Perawat. Silakan segera menuju ke ruang periksa UKESMAS.
        </p>

        <div class="d-flex justify-content-center mb-3">
          <button type="button" class="btn qcall-sound-btn" id="qcallPlaySoundBtn">
            <i class="bi bi-volume-up-fill me-1 text-teal"></i>Putar Suara
          </button>
        </div>

        <div id="qcallError" class="alert alert-danger py-2 px-3 small d-none mb-3" role="alert"></div>

        <button type="button" class="btn btn-teal w-100 py-2 fw-bold" id="qcallAckBtn" style="border-radius:12px;font-size:.96rem">
          <i class="bi bi-check-circle-fill me-2"></i>Konfirmasi &amp; Menuju Ruang Periksa
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  var POLL_INTERVAL_MS   = 4000;  // Cek setiap 4 detik
  var VOICE_REPEAT_MS    = 10000; // Ulangi panggilan suara setiap 10 detik
  var modalEl            = document.getElementById('queueCallModal');
  var numEl              = document.getElementById('qcallNumber');
  var catEl              = document.getElementById('qcallCategory');
  var ackBtn             = document.getElementById('qcallAckBtn');
  var playSoundBtn       = document.getElementById('qcallPlaySoundBtn');
  var errEl              = document.getElementById('qcallError');

  if (!modalEl || !numEl || !ackBtn) return;

  var bsModal            = null;
  var activeQueueId      = null;
  var activeQueueNumber  = '';
  var isModalOpen        = false;
  var isAcknowledging    = false;
  var voiceTimer         = null;
  var pollTimer          = null;
  var selectedIdVoice    = null;

  function getBase() {
    return (window.APP_BASE || '').replace(/\/$/, '');
  }

  function getCsrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? (m.getAttribute('content') || '') : '';
  }

  /** Format nomor antrean agar dibaca natural oleh TTS Indonesia (A-001 -> "A, 0, 0, 1") */
  function formatQueueForSpeech(queueNumber) {
    var clean = String(queueNumber || '').trim().toUpperCase();
    if (!clean) return '';
    var parts = clean.split(/[-_\s]+/).filter(Boolean);
    if (parts.length >= 2) {
      var prefix = parts[0].split('').join(', ');
      var digits = parts.slice(1).join('').split('').join(', ');
      return prefix + ', ' + digits;
    }
    return clean.split('').join(', ');
  }

  /** Cari suara Bahasa Indonesia jika tersedia di browser */
  function pickIndonesianVoice() {
    if (!('speechSynthesis' in window)) return null;
    var voices = window.speechSynthesis.getVoices() || [];
    if (!voices.length) return null;
    for (var i = 0; i < voices.length; i++) {
      var lang = (voices[i].lang || '').toLowerCase();
      if (lang === 'id-id' || lang === 'id_id') return voices[i];
    }
    for (var j = 0; j < voices.length; j++) {
      var l = (voices[j].lang || '').toLowerCase();
      if (l.indexOf('id') === 0 || l.indexOf('in') === 0) return voices[j];
    }
    return null;
  }

  if ('speechSynthesis' in window) {
    selectedIdVoice = pickIndonesianVoice();
    window.speechSynthesis.onvoiceschanged = function () {
      selectedIdVoice = pickIndonesianVoice();
    };
  }

  /** Putar chime klinis singkat (2 nada lembut) dengan Web Audio API */
  function playChime() {
    try {
      var AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      var ctx = new AudioCtx();
      if (ctx.state === 'suspended') {
        ctx.resume().catch(function () {});
      }
      var now = ctx.currentTime;
      var notes = [
        { freq: 783.99, start: 0.00, dur: 0.22 }, // G5
        { freq: 1046.50, start: 0.22, dur: 0.35 } // C6
      ];
      notes.forEach(function (n) {
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(n.freq, now + n.start);
        gain.gain.setValueAtTime(0.0001, now + n.start);
        gain.gain.exponentialRampToValueAtTime(0.18, now + n.start + 0.03);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + n.start + n.dur);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now + n.start);
        osc.stop(now + n.start + n.dur + 0.02);
      });
      setTimeout(function () {
        if (ctx && typeof ctx.close === 'function') ctx.close().catch(function () {});
      }, 900);
    } catch (e) {
      // Abaikan jika browser memblokir autoplay audio sebelum ada interaksi pengguna
    }
  }

  /** Ucapkan pengumuman nomor antrean dalam Bahasa Indonesia */
  function speakAnnouncement(queueNumber, withChime) {
    if (withChime) playChime();
    if (!('speechSynthesis' in window)) return;
    try {
      window.speechSynthesis.cancel();
      var spokenNum = formatQueueForSpeech(queueNumber);
      var text = 'Nomor antrian ' + spokenNum + ', silahkan melakukan pemeriksaan';
      var utter = new SpeechSynthesisUtterance(text);
      utter.lang = 'id-ID';
      utter.rate = 0.95;
      utter.pitch = 1.0;
      utter.volume = 1.0;
      var v = selectedIdVoice || pickIndonesianVoice();
      if (v) utter.voice = v;

      // Beri jeda singkat setelah chime agar suara tidak bertabrakan
      var delay = withChime ? 520 : 60;
      setTimeout(function () {
        if (!isModalOpen) return;
        try {
          window.speechSynthesis.speak(utter);
        } catch (err) {}
      }, delay);
    } catch (e) {}
  }

  function stopVoiceLoop() {
    if (voiceTimer) {
      clearInterval(voiceTimer);
      voiceTimer = null;
    }
    if ('speechSynthesis' in window) {
      try { window.speechSynthesis.cancel(); } catch (e) {}
    }
  }

  function startVoiceLoop(queueNumber) {
    stopVoiceLoop();
    speakAnnouncement(queueNumber, true);
    voiceTimer = setInterval(function () {
      if (!isModalOpen || isAcknowledging) {
        stopVoiceLoop();
        return;
      }
      speakAnnouncement(queueNumber, true);
    }, VOICE_REPEAT_MS);
  }

  function showCallModal(data) {
    var qid = Number(data.queue_id || 0);
    if (!qid) return;

    // Jika modal sudah terbuka untuk tiket yang sama, tidak perlu re-trigger
    if (isModalOpen && activeQueueId === qid) return;

    activeQueueId = qid;
    activeQueueNumber = String(data.queue_number || '');
    numEl.textContent = activeQueueNumber;
    if (catEl) {
      catEl.textContent = String(data.complaint_category || 'Pemeriksaan Umum');
    }
    if (errEl) {
      errEl.classList.add('d-none');
      errEl.textContent = '';
    }

    if (!bsModal && window.bootstrap && window.bootstrap.Modal) {
      bsModal = new window.bootstrap.Modal(modalEl, {
        backdrop: 'static',
        keyboard: false
      });
    }

    isModalOpen = true;
    if (bsModal) bsModal.show();
    startVoiceLoop(activeQueueNumber);
  }

  function checkCall() {
    if (isAcknowledging) return;
    fetch(getBase() + '/antrean/check_call', {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      credentials: 'same-origin',
      cache: 'no-store'
    })
      .then(function (r) {
        if (r.status === 401) {
          stopPolling();
          stopVoiceLoop();
          window.location.href = getBase() + '/login/?kicked=1';
          return null;
        }
        if (!r.ok) return null;
        return r.json();
      })
      .then(function (res) {
        if (!res) return;
        if (res.has_call && res.queue_id) {
          showCallModal(res);
        } else if (isModalOpen && !isAcknowledging) {
          // Jika status di server sudah berubah (mis. sudah diperiksa/selesai oleh dokter)
          stopVoiceLoop();
          isModalOpen = false;
          activeQueueId = null;
          if (bsModal) bsModal.hide();
        }
      })
      .catch(function () {
        // Gagal jaringan sementara: biarkan polling berikutnya mencoba lagi
      });
  }

  if (playSoundBtn) {
    playSoundBtn.addEventListener('click', function () {
      if (activeQueueNumber) {
        speakAnnouncement(activeQueueNumber, true);
      }
    });
  }

  ackBtn.addEventListener('click', function () {
    if (!activeQueueId || isAcknowledging) return;
    isAcknowledging = true;
    stopVoiceLoop();

    if (errEl) {
      errEl.classList.add('d-none');
      errEl.textContent = '';
    }
    var origHtml = ackBtn.innerHTML;
    ackBtn.disabled = true;
    ackBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Mengonfirmasi...';

    var fd = new FormData();
    fd.append('csrf_token', getCsrf());
    fd.append('queue_id', String(activeQueueId));

    fetch(getBase() + '/antrean/acknowledge_call', {
      method: 'POST',
      body: fd,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json().catch(function () { return { success: false }; }); })
      .then(function (res) {
        if (res && res.success) {
          isModalOpen = false;
          activeQueueId = null;
          activeQueueNumber = '';
          if (bsModal) bsModal.hide();
        } else {
          if (errEl) {
            errEl.textContent = (res && res.message) ? res.message : 'Gagal mengonfirmasi panggilan. Silakan coba lagi.';
            errEl.classList.remove('d-none');
          }
        }
      })
      .catch(function () {
        if (errEl) {
          errEl.textContent = 'Koneksi bermasalah saat mengonfirmasi. Silakan klik kembali.';
          errEl.classList.remove('d-none');
        }
      })
      .finally(function () {
        isAcknowledging = false;
        ackBtn.disabled = false;
        ackBtn.innerHTML = origHtml;
      });
  });

  // Pastikan suara berhenti jika pengguna meninggalkan halaman
  window.addEventListener('beforeunload', stopVoiceLoop);

  // Jalankan pengecekan segera setelah DOM & Bootstrap siap, lalu ulangi tiap 4 detik
  function initPolling() {
    checkCall();
    pollTimer = setInterval(checkCall, POLL_INTERVAL_MS);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPolling);
  } else {
    initPolling();
  }
})();
</script>
