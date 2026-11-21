<?php
require __DIR__ . '/../template/header.php';
?>

<div class="container-fluid py-2" style="max-width:1240px">
  <div class="welcome-banner mb-4">
    <span class="badge badge-soft-primary rounded-pill mb-2">
      <i class="bi bi-mortarboard-fill me-1"></i>Modul Dokumentasi &amp; Analisis Sistem (Presentasi Akademik)
    </span>
    <h2>UNKLAB SmartClinic: Sistem Manajemen Klinik Kampus UKESMAS</h2>
    <p class="mb-3">Dokumentasi rekayasa perangkat lunak komprehensif yang mencakup Root Cause Analysis (RCA), Fishbone (Ishikawa) Diagram, Arsitektur MVC + Google Gemini AI, Algoritma Sistem, Alur Kerja (Workflow), dan Referensi Ilmiah.</p>
    <div class="d-flex flex-wrap gap-2">
      <a href="#rca" class="btn btn-teal btn-sm">1. Root Cause Analysis</a>
      <a href="#fishbone" class="btn btn-outline-clinical btn-sm">2. Fishbone Diagram</a>
      <a href="#arsitektur" class="btn btn-outline-clinical btn-sm">3. Arsitektur MVC + AI</a>
      <a href="#algoritma" class="btn btn-outline-clinical btn-sm">4. Algoritma &amp; Formula</a>
      <a href="#workflow" class="btn btn-outline-clinical btn-sm">5. Proses Kerja Sistem</a>
      <a href="#referensi" class="btn btn-outline-clinical btn-sm">6. Referensi &amp; Pihak Ketiga</a>
    </div>
  </div>

  <!-- 1. ROOT CAUSE ANALYSIS (RCA) -->
  <section class="card-x mb-4 doc-section" id="rca">
    <div class="card-head">
      <div>
        <h5 class="section-title"><i class="bi bi-search-heart text-gold me-2"></i>1. Root Cause Analysis (RCA) — Metode 5 Whys</h5>
        <div class="section-sub">Identifikasi akar masalah operasional pelayanan kesehatan mahasiswa di UKESMAS Universitas Klabat</div>
      </div>
      <span class="badge badge-soft-danger">Problem Analysis</span>
    </div>
    <div class="card-body-x">
      <div class="row g-3">
        <div class="col-lg-6">
          <div class="p-3 rounded-4 h-100" style="background:var(--slate-50);border:1px solid var(--slate-200)">
            <div class="fw-bold text-navy mb-2"><i class="bi bi-1-circle-fill text-danger me-1"></i>Masalah 1: Antrean Manual &amp; Lambatnya Anamnesis Awal</div>
            <ul class="small mb-2">
              <li><strong>Why 1:</strong> Mengapa pasien sering menunggu lama di ruang tunggu? &rarr; Karena pendaftaran antrean masih dilakukan manual di buku tulis saat tiba di klinik.</li>
              <li><strong>Why 2:</strong> Mengapa belum ada panduan penanganan awal sebelum ke klinik? &rarr; Karena belum ada sistem konsultasi keluhan awal berbasis AI.</li>
              <li><strong>Why 3:</strong> Mengapa dokter memerlukan waktu lama saat anamnesis awal? &rarr; Karena keluhan belum dirangkum secara terstruktur sebelum masuk ruang periksa.</li>
            </ul>
            <div class="p-2 rounded-3 small" style="background:#ecfdf5;color:#047857">
              <strong>Solusi SmartClinic:</strong> <em>Gemini AI Assistant</em> memperkirakan kategori keluhan, memberi saran penanganan awal, menyusun ringkasan untuk dokter, dan terhubung langsung ke sistem antrean online berurutan (FIFO).
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="p-3 rounded-4 h-100" style="background:var(--slate-50);border:1px solid var(--slate-200)">
            <div class="fw-bold text-navy mb-2"><i class="bi bi-2-circle-fill text-danger me-1"></i>Masalah 2: Kekosongan Stok Obat (Stockout) Saat Musim Ujian</div>
            <ul class="small mb-2">
              <li><strong>Why 1:</strong> Mengapa obat ISPA dan maag/antasida sering habis mendadak saat pekan ujian? &rarr; Karena pengadaan obat masih bersifat reaktif (menunggu stok habis).</li>
              <li><strong>Why 2:</strong> Mengapa pemotongan stok tidak sinkron dengan pemakaian? &rarr; Karena resep ditulis di kertas dan tidak langsung memotong saldo inventaris apotek.</li>
              <li><strong>Why 3:</strong> Mengapa pola lonjakan penyakit kampus tidak terdeteksi dini? &rarr; Karena data rekam medis belum diolah dengan analitik prediktif.</li>
            </ul>
            <div class="p-2 rounded-3 small" style="background:#ecfdf5;color:#047857">
              <strong>Solusi SmartClinic:</strong> Integrasi resep EMR yang memotong stok secara atomik (transaksi SQL) + <em>AI Stock &amp; Disease Forecaster</em> berbasis agregasi 14 hari.
            </div>
          </div>
        </div>
        <div class="col-12">
          <div class="p-3 rounded-4" style="background:var(--slate-50);border:1px solid var(--slate-200)">
            <div class="fw-bold text-navy mb-2"><i class="bi bi-3-circle-fill text-danger me-1"></i>Masalah 3: Sulitnya Validasi Surat Keterangan Sakit oleh Dosen</div>
            <div class="small mb-2">Surat sakit kertas rawan hilang, rusak, atau dipalsukan, dan dosen pengampu kesulitan memverifikasi keabsahannya secara real-time tanpa membuka diagnosis rahasia pasien.</div>
            <div class="p-2 rounded-3 small" style="background:#ecfdf5;color:#047857">
              <strong>Solusi SmartClinic:</strong> <em>Digital Sick Leave</em> dengan kode kriptografi unik (<code>SKS-XXXX-XXXX</code>) dan QR Code yang dapat diverifikasi dosen secara instan dengan tetap menjaga privasi diagnosis medis.
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 2. FISHBONE DIAGRAM (ISHIKAWA) -->
  <section class="card-x mb-4 doc-section" id="fishbone">
    <div class="card-head">
      <div>
        <h5 class="section-title"><i class="bi bi-diagram-3-fill text-gold me-2"></i>2. Fishbone Diagram (Diagram Tulang Ikan / Ishikawa)</h5>
        <div class="section-sub">Visualisasi faktor penyebab ketidakefisienan layanan klinik kampus konvensional</div>
      </div>
      <span class="badge badge-soft-warning">Ishikawa 6M</span>
    </div>
    <div class="card-body-x">
      <div class="p-3 rounded-4 overflow-auto" style="background:#0f172a">
        <svg viewBox="0 0 980 420" width="100%" height="390" xmlns="http://www.w3.org/2000/svg" style="min-width:760px">
          <!-- Tulang Utama -->
          <line x1="60" y1="210" x2="770" y2="210" stroke="#f59e0b" stroke-width="5"/>
          <!-- Kepala Ikan (Effect) -->
          <polygon points="770,150 950,210 770,270" fill="#1e3a8a" stroke="#f59e0b" stroke-width="3"/>
          <text x="845" y="202" fill="#ffffff" font-size="13" font-weight="bold" text-anchor="middle">LAYANAN UKESMAS</text>
          <text x="845" y="220" fill="#fcd34d" font-size="12" font-weight="bold" text-anchor="middle">BELUM OPTIMAL</text>

          <!-- Cabang Atas 1: Man -->
          <line x1="210" y1="60" x2="290" y2="210" stroke="#60a5fa" stroke-width="3"/>
          <rect x="120" y="25" width="180" height="34" rx="8" fill="#1e3a8a" stroke="#60a5fa"/>
          <text x="210" y="47" fill="#fff" font-size="13" font-weight="bold" text-anchor="middle">MAN (SDM &amp; Pasien)</text>
          <text x="135" y="95" fill="#cbd5e1" font-size="11">• Dokter/perawat jaga terbatas</text>
          <text x="150" y="118" fill="#cbd5e1" font-size="11">• Mahasiswa bingung P3K awal</text>
          <text x="165" y="141" fill="#cbd5e1" font-size="11">• Dosen sulit cek surat sakit</text>

          <!-- Cabang Atas 2: Method -->
          <line x1="450" y1="60" x2="530" y2="210" stroke="#60a5fa" stroke-width="3"/>
          <rect x="360" y="25" width="180" height="34" rx="8" fill="#1e3a8a" stroke="#60a5fa"/>
          <text x="450" y="47" fill="#fff" font-size="13" font-weight="bold" text-anchor="middle">METHOD (Prosedur)</text>
          <text x="375" y="95" fill="#cbd5e1" font-size="11">• Pendaftaran antrean masih manual</text>
          <text x="390" y="118" fill="#cbd5e1" font-size="11">• Anamnesis berulang dari nol</text>
          <text x="405" y="141" fill="#cbd5e1" font-size="11">• Surat izin sakit masih kertas</text>

          <!-- Cabang Atas 3: Material -->
          <line x1="670" y1="60" x2="730" y2="210" stroke="#60a5fa" stroke-width="3"/>
          <rect x="590" y="25" width="175" height="34" rx="8" fill="#1e3a8a" stroke="#60a5fa"/>
          <text x="677" y="47" fill="#fff" font-size="13" font-weight="bold" text-anchor="middle">MATERIAL (Obat)</text>
          <text x="595" y="95" fill="#cbd5e1" font-size="11">• Stok obat kritis telat disadari</text>
          <text x="610" y="118" fill="#cbd5e1" font-size="11">• Risiko obat lewat kedaluwarsa</text>
          <text x="625" y="141" fill="#cbd5e1" font-size="11">• Mutasi resep tidak otomatis</text>

          <!-- Cabang Bawah 1: Machine/System -->
          <line x1="210" y1="360" x2="290" y2="210" stroke="#34d399" stroke-width="3"/>
          <rect x="120" y="360" width="180" height="34" rx="8" fill="#065f46" stroke="#34d399"/>
          <text x="210" y="382" fill="#fff" font-size="13" font-weight="bold" text-anchor="middle">MACHINE (Teknologi)</text>
          <text x="155" y="280" fill="#cbd5e1" font-size="11">• Pencatatan EMR belum terpusat</text>
          <text x="140" y="303" fill="#cbd5e1" font-size="11">• Pencarian obat masih keyword kaku</text>
          <text x="125" y="326" fill="#cbd5e1" font-size="11">• Belum ada asisten konsultasi 24/7</text>

          <!-- Cabang Bawah 2: Measurement -->
          <line x1="450" y1="360" x2="530" y2="210" stroke="#34d399" stroke-width="3"/>
          <rect x="360" y="360" width="185" height="34" rx="8" fill="#065f46" stroke="#34d399"/>
          <text x="452" y="382" fill="#fff" font-size="13" font-weight="bold" text-anchor="middle">MEASUREMENT (Analitik)</text>
          <text x="395" y="280" fill="#cbd5e1" font-size="11">• Tidak ada prediksi wabah kampus</text>
          <text x="380" y="303" fill="#cbd5e1" font-size="11">• Rekapitulasi tren kasus klinik manual</text>
          <text x="365" y="326" fill="#cbd5e1" font-size="11">• Kuota izin sakit sulit dipantau</text>

          <!-- Cabang Bawah 3: Environment -->
          <line x1="670" y1="360" x2="730" y2="210" stroke="#34d399" stroke-width="3"/>
          <rect x="590" y="360" width="175" height="34" rx="8" fill="#065f46" stroke="#34d399"/>
          <text x="677" y="382" fill="#fff" font-size="13" font-weight="bold" text-anchor="middle">ENVIRONMENT (Kampus)</text>
          <text x="615" y="280" fill="#cbd5e1" font-size="11">• Aktivitas kampus &amp; ruang kelas padat</text>
          <text x="600" y="303" fill="#cbd5e1" font-size="11">• Lonjakan maag saat pekan ujian</text>
          <text x="595" y="326" fill="#cbd5e1" font-size="11">• Cuaca Airmadidi memicu ISPA</text>
        </svg>
      </div>
    </div>
  </section>

  <!-- 3. ARSITEKTUR MVC + AI -->
  <section class="card-x mb-4 doc-section" id="arsitektur">
    <div class="card-head">
      <div>
        <h5 class="section-title"><i class="bi bi-layers-fill text-gold me-2"></i>3. Arsitektur Sistem: PHP Native MVC + Security WAF + Google Gemini AI</h5>
        <div class="section-sub">Desain berlapis (Layered Architecture) dengan mekanisme Intelligent Contextual Fallback</div>
      </div>
      <span class="badge badge-soft-primary">System Architecture</span>
    </div>
    <div class="card-body-x">
      <div class="row g-3">
        <div class="col-md-3">
          <div class="arch-layer h-100" style="border-color:#1e3a8a;background:#eff6ff">
            <div class="fw-800 text-navy mb-1">1. Presentation Layer (View)</div>
            <div class="small text-muted-2">
              • Bootstrap 5 + Custom SaaS UI<br>
              • Chart.js Interactive Analytics<br>
              • Floating Chatbot Widget<br>
              • Print-ready Surat Sakit + QR
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="arch-layer h-100" style="border-color:#f59e0b;background:#fffbeb">
            <div class="fw-800 text-navy mb-1">2. Gateway &amp; WAF Layer</div>
            <div class="small text-muted-2">
              • <code>App.php</code> Clean URL Router<br>
              • <code>Security.php</code> Anti-SQLi, Anti-XSS, Anti-Path Traversal &amp; RCE Guard<br>
              • CSRF Token &amp; RBAC 5 Role
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="arch-layer h-100" style="border-color:#10b981;background:#ecfdf5">
            <div class="fw-800 text-navy mb-1">3. Business &amp; AI Layer</div>
            <div class="small text-muted-2">
              • <code>ClinicController</code> &amp; Controllers<br>
              • <code>GeminiService.php</code> (cURL REST ke <code>gemini-2.0-flash</code>)<br>
              • Smart Rule-Engine Fallback
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="arch-layer h-100" style="border-color:#6366f1;background:#eef2ff">
            <div class="fw-800 text-navy mb-1">4. Data Persistence Layer</div>
            <div class="small text-muted-2">
              • <code>Database.php</code> Prepared Statements &amp; ACID Transactions<br>
              • MySQL <code>db_project</code> (17 tabel terintegrasi)
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 4. ALGORITMA & FORMULA -->
  <section class="card-x mb-4 doc-section" id="algoritma">
    <div class="card-head">
      <div>
        <h5 class="section-title"><i class="bi bi-cpu-fill text-gold me-2"></i>4. Penjelasan Algoritma &amp; Formula Inti</h5>
        <div class="section-sub">Logika perhitungan prioritas antrean, forecasting stok obat, dan pencarian semantik</div>
      </div>
    </div>
    <div class="card-body-x">
      <div class="row g-3">
        <div class="col-lg-6">
          <h6 class="fw-bold text-navy">A. Algoritma Antrean Berurutan (FIFO — First-In, First-Out)</h6>
          <p class="small text-muted-2">Setiap pasien yang mendaftar mendapatkan nomor urut berurutan (<code>A-001</code>, <code>A-002</code>, dst.) dan dilayani secara adil berdasarkan urutan kedatangan/pendaftaran:</p>
          <div class="code-block">QueueNumber = 'A-' + LPAD(COUNT(today_queue) + 1, 3, '0')
