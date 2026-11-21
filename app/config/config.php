<?php
/**
 * Konfigurasi Utama - UNKLAB SmartClinic (AI-Powered UKESMAS Management System)
 * Framework MVC Native PHP
 */

// 1. Deteksi Base URL / APP_PATH secara otomatis
if (!defined('APP_PATH')) {
    if (php_sapi_name() === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        $folderName = basename(dirname(__DIR__, 2));
        define("APP_PATH", "http://localhost/" . $folderName);
    } else {
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $protocol = $is_https ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $cleanDir = preg_replace('#/public$#', '', $scriptDir);
        define("APP_PATH", $protocol . $host . $cleanDir);
    }
}

// 2. Direktori Root Proyek (Filesystem Path)
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__, 2));
}

// 3. Konfigurasi Database MySQL
define("HOST_DB", "localhost");
define("NAME_DB", "db_project");
define("USER_DB", "root");
define("PASS_DB", "");            // Password default XAMPP (sistem juga otomatis mencoba "12345" dan "root")

// 4. Informasi Aplikasi
define("APP_NAME", "UNKLAB SmartClinic");
define("APP_SHORT_NAME", "SmartClinic");
define("APP_DESC", "Sistem Manajemen UKESMAS Universitas Klabat berbasis Artificial Intelligence (Google Gemini)");
define("APP_VERSION", "1.0.0");
define("APP_AUTHOR", "UKESMAS Universitas Klabat");

// 5. Kunci Enkripsi & Keamanan Aplikasi (Ganti salt jika diperlukan)
define('APP_ENC_KEY', hash('sha256', 'UNKLAB_SMARTCLINIC_SECRET_KEY_2026', true));
define('APP_BIDX_KEY', hash('sha256', 'UNKLAB_SMARTCLINIC_BLIND_INDEX_SALT_KEY_2026', true));

// Muat kredensial rahasia lokal jika tersedia (diabaikan oleh .gitignore)
if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
}

// 6. KONFIGURASI GOOGLE GEMINI AI
//    Cara termudah mengisi API Key (pilih salah satu):
//      a) Buat file app/config/secrets.php dan definisikan GEMINI_API_KEY, ATAU
//      b) Set environment variable GEMINI_API_KEY, ATAU
//      c) Login sebagai superadmin -> menu "Pengaturan" -> isi "Gemini API Key" (tersimpan di database).
//    Dapatkan key gratis di: https://aistudio.google.com/apikey
//    Jika key kosong / kuota habis / jaringan gagal, sistem otomatis memakai
//    "Intelligent Contextual Fallback" (mesin aturan lokal) sehingga demo tetap berjalan.
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
}
define('GEMINI_MODEL', 'gemini-3.5-flash-lite'); // alternatif: gemini-flash-lite-latest, gemini-3.5-flash, gemini-3.8-flash
define('GEMINI_TIMEOUT', 25);                 // detik
define('GEMINI_SSL_VERIFY', true);            // biarkan true; CA bundle XAMPP terdeteksi otomatis

// 7. KONFIGURASI GOOGLE OAUTH 2.0 ("Login dengan Akun UNKLAB" - Khusus Mahasiswa)
//    Buat OAuth 2.0 Client ID (Web Application) di: https://console.cloud.google.com/apis/credentials
//    Tambahkan Authorized Redirect URI: http://localhost/template-final-project-ai/login/google_callback
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
}
define('GOOGLE_REDIRECT_URI', APP_PATH . '/login/google_callback');
