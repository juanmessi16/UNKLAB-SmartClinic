<!-- Floating "Konsultasi AI 24 Jam" -->
<button class="fab-ai no-print" id="fabAi" type="button" aria-label="Konsultasi AI 24 Jam"><i class="bi bi-heart-pulse-fill"></i><span>Konsultasi AI 24 Jam</span></button>
<section class="chat-panel no-print" id="chatPanel" aria-label="Konsultasi AI 24 Jam">
  <div class="chat-head">
    <div class="avatar" style="background:rgba(255,255,255,.16);color:#fff;border:1px solid rgba(255,255,255,.25)"><i class="bi bi-heart-pulse-fill"></i></div>
    <div class="flex-grow-1">
      <div class="t">Konsultasi AI 24 Jam</div>
      <div class="s"><span class="live-dot" style="background:#34d399"></span>Layanan Konsultasi Kesehatan &bull; Online 24/7</div>
    </div>
    <button class="btn-icon" id="chatClose" type="button" style="background:rgba(255,255,255,.14);border-color:transparent;color:#fff" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
  </div>
  <div class="chat-body" id="chatBody"></div>
  <div class="chat-suggest" id="chatSuggest"></div>
  <form class="chat-input" id="chatForm" autocomplete="off">
    <input type="text" id="chatInput" placeholder="Tanyakan keluhan, gejala, atau tips kesehatan..." maxlength="600">
    <button class="btn btn-teal" type="submit" aria-label="Kirim"><i class="bi bi-send-fill"></i></button>
  </form>
</section>