ORDER BY FIELD(status, 'Dipanggil', 'Diperiksa', 'Menunggu'),
         id ASC</div>
        </div>
        <div class="col-lg-6">
          <h6 class="fw-bold text-navy">B. Algoritma Prediksi Kebutuhan &amp; Restock Obat (7 Hari)</h6>
          <p class="small text-muted-2">Menggabungkan laju konsumsi harian 14 hari ($U_{14}$) dan 7 hari terakhir ($U_7$) dengan faktor pertumbuhan kunjungan mingguan ($g$):</p>
          <div class="code-block">DailyRate = max(U_14 / 14, U_7 / 7) * (1 + clamp(g, 0, 0.5))
Need_7d   = ceil(DailyRate * 7)
Restock   = max(0, ceil((Need_7d + SafetyStock_Min - CurrentStock) / 10) * 10)</div>
        </div>
      </div>
    </div>
  </section>

  <!-- 5. PROSES KERJA SISTEM (WORKFLOW) -->
  <section class="card-x mb-4 doc-section" id="workflow">
    <div class="card-head">
      <div>
        <h5 class="section-title"><i class="bi bi-signpost-split-fill text-gold me-2"></i>5. Proses Kerja Sistem (End-to-End Clinical Workflow)</h5>
        <div class="section-sub">Alur terintegrasi dari keluhan mahasiswa hingga pelaporan analitik pimpinan UKESMAS</div>
      </div>
    </div>
    <div class="card-body-x">
      <div class="row g-3">
        <?php foreach ([
          ['Langkah 1', 'Konsultasi Keluhan ke AI', 'Mahasiswa menuliskan keluhan bahasa alami. Gemini AI Assistant menghasilkan estimasi kategori keluhan, saran penanganan awal, dan ringkasan untuk dokter.'],
          ['Langkah 2', 'Pengambilan Nomor Antrean (FIFO)', 'Nomor antrean diterbitkan berurutan (A-001, A-002, A-003) sesuai urutan pendaftaran yang tersedia.'],
          ['Langkah 3', 'Pemeriksaan & Input EMR', 'Dokter/perawat memanggil pasien, meninjau keluhan pasien & rekomendasi awal AI, lalu mencatat tanda vital dan diagnosis ICD-10.'],
          ['Langkah 4', 'Resep Digital & Potong Stok', 'Saat EMR disimpan, transaksi SQL otomatis memotong stok tabel medicines dan mencatat mutasi di medicine_stock_logs.'],
          ['Langkah 5', 'Surat Sakit Digital ber-QR', 'Jika pasien butuh istirahat, sistem menerbitkan kode SKS unik & QR yang dapat diverifikasi dosen tanpa membuka diagnosis.'],
          ['Langkah 6', 'AI Health & Stock Forecasting', 'Kepala UKESMAS menjalankan analisis agregat 14 hari untuk memprediksi tren penyakit minggu depan & daftar belanja obat.'],
        ] as $w): ?>
          <div class="col-md-4">
            <div class="p-3 rounded-4 h-100" style="background:var(--slate-50);border:1px solid var(--slate-200)">
              <span class="badge badge-soft-warning mb-1"><?= e($w[0]) ?></span>
              <div class="fw-bold text-navy mb-1"><?= e($w[1]) ?></div>
              <div class="small text-muted-2"><?= e($w[2]) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- 6. DAFTAR REFERENSI & LAYANAN PIHAK KETIGA -->
  <section class="card-x doc-section" id="referensi">
    <div class="card-head">
      <div>
        <h5 class="section-title"><i class="bi bi-book-half text-gold me-2"></i>6. Daftar Referensi Akademik &amp; Layanan Pihak Ketiga</h5>
        <div class="section-sub">Pustaka, API eksternal, dan rujukan standar kesehatan yang digunakan dalam sistem</div>
      </div>
    </div>
    <div class="card-body-x">
      <div class="row g-4">
        <div class="col-md-6">
          <h6 class="fw-bold text-navy">Layanan Pihak Ketiga &amp; Teknologi</h6>
          <ul class="small">
            <li><strong>Google Gemini REST API (<code>generativelanguage.googleapis.com</code>):</strong> Model <code>gemini-2.0-flash</code> / <code>gemini-1.5-flash</code> dengan <code>responseMimeType: application/json</code> untuk Triage, Forecasting, Semantic Search, dan Chatbot.</li>
            <li><strong>Bootstrap 5.3 &amp; Bootstrap Icons:</strong> Kerangka kerja komponen UI responsif.</li>
            <li><strong>Chart.js 4.4:</strong> Visualisasi grafik interaktif tren kunjungan &amp; distribusi penyakit.</li>
            <li><strong>QR Server API:</strong> Generator QR Code verifikasi Surat Keterangan Sakit.</li>
          </ul>
        </div>
        <div class="col-md-6">
          <h6 class="fw-bold text-navy">Referensi Standar Medis &amp; Rekayasa Sistem</h6>
          <ul class="small">
            <li>World Health Organization (WHO). <em>International Statistical Classification of Diseases and Related Health Problems (ICD-10)</em>.</li>
            <li>Kementerian Kesehatan RI. <em>Formularium Nasional &amp; Pedoman Pengelolaan Obat Publik di Fasilitas Pelayanan Kesehatan Dasar</em>.</li>
            <li>OWASP Foundation. <em>OWASP Top 10 Web Application Security Risks</em> (Mitigasi SQLi, XSS, CSRF, dan Session Management).</li>
            <li>Ishikawa, K. (1986). <em>Guide to Quality Control</em> (Metode Root Cause &amp; Fishbone Analysis).</li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</div>

<?php require __DIR__ . '/../template/footer.php'; ?>
