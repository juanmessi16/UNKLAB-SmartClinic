/* UNKLAB SmartClinic - front-end behaviours */
(function () {
  'use strict';
  var BASE = (window.APP_BASE || '').replace(/\/$/, '');
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  window.SC = {
    base: BASE,
    csrf: csrf,
    post: function (path, data) {
      var fd = new FormData();
      fd.append('csrf_token', csrf);
      Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
      return fetch(BASE + '/' + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) {
          if (r.status === 401) {
            return r.json().catch(function () { return { session_expired: true }; }).then(function (d) {
              if (d && d.session_expired) {
                window.location.href = BASE + '/login/?kicked=1';
              }
              return d;
            });
          }
          return r.json().catch(function () { return { success: false, message: 'Respons server tidak valid (HTTP ' + r.status + ').' }; });
        });
    },
    get: function (path) {
      return fetch(BASE + '/' + path, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) {
          if (r.status === 401) {
            return r.json().catch(function () { return { session_expired: true }; }).then(function (d) {
              if (d && d.session_expired) {
                window.location.href = BASE + '/login/?kicked=1';
              }
              return d;
            });
          }
          return r.json().catch(function () { return { success: false, message: 'Respons tidak valid.' }; });
        });
    },
    esc: function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
  };

  // ---------- Sidebar (mobile) ----------
  var sb = document.getElementById('sidebar');
  var bd = document.getElementById('sidebarBackdrop');
  function toggleSidebar(open) {
    if (!sb) return;
    var o = typeof open === 'boolean' ? open : !sb.classList.contains('open');
    sb.classList.toggle('open', o);
    if (bd) bd.classList.toggle('show', o);
  }
  document.querySelectorAll('[data-toggle-sidebar]').forEach(function (b) { b.addEventListener('click', function () { toggleSidebar(); }); });
  if (bd) bd.addEventListener('click', function () { toggleSidebar(false); });

  // Ctrl+K fokus pencarian global
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      var i = document.getElementById('globalSearch');
      if (i) { e.preventDefault(); i.focus(); }
    }
  });

  // ---------- Chat widget ("Konsultasi AI 24 Jam" - Multi-turn LLM) ----------
  var fab = document.getElementById('fabAi');
  var panel = document.getElementById('chatPanel');
  if (fab && panel) {
    var body = document.getElementById('chatBody');
    var input = document.getElementById('chatInput');
    var form = document.getElementById('chatForm');
    var sugg = document.getElementById('chatSuggest');
    var greeted = false;
    var chatHistory = [];
    var defaultSuggestions = [
      'Cara meredakan demam dan pusing?',
      'Pertolongan pertama saat maag kambuh',
      'Tips menjaga daya tahan tubuh saat begadang'
    ];

    function addMsg(text, who, src) {
      var d = document.createElement('div');
      d.className = 'msg ' + who;
      d.textContent = text;
      if (src) { var s = document.createElement('span'); s.className = 'src'; s.textContent = src; d.appendChild(s); }
      body.appendChild(d);
      body.scrollTop = body.scrollHeight;
      return d;
    }
    function setSuggest(list) {
      sugg.innerHTML = '';
      (list || []).slice(0, 3).forEach(function (t) {
        var b = document.createElement('button'); b.type = 'button'; b.textContent = t;
        b.addEventListener('click', function () { send(t); });
        sugg.appendChild(b);
      });
    }
    function send(text) {
      text = (text || '').trim();
      if (!text) return;
      addMsg(text, 'user');
      input.value = '';
      setSuggest([]);
      var recentHistory = chatHistory.slice(-6);
      var t = document.createElement('div');
      t.className = 'msg bot typing'; t.innerHTML = '<span></span><span></span><span></span>';
      body.appendChild(t); body.scrollTop = body.scrollHeight;
      SC.post('ai/chat', { message: text, history: JSON.stringify(recentHistory) }).then(function (r) {
        t.remove();
        if (r && r.success && r.reply) {
          addMsg(r.reply, 'bot', 'Konsultasi AI 24 Jam');
          if (r.source === 'gemini') {
            chatHistory.push({ role: 'user', text: text });
            chatHistory.push({ role: 'assistant', text: r.reply });
            if (chatHistory.length > 10) chatHistory = chatHistory.slice(-10);
          }
          setSuggest(r.suggestions && r.suggestions.length ? r.suggestions : defaultSuggestions);
        } else {
          addMsg((r && r.message) || 'Maaf, layanan Konsultasi AI 24 Jam sedang mengalami kendala. Silakan coba beberapa saat lagi.', 'bot', 'Konsultasi AI 24 Jam');
          setSuggest(defaultSuggestions);
        }
      }).catch(function () {
        t.remove();
        addMsg('Koneksi bermasalah saat menghubungi server AI. Silakan periksa internet Anda dan coba lagi.', 'bot', 'Konsultasi AI 24 Jam');
        setSuggest(defaultSuggestions);
      });
    }
    function toggle(open) {
      var o = typeof open === 'boolean' ? open : !panel.classList.contains('open');
      panel.classList.toggle('open', o);
      if (o) {
        if (!greeted) {
          greeted = true;
          addMsg('Halo! Saya Konsultasi AI 24 Jam. Ceritakan keluhan, gejala, atau pertanyaan seputar kesehatan Anda—saya siap membantu kapan saja.', 'bot', 'Konsultasi AI 24 Jam');
          setSuggest(defaultSuggestions);
        }
        setTimeout(function () { input.focus(); }, 150);
      }
    }
    fab.addEventListener('click', function () { toggle(); });
    document.querySelectorAll('[data-open-chat]').forEach(function (b) { b.addEventListener('click', function (e) { e.preventDefault(); toggle(true); }); });
    var cl = document.getElementById('chatClose'); if (cl) cl.addEventListener('click', function () { toggle(false); });
    form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value); });
  }

  // ---------- Konfirmasi aksi (data-confirm) ----------
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.dataset && f.dataset.confirm && !window.confirm(f.dataset.confirm)) e.preventDefault();
  });

  // ---------- Hapus alert otomatis ----------
  setTimeout(function () {
    document.querySelectorAll('.alert-auto').forEach(function (a) {
      a.style.transition = 'opacity .5s'; a.style.opacity = '0';
      setTimeout(function () { a.remove(); }, 600);
    });
  }, 6000);

  // ---------- Render hasil pencarian semantik (dipakai landing & halaman pencarian) ----------
  window.SC.renderSearch = function (r, el) {
    if (!r || !r.success) { el.innerHTML = '<div class="empty-state"><i class="bi bi-exclamation-circle"></i>' + SC.esc((r && r.message) || 'Pencarian gagal.') + '</div>'; return; }
    var h = '<div class="d-flex align-items-start justify-content-between gap-2 mb-3 flex-wrap"><div class="pe-2"><div class="fw-bold text-navy mb-1"><i class="bi bi-clipboard2-pulse text-teal me-1"></i>' + SC.esc(r.answer || 'Hasil pencarian') + '</div></div>'
      + '<span class="ai-badge ' + (r.source === 'gemini' ? '' : 'fallback') + '"><i class="bi bi-' + (r.source === 'gemini' ? 'heart-pulse' : 'journal-medical') + '"></i>' + (r.source === 'gemini' ? 'Analisis Medis' : 'Katalog Klinik') + '</span></div>';
    if (!r.results || !r.results.length) {
      h += '<div class="empty-state"><i class="bi bi-search"></i>Tidak ada hasil yang relevan. Coba gunakan kalimat lain.</div>';
    } else {
      h += '<div class="d-grid gap-2">';
      r.results.forEach(function (x) {
        var tag = x.type === 'obat' ? '<span class="badge badge-soft-primary"><i class="bi bi-capsule me-1"></i>Obat</span>' : '<span class="badge badge-soft-info"><i class="bi bi-journal-medical me-1"></i>Panduan Medis</span>';
        h += '<a class="result-item" href="' + SC.esc(x.url) + '"><div class="d-flex justify-content-between align-items-start gap-2"><div class="fw-bold text-navy">' + SC.esc(x.title) + '</div>' + tag + '</div>'
          + '<div class="small text-muted-2 mt-1">' + SC.esc(x.snippet) + '…</div>'
          + '<div class="d-flex align-items-center gap-2 mt-2"><div class="score-bar flex-grow-1"><span style="width:' + Math.min(100, x.score) + '%"></span></div><span class="small fw-bold text-navy">' + x.score + '%</span></div>'
          + '<div class="small text-muted-2 mt-1"><i class="bi bi-info-circle text-teal"></i> ' + SC.esc(x.reason) + '</div></a>';
      });
      h += '</div>';
    }
    el.innerHTML = h;
  };
})();
