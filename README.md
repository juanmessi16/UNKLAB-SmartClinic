# UNKLAB SmartClinic (AI-Powered UKESMAS Management System)

Sistem Informasi Layanan Kesehatan Kampus & Unit Kesehatan Mahasiswa (**UKESMAS**) **Universitas Klabat (UNKLAB)** berbasis **Artificial Intelligence (Google Gemini AI)** yang dibangun di atas arsitektur **PHP Native MVC** dengan standar keamanan **Web Application Firewall (WAF)**, **Single-Device Session Lock**, dan **Google OAuth 2.0**.

---

## 🌐 Live Demo & Akses Web (Online 24 Jam)

Aplikasi **UNKLAB SmartClinic** telah aktif secara online dan dapat langsung diakses serta diuji coba melalui peramban:

👉 **[https://unklab-smartclinic.freedev.app/](https://unklab-smartclinic.freedev.app/)**

### 🔑 Akses Cepat Akun Demo (Online & Lokal)
Pada halaman login telah disediakan panel **"Akses Akun Demo (Klik untuk isi cepat)"** untuk mengisi kredensial secara otomatis bagi kelima peran:

| Peran (Role) | Nama Akun Demo | Username | Password | Default Redirect & Hak Akses Utama |
| :--- | :--- | :--- | :--- | :--- |
| **Super Administrator** | `Super Administrator` | `superadmin` | `admin123` | `/dashboard/` — Akses kendali penuh sistem, manajemen user, konfigurasi Gemini AI, dan monitor WAF. |
| **Clinic Administrator** | `Kepala UKESMAS` | `clinicadmin` | `admin123` | `/dashboard/` — Analitik & *forecasting* stok obat, laporan kunjungan klinik, dan jadwal dokter jaga. |
| **Medical Staff** | `Dokter / Perawat` | `medstaff` | `medis123` | `/dashboard/` — Pemanggilan antrean live (FIFO), pencatatan EMR, resep digital, dan penerbitan surat sakit. |
| **Lecturer** | `Dosen` | `dosen` | `dosen123` | `/verifikasi/` — Welcome banner khusus dosen, scan QR/Barcode kamera verifikasi surat sakit mahasiswa (`SKS-XXXX-XXXX`), dan layanan periksa. |
| **Student** | `Juan Messi Septhian Tulengkey` | `mahasiswa` | `mahasiswa123` | `/dashboard/` — Konsultasi keluhan mandiri berbasis AI 24 jam, ambil antrean online, riwayat EMR, dan unduh/cetak surat sakit. |

> **Catatan Mahasiswa:** Mahasiswa juga dapat melakukan login menggunakan akun resmi Google UNKLAB (`@student.unklab.ac.id` / `@unklab.ac.id`) melalui tombol **Login dengan Akun UNKLAB** (Google OAuth 2.0).

---

## 🚀 Cara Menjalankan Program (Local XAMPP)

### 1. Pastikan Apache & MySQL di XAMPP Aktif
Aplikasi ini berjalan di atas web server XAMPP:
- Buka aplikasi **XAMPP Control Panel** di Windows.
- Pastikan modul berikut berstatus **Running** (berwarna hijau):
  - **Apache** (Port 80 / 443)
  - **MySQL** (Port 3306)

### 2. Import Database
- Buka browser dan akses **phpMyAdmin**: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
- Buat database baru bernama: `db_project`
- *Import* file SQL yang telah disediakan: `db_project.sql` (atau `database/db_project.sql`).

### 3. Buka Aplikasi di Browser
Buka browser (Google Chrome, Edge, dll.), lalu akses tautan utama:

👉 **[http://localhost/template-final-project-ai/](http://localhost/template-final-project-ai/)**

---

## 🧭 Alur Akses Aplikasi

1. **Welcome Landing Cover (`/`)**  
   Pengguna disambut oleh halaman cover layar penuh (*Clean Clinical UI*) dengan logo resmi Universitas Klabat, ringkasan keunggulan sistem, dan tombol utama **"Masuk ke SmartClinic"**.
2. **Halaman Login Terpusat (`/login/`)**  
   Desain *single centered card* modern dengan:
   - Login manual (Username & Password).
   - Tombol **Login dengan Akun UNKLAB** (Google OAuth 2.0 untuk domain `@student.unklab.ac.id` / `@unklab.ac.id`).
   - Accordion **"Akses Akun Demo (Klik untuk isi cepat)"** untuk pengujian instan seluruh peran.
3. **Role-Based Redirect & Dedicated Layout**  
   Setelah login, pengguna langsung diarahkan ke modul spesifik peran masing-masing dengan antarmuka yang disesuaikan.

---

## 👥 Akun Demo 5 Peran (RBAC)

Halaman Login (`/login/`) menyediakan tombol *quick-fill* instan untuk kelima peran berikut:

| Peran (Role) | Nama Akun Demo | Username | Password | Default Redirect & Hak Akses Utama |
| :--- | :--- | :--- | :--- | :--- |
| **Super Administrator** | `Super Administrator` | `superadmin` | `admin123` | `/dashboard/` — Akses kendali penuh sistem, manajemen user, konfigurasi Gemini AI, dan monitor WAF. |
| **Clinic Administrator** | `Kepala UKESMAS` | `clinicadmin` | `admin123` | `/dashboard/` — Analitik & *forecasting* stok obat, laporan kunjungan klinik, dan jadwal dokter jaga. |
| **Medical Staff** | `Dokter / Perawat` | `medstaff` | `medis123` | `/dashboard/` — Pemanggilan antrean live (FIFO), pencatatan EMR, resep digital, dan penerbitan surat sakit. |
| **Lecturer** | `Dosen` | `dosen` | `dosen123` | `/verifikasi/` — Welcome banner khusus dosen, verifikasi surat sakit mahasiswa (`SKS-XXXX-XXXX`), dan layanan periksa. |
| **Student** | `Juan Messi Septhian Tulengkey` | `mahasiswa` | `mahasiswa123` | `/dashboard/` (Tampilan Khusus Mahasiswa) — Konsultasi keluhan mandiri berbasis AI, ambil antrean online, riwayat EMR, dan unduh/cetak surat sakit. |

---

## 🏥 Fitur & Modul Utama

1. **Welcome Landing Cover & Portal Akademik**  
   Halaman beranda modern dan ramah pengguna dengan informasi layanan UKESMAS, integrasi AI, dan gerbang masuk satu pintu.
2. **Dashboard Role-Based & Clean Clinical UI**  
   Tata letak profesional dengan palet warna *Medical Teal* (`#0F766E`), kartu sambutan dinamis, indikator status antrean live, metrik operasional, dan grafik interaktif Chart.js.
3. **AI Symptom Triage & Pre-Screening (`GeminiService::triage`)**  
   Analisis keluhan pasien menggunakan bahasa alami untuk menentukan klasifikasi urgensi (Hijau / Kuning / Merah), panduan pertolongan pertama (P3K), deteksi *red flags*, dan anamnesis awal untuk dokter.
4. **Antrean Online & Panggilan Pasien Real-Time**  
   Sistem antrean online berurutan (FIFO) dengan prioritas triase darurat, estimasi waktu tunggu, dan notifikasi panggilan antrean visual/audio.
5. **Electronic Medical Records (EMR) & Digital Prescription**  
   Pencatatan rekam medis elektronik terstandarisasi: tanda-tanda vital, diagnosis ICD-10, riwayat alergi, tindakan medis, serta resep obat yang langsung memotong stok apotek secara otomatis.
6. **Digital Sick Leave (Surat Keterangan Sakit Digital & Verifikasi QR)**  
   Penerbitan surat istirahat medis resmi dengan kuota otomatis (maks. 10 hari/semester), dilengkapi kode verifikasi unik `SKS-XXXX-XXXX` dan QR code untuk validasi keaslian dokumen secara publik atau oleh dosen.
7. **Portal Verifikasi Dosen dengan Welcome Banner**  
   Halaman verifikasi khusus dosen yang dilengkapi kartu sambutan, status mesin AI, pemantauan antrean live hari ini, serta akses cepat ke layanan konsultasi kesehatan dosen.
8. **Medicine & Pharmacy Inventory Management**  
   Manajemen stok obat dan alat kesehatan klinik, pencatatan mutasi otomatis (`medicine_stock_logs`), penyesuaian stok opname, dan peringatan dini stok kritis/kedaluwarsa.
9. **AI Health Analytics & Stock Forecasting (`GeminiService::forecast`)**  
   Analisis prediktif tren penyakit kampus berdasarkan data historis kunjungan (misal lonjakan ISPA, dispepsia saat ujian semester) dan rekomendasi cerdas restock obat apotek.
10. **Konsultasi AI 24 Jam (Floating Widget Chatbot)**  
    Widget asisten virtual kesehatan interaktif 24/7 di pojok kanan bawah yang dapat diakses di setiap halaman untuk menjawab keluhan awal, prosedur klinik, dan informasi layanan kesehatan UKESMAS.
11. **Dokumentasi & Analisis Sistem (`/dokumentasi/`)**  
    Modul komprehensif untuk pengujian dan presentasi akademik: *Root Cause Analysis* (RCA), Diagram *Fishbone* (Ishikawa), Arsitektur MVC + AI, alur algoritma, dan referensi sistem.

---

## 🛡️ Keamanan & Arsitektur Sistem

- **Arsitektur MVC Native PHP**: Struktur bersih tanpa framework bloated (*Lightweight, Fast, Scalable*).
- **Web Application Firewall (WAF)**: Proteksi otomatis terhadap serangan SQL Injection (SQLi), Cross-Site Scripting (XSS), Path Traversal, dan Session Hijacking.
- **Single-Device Session Lock**: Mencegah akun digunakan secara bersamaan di lebih dari satu perangkat/browser; sesi lama otomatis dialihkan (`?kicked=1`) jika ada login baru.
- **Single-Tab Lock**: Mencegah benturan sesi antar-tab di browser yang sama.
- **Google OAuth 2.0**: Autentikasi aman akun resmi Google Universitas Klabat untuk mahasiswa.
- **Intelligent Contextual Fallback**: Jika Google Gemini API offline, belum diisi, atau terkena limit kuota, sistem secara mulus beralih ke mesin aturan klinis lokal berbasis konteks tanpa terjadi error pada aplikasi.

---

## ⚙️ Konfigurasi Sistem

Seluruh konfigurasi utama tersimpan di file `app/config/config.php`:

### 1. Google Gemini AI Key
Dapat diatur melalui salah satu cara berikut:
- **Melalui Dashboard**: Login sebagai `superadmin` &rarr; buka menu **Pengaturan** &rarr; simpan **Gemini API Key**.
- **Melalui File Config**: Isi konstanta `GEMINI_API_KEY` di `app/config/config.php`.
- **Environment Variable**: Set variable sistem `GEMINI_API_KEY`.

### 2. Google OAuth 2.0 (Opsional)
Untuk mengaktifkan fitur *Login dengan Akun UNKLAB*:
- Dapatkan Client ID & Secret di [Google Cloud Console](https://console.cloud.google.com/).
- Atur Authorized Redirect URI: `http://localhost/template-final-project-ai/login/google`
- Isi `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` di `app/config/config.php`.

---

## 🌐 Opsi Deployment Publik

Untuk mendistribusikan atau mempresentasikan proyek ini secara online gratis:
1. **Cloudflare Tunnel (Instan untuk Demo Tugas Akhir):**  
   Jalankan `cloudflared tunnel --url http://localhost` untuk mengekspos XAMPP lokal ke URL publik HTTPS resmi secara instan tanpa biaya.
2. **InfinityFree ([infinityfree.com](https://www.infinityfree.com/)):**  
   Layanan web hosting gratis dengan dukungan penuh PHP Native, MySQL (phpMyAdmin), `.htaccess`, dan sertifikat SSL gratis.
