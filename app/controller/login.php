<?php
/**
 * Controller Autentikasi (Login / Logout + Google OAuth 2.0 Akun UNKLAB) - UNKLAB SmartClinic
 * 5 peran: superadmin, clinicadmin, medstaff, lecturer, student. Dilindungi CSRF + rate limit.
 */
class login extends ClinicController {

    private function isGoogleConfigured() {
        return defined('GOOGLE_CLIENT_ID') && defined('GOOGLE_CLIENT_SECRET')
            && trim((string)GOOGLE_CLIENT_ID) !== ''
            && trim((string)GOOGLE_CLIENT_SECRET) !== '';
    }

    public function index() {
        $this->start_default_session_app();
        if (!empty($_SESSION['user-id'])) {
            $dest = (!empty($_SESSION['user-role']) && in_array($_SESSION['user-role'], ['lecturer', 'dosen'], true)) ? 'verifikasi/' : 'dashboard/';
            $this->redirect($dest);
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        $notice = null;
        if (!empty($_GET['kicked'])) {
            $error = 'Sesi Anda telah berakhir karena akun ini sedang digunakan di perangkat lain.';
        } elseif (!empty($_GET['timeout'])) {
            $notice = 'Sesi Anda berakhir karena tidak aktif 15 menit. Silakan masuk kembali.';
        } elseif (!empty($_GET['revoked'])) {
            $notice = 'Sesi Anda dicabut oleh administrator.';
        } elseif (!empty($_GET['auth'])) {
            $notice = 'Silakan masuk untuk mengakses halaman tersebut.';
        } elseif (!empty($_GET['logout'])) {
            $notice = 'Anda telah keluar dengan aman.';
        }

        $db = Database::shared();
        $this->display('login/index', [
            'title'             => 'Masuk - ' . APP_NAME,
            'error'             => $error,
            'notice'            => $notice,
            'db_ready'          => $db->isConnected() && $db->hasTable('users'),
            'google_configured' => $this->isGoogleConfigured(),
            'google_redirect'   => defined('GOOGLE_REDIRECT_URI') ? GOOGLE_REDIRECT_URI : (APP_PATH . '/login/google_callback'),
            'demo_accounts'     => [
                ['superadmin', 'admin123', 'Super Administrator', 'Akses penuh, konfigurasi AI & log keamanan', 'bi-shield-lock-fill'],
                ['clinicadmin', 'admin123', 'Kepala UKESMAS', 'Inventaris obat, laporan & AI Forecasting', 'bi-hospital-fill'],
                ['medstaff', 'medis123', 'Dokter / Perawat', 'Antrean, EMR, resep & surat sakit', 'bi-heart-pulse-fill'],
                ['dosen', 'dosen123', 'Dosen', 'Verifikasi surat keterangan sakit', 'bi-person-workspace'],
                ['mahasiswa', 'mahasiswa123', 'Mahasiswa', 'Konsultasi AI, antrean & surat sakit', 'bi-mortarboard-fill'],
            ],
            'csrf_token'        => $this->get_csrf_token(),
        ]);
    }

    public function authenticate() {
        $this->start_default_session_app();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $this->redirect('login/');

        if (!$this->verify_csrf_token()) {
            $_SESSION['login_error'] = 'Token sesi tidak valid atau telah kedaluwarsa. Silakan coba lagi.';
            $this->redirect('login/');
        }
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            $_SESSION['login_error'] = 'Username dan password wajib diisi.';
            $this->redirect('login/');
        }
        $db = Database::shared();
        if (!$db->isConnected() || !$db->hasTable('users')) {
            $_SESSION['login_error'] = 'Database db_project belum siap. Jalankan inisialisasi database dari halaman beranda.';
            $this->redirect('login/');
        }
        if (!Security::check_login_rate_limit($username)) {
            Security::log_incident('LOGIN_RATE_LIMIT', $username, 'Blocked');
            $_SESSION['login_error'] = 'Terlalu banyak percobaan login gagal. Coba lagi dalam 15 menit.';
            $this->redirect('login/');
        }

        $user = (new User_model())->validate_credentials($username, $password);
        if ($user) {
            $this->completeLoginSession($user, 'Login manual sebagai ' . role_label($user['role']));
            $dest = in_array($user['role'], ['lecturer', 'dosen'], true) ? 'verifikasi/' : 'dashboard/';
            $this->redirect($dest);
        }
        $this->log_user_login(null, $username, 'Failed', 'Username atau password salah');
        $_SESSION['login_error'] = 'Username atau password yang Anda masukkan salah.';
        $this->redirect('login/');
    }

