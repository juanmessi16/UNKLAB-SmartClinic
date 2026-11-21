<?php
/**
 * Helper global untuk view & controller - UNKLAB SmartClinic
 */

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function url($path = '') {
    return rtrim(APP_PATH, '/') . '/' . ltrim($path, '/');
}

function asset($path) {
    return rtrim(APP_PATH, '/') . '/assets/' . ltrim($path, '/');
}

function tgl($date, $withTime = false) {
    if (empty($date)) return '-';
    $t = strtotime($date);
    if (!$t) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $s = date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
    if ($withTime) $s .= ', ' . date('H:i', $t);
    return $s;
}

function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function role_label($role) {
    $map = [
        'superadmin'  => 'Super Administrator',
        'clinicadmin' => 'Kepala UKESMAS',
        'medstaff'    => 'Dokter / Perawat',
        'lecturer'    => 'Dosen',
        'student'     => 'Mahasiswa',
    ];
    return $map[$role] ?? ucfirst((string)$role);
}

function category_badge($cat) {
    $c = trim((string)$cat) ?: 'Umum';
    return '<span class="badge badge-soft-primary">' . e($c) . '</span>';
}

function queue_status_badge($st) {
    $map = ['Menunggu' => 'warning', 'Dipanggil' => 'primary', 'Diperiksa' => 'info', 'Selesai' => 'success', 'Dibatalkan' => 'secondary'];
    return '<span class="badge badge-soft-' . ($map[$st] ?? 'secondary') . '">' . e($st) . '</span>';
}

/** Hitung stok status: 'habis' | 'kritis' | 'ok' */
function stock_status($stok, $min) {
    if ((int)$stok <= 0) return 'habis';
    if ((int)$stok <= (int)$min) return 'kritis';
    return 'ok';
}

function stock_badge($stok, $min) {
    $s = stock_status($stok, $min);
    $map = ['habis' => ['danger', 'Habis'], 'kritis' => ['danger', 'Kritis'], 'ok' => ['success', 'Aman']];
    return '<span class="badge badge-soft-' . $map[$s][0] . '">' . $map[$s][1] . '</span>';
}

function expiry_badge($date) {
    if (empty($date)) return '<span class="badge badge-soft-secondary">-</span>';
    $days = (int)floor((strtotime($date) - strtotime(date('Y-m-d'))) / 86400);
    if ($days < 0) return '<span class="badge badge-soft-danger">Kedaluwarsa</span>';
    if ($days <= 45) return '<span class="badge badge-soft-warning">' . $days . ' hari lagi</span>';
    return '<span class="badge badge-soft-success">' . tgl($date) . '</span>';
}

function flash($type, $text) {
    if (session_status() === PHP_SESSION_NONE) @session_start();
    $_SESSION['flash_message'] = ['type' => $type, 'text' => $text];
}

function initials($name) {
    $cleaned = preg_replace('/^(dr\.|Dr\.|Ns\.)\s*/i', '', (string)$name);
    $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleaned);
    $parts = array_filter(preg_split('/\s+/', trim($cleaned)));
    $s = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p !== '') $s .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $s ?: 'U';
}

/** Definisi menu sidebar per peran (RBAC). */
function sidebar_menu($role) {
    $dash = ['dashboard', 'Dashboard', 'bi-grid-1x2-fill', 'dashboard'];
    $triase = ['triase', 'Konsultasi AI & Antrean', 'bi-heart-pulse-fill', 'triase'];
    $antreanSaya = ['antrean', 'Antrean Saya', 'bi-ticket-perforated-fill', 'antrean'];
    $antreanKelola = ['antrean', 'Manajemen Antrean', 'bi-people-fill', 'antrean'];
    $riwayat = ['riwayat', 'Riwayat Pemeriksaan', 'bi-clipboard2-pulse-fill', 'emr/riwayat'];
    $suratSaya = ['suratsakit', 'Surat Sakit Saya', 'bi-file-earmark-medical-fill', 'suratsakit'];
    $suratKelola = ['suratsakit', 'Surat Sakit Digital', 'bi-file-earmark-medical-fill', 'suratsakit'];
    $verif = ['verifikasi', 'Verifikasi Surat Sakit', 'bi-patch-check-fill', 'verifikasi'];
    $emr = ['emr', 'Rekam Medis (EMR)', 'bi-journal-medical', 'emr'];
    $pasien = ['pasien', 'Data Pasien', 'bi-person-vcard-fill', 'pasien'];
    $obat = ['obat', 'Stok Obat & Apotek', 'bi-capsule-pill', 'obat'];
    $laporan = ['laporan', 'Laporan Klinik', 'bi-bar-chart-line-fill', 'laporan'];
    $analitik = ['analitik', 'AI Analytics & Forecast', 'bi-graph-up-arrow', 'analitik'];
    $jadwal = ['jadwal', 'Jadwal Dokter Jaga', 'bi-calendar2-week-fill', 'jadwal'];
    $cari = ['pencarian', 'Pencarian & Katalog', 'bi-search', 'pencarian'];
    $docs = ['dokumentasi', 'Dokumentasi Sistem', 'bi-diagram-3-fill', 'dokumentasi'];
    $profil = ['profil', 'Profil Kesehatan', 'bi-person-heart', 'pasien/profil'];

    switch ($role) {
        case 'student':
            $dashStudent = ['dashboard', 'Konsultasi AI & Ambil Antrean', 'bi-heart-pulse-fill', 'dashboard'];
            return ['Menu Utama' => [$dashStudent, $antreanSaya, $suratSaya], 'Informasi' => [$jadwal, $docs]];
        case 'lecturer':
        case 'dosen':
            return [
                'Menu Utama' => [$verif, $triase, $antreanSaya, $riwayat],
                'Informasi'  => [$jadwal, $docs]
            ];
        case 'medstaff':
            return ['Layanan Klinik' => [$dash, $antreanKelola, $emr, $suratKelola, $obat], 'Informasi' => [$jadwal]];
        case 'clinicadmin':
            return ['Manajemen Klinik' => [$dash, $antreanKelola, $emr, $pasien, $suratKelola, $obat, $laporan, $analitik, $jadwal], 'Informasi' => [$cari, $docs]];
        case 'superadmin':
            return [
                'Manajemen Klinik' => [$dash, $antreanKelola, $emr, $pasien, $suratKelola, $obat, $laporan, $analitik, $jadwal],
                'Administrasi Sistem' => [['pengguna', 'Manajemen Pengguna', 'bi-people', 'pengguna'], ['pengaturan', 'Pengaturan & Konfigurasi AI', 'bi-sliders', 'pengaturan'], ['keamanan', 'Log Keamanan (WAF)', 'bi-shield-lock-fill', 'keamanan']],
                'Informasi' => [$cari, $docs],
            ];
    }
    return ['Menu' => [$dash]];
}