    /**
     * GET /login/google
     * Redirect mahasiswa ke Google OAuth 2.0 dengan prompt=select_account
     */
    public function google() {
        $this->start_default_session_app();
        if (!empty($_SESSION['user-id'])) {
            $dest = (!empty($_SESSION['user-role']) && in_array($_SESSION['user-role'], ['lecturer', 'dosen'], true)) ? 'verifikasi/' : 'dashboard/';
            $this->redirect($dest);
        }

        if (!$this->isGoogleConfigured()) {
            $_SESSION['login_error'] = 'Login dengan Akun UNKLAB (Google OAuth) belum dikonfigurasi. Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di app/config/config.php terlebih dahulu.';
            $this->redirect('login/');
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $params = [
            'client_id'     => GOOGLE_CLIENT_ID,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account',
            'state'         => $state,
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $authUrl);
        exit();
    }

    /**
     * GET /login/google_callback
     * Tukar code -> access_token -> userinfo -> validasi domain UNKLAB -> auto-provision mahasiswa -> login
     */
    public function google_callback() {
        $this->start_default_session_app();

        if (!$this->isGoogleConfigured()) {
            $_SESSION['login_error'] = 'Konfigurasi Google OAuth belum lengkap di app/config/config.php.';
            $this->redirect('login/');
        }

        if (!empty($_GET['error'])) {
            $_SESSION['login_error'] = 'Login Google dibatalkan atau ditolak oleh pengguna.';
            $this->redirect('login/');
        }

        $code  = trim((string)($_GET['code'] ?? ''));
        $state = trim((string)($_GET['state'] ?? ''));
        $expectedState = (string)($_SESSION['google_oauth_state'] ?? '');
        unset($_SESSION['google_oauth_state']);

        if ($code === '' || $state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            $_SESSION['login_error'] = 'Sesi autentikasi Google tidak valid atau telah kedaluwarsa. Silakan coba lagi.';
            $this->redirect('login/');
        }

        // 1. Tukarkan authorization code menjadi access_token
        $tokenResp = $this->googleHttpRequest('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]);

        if (!$tokenResp['ok'] || empty($tokenResp['data']['access_token'])) {
            $detail = $tokenResp['data']['error_description'] ?? ($tokenResp['error'] ?: 'Gagal menukar kode OAuth dengan Google.');
            $_SESSION['login_error'] = 'Autentikasi Google gagal: ' . $detail;
            $this->redirect('login/');
        }

        $accessToken = (string)$tokenResp['data']['access_token'];

        // 2. Ambil data profil pengguna dari Google Userinfo API
        $profileResp = $this->googleHttpRequest('https://www.googleapis.com/oauth2/v2/userinfo', null, [
            'Authorization: Bearer ' . $accessToken,
        ]);

        if (!$profileResp['ok'] || empty($profileResp['data']['email'])) {
            $_SESSION['login_error'] = 'Gagal mengambil informasi profil akun Google Anda. Silakan coba lagi.';
            $this->redirect('login/');
        }

        $profile  = $profileResp['data'];
        $googleId = (string)($profile['id'] ?? ($profile['sub'] ?? ''));
        $email    = strtolower(trim((string)($profile['email'] ?? '')));
        $fullName = trim((string)($profile['name'] ?? ''));
        $picture  = trim((string)($profile['picture'] ?? ''));

        // 3. Validasi Domain Ketat: HANYA @unklab.ac.id atau @student.unklab.ac.id
        if (!preg_match('/@(student\.)?unklab\.ac\.id$/i', $email)) {
            $this->log_user_login(null, $email, 'Failed', 'Domain non-UNKLAB ditolak pada Google OAuth');
            $_SESSION['login_error'] = 'Akses ditolak. Silakan gunakan akun Google resmi Universitas Klabat (@unklab.ac.id / @student.unklab.ac.id).';
            $this->redirect('login/');
        }

        // 4. Auto-Provisioning & Sinkronisasi DB (Khusus Role Mahasiswa)
        $db = Database::shared();
        if (!$db->isConnected() || !$db->hasTable('users')) {
            $_SESSION['login_error'] = 'Database db_project belum siap.';
            $this->redirect('login/');
        }

        $result = (new User_model())->find_or_create_google_student($googleId, $email, $fullName, $picture);
        if (!$result['ok']) {
            $this->log_user_login(null, $email, 'Failed', $result['error']);
            $_SESSION['login_error'] = $result['error'];
            $this->redirect('login/');
        }

        $user = $result['user'];
        $user['role'] = 'student'; // Pastikan selalu role mahasiswa

        $this->completeLoginSession($user, 'Login Google OAuth Akun UNKLAB (' . $email . ')');
        $this->redirect('dashboard/');
    }

    /** Set seluruh variabel session login + catat log & tracking sesi */
    private function completeLoginSession($user, $activityDetail) {
        session_regenerate_id(true);

        // Single-device session token unik
        $sessionToken = bin2hex(random_bytes(32));

        $_SESSION['user-id']         = (int)$user['id'];
        $_SESSION['user-name']       = $user['nama_lengkap'];
        $_SESSION['user-username']   = $user['username'];
        $_SESSION['user-role']       = $user['role'];
        $_SESSION['user-email']      = $user['email'];
        $_SESSION['user-avatar-url'] = $user['avatar_url'] ?? '';
        $_SESSION['patient-id']      = (int)($user['patient_id'] ?? 0);
        $_SESSION['session_token']   = $sessionToken;
        $_SESSION['LAST_ACTIVITY']   = time();

        // Simpan token unik & waktu login terakhir ke database users
        try {
            $db = Database::shared();
            if ($db->isConnected()) {
                $db->run(
                    "UPDATE users SET active_session_token = ?, last_login_at = NOW(), last_login = NOW() WHERE id = ?",
                    [$sessionToken, (int)$user['id']]
                );
            }
        } catch (Throwable $e) {}

        $this->log_user_login($user['id'], $user['username'], 'SUCCESS', $activityDetail);
        $this->track_user_session($user['id'], $user['username'], $user['nama_lengkap'], $user['role']);
        $this->logActivity('User Login', $activityDetail);
    }

    /** Helper cURL untuk komunikasi dengan Google OAuth 2.0 API (tanpa Composer) */
    private function googleHttpRequest($url, $postFields = null, $headers = []) {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'data' => null, 'error' => 'Ekstensi PHP cURL tidak aktif.'];
        }
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if (!ini_get('curl.cainfo')) {
            foreach (['C:\\xampp\\apache\\bin\\curl-ca-bundle.crt', 'C:\\xampp\\php\\extras\\ssl\\cacert.pem'] as $ca) {
                if (file_exists($ca)) { $opts[CURLOPT_CAINFO] = $ca; break; }
            }
        }
        if ($postFields !== null) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($postFields);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        if ($headers) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['ok' => false, 'data' => null, 'error' => 'Koneksi cURL gagal: ' . $err];
        }
        $decoded = json_decode($body, true);
        return [
            'ok'    => ($http >= 200 && $http < 300 && is_array($decoded)),
            'data'  => is_array($decoded) ? $decoded : null,
            'error' => $http >= 400 ? ('HTTP ' . $http) : '',
        ];
    }

    public function logout() {
        $this->start_default_session_app();
        if (!empty($_SESSION['user-id'])) {
            $userId = (int)$_SESSION['user-id'];
            try {
                $db = Database::shared();
                if ($db->isConnected()) {
                    $db->run("UPDATE users SET active_session_token = NULL WHERE id = ?", [$userId]);
                }
            } catch (Throwable $e) {}
            $this->logActivity('User Logout', 'Pengguna keluar dari sistem');
            $this->destroy_user_session_db(session_id());
        }
        $this->destroy_session_cleanly();
        header('Location: ' . APP_PATH . '/login/?logout=1');
        exit();
    }
}
