<?php
/**
 * ClinicController - basis controller SmartClinic (RBAC, helper JSON/redirect/CSRF, layout data).
 * Semua controller modul meng-extend kelas ini; keamanan WAF tetap diwariskan dari Controller.
 */
class ClinicController extends Controller {

    protected $user = null;

    /** Wajib login + (opsional) batasi role. Mengembalikan array user. */
    protected function auth($roles = []) {
        $this->start_default_session_app();
        if (empty($_SESSION['user-id'])) {
            header('Location: ' . APP_PATH . '/login/?auth=1');
            exit();
        }
        $this->validateSingleDeviceSession(false);
        if (!empty($roles)) {
            $userRole = strtolower($_SESSION['user-role'] ?? '');
            $allowed = array_map('strtolower', (array)$roles);
            if (!in_array($userRole, $allowed)) {
                // Jika akun/peran di browser telah berganti di tab lain, arahkan bersih ke dashboard peran aktif
                header('Location: ' . APP_PATH . '/dashboard/');
                exit();
            }
        }
        $this->user = $this->currentUser();
        return $this->user;
    }

    /**
     * Validasi sesi Single-Device: memastikan 1 akun hanya aktif di 1 perangkat/browser.
     * Jika akun login di tempat lain (token DB berubah), sesi ini langsung dihancurkan.
     */
    protected function validateSingleDeviceSession($isAjax = false) {
        $userId = (int)($_SESSION['user-id'] ?? 0);
        if ($userId <= 0) return;

        $clientToken = (string)($_SESSION['session_token'] ?? '');

        try {
            $db = Database::shared();
            if ($db->isConnected()) {
                $userRow = $db->fetchOne("SELECT active_session_token, nama_lengkap FROM users WHERE id = ? LIMIT 1", [$userId]);
                $dbToken = $userRow ? (string)($userRow['active_session_token'] ?? '') : '';

                // Sinkronisasi otomatis nama akun demo mahasiswa jika di database masih nama lama
                if ($userId === 5 && ($userRow['nama_lengkap'] ?? '') === 'Christian Mandagi') {
                    $db->run("UPDATE users SET nama_lengkap = 'Juan Messi Septhian Tulengkey' WHERE id = 5");
                    $db->run("UPDATE patients_profile SET nama = 'Juan Messi Septhian Tulengkey' WHERE user_id = 5 OR nama = 'Christian Mandagi'");
                    $userRow['nama_lengkap'] = 'Juan Messi Septhian Tulengkey';
                    $_SESSION['user-name'] = 'Juan Messi Septhian Tulengkey';
                }

                // Migrasi / sinkronisasi awal bila salah satu token masih kosong
                if ($dbToken === '' && $clientToken !== '') {
                    $db->run("UPDATE users SET active_session_token = ? WHERE id = ?", [$clientToken, $userId]);
                    if (!empty($userRow['nama_lengkap'])) $_SESSION['user-name'] = $userRow['nama_lengkap'];
                    return;
                }
                if ($clientToken === '' && $dbToken !== '') {
                    $_SESSION['session_token'] = $dbToken;
                    if (!empty($userRow['nama_lengkap'])) $_SESSION['user-name'] = $userRow['nama_lengkap'];
                    return;
                }

                // Jika token tidak cocok: akun sedang digunakan di perangkat lain
                if ($clientToken === '' || $dbToken === '' || !hash_equals($dbToken, $clientToken)) {
                    $this->destroy_session_cleanly();

                    $wantsJson = $isAjax
                        || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

                    if ($wantsJson) {
                        $this->json([
                            'success'         => false,
                            'session_expired' => true,
                            'message'         => 'Akun digunakan di perangkat lain.'
                        ], 401);
                    }

                    header('Location: ' . APP_PATH . '/login/?kicked=1');
                    exit();
                }

                // Sinkronkan nama lengkap terkini ke session
                if (!empty($userRow['nama_lengkap'])) {
                    $_SESSION['user-name'] = $userRow['nama_lengkap'];
                }
            }
        } catch (Throwable $e) {
            // Hindari memblokir user jika ada kendala koneksi DB sementara
        }
    }

    protected function currentUser() {
        $nama = $_SESSION['user-name'] ?? 'Pengguna';
        if ($nama === 'Christian Mandagi') {
            $nama = 'Juan Messi Septhian Tulengkey';
            $_SESSION['user-name'] = $nama;
        }
        return [
            'id'         => (int)($_SESSION['user-id'] ?? 0),
            'nama'       => $nama,
            'username'   => $_SESSION['user-username'] ?? '',
            'role'       => $_SESSION['user-role'] ?? '',
            'email'      => $_SESSION['user-email'] ?? '',
            'avatar_url' => $_SESSION['user-avatar-url'] ?? '',
            'patient_id' => (int)($_SESSION['patient-id'] ?? 0),
        ];
    }

    /** Untuk endpoint AJAX: balas 401/403 JSON, bukan redirect. */
    protected function authJson($roles = []) {
        $this->start_default_session_app();
        if (empty($_SESSION['user-id'])) {
            $this->json(['success' => false, 'session_expired' => true, 'message' => 'Sesi berakhir. Silakan login kembali.'], 401);
        }
        $this->validateSingleDeviceSession(true);
        $this->user = $this->currentUser();
        if ($roles && !in_array($this->user['role'], (array)$roles)) {
            $this->json(['success' => false, 'message' => 'Peran Anda tidak memiliki izin untuk aksi ini.'], 403);
        }
        return $this->user;
    }

    protected function json($data, $status = 200) {
        $this->json_response($data, $status);
    }

    protected function input($key, $default = '') {
        $v = $_POST[$key] ?? ($_GET[$key] ?? $default);
        return is_string($v) ? trim($v) : $v;
    }

    protected function intInput($key, $default = 0) {
        $v = $_POST[$key] ?? ($_GET[$key] ?? $default);
        return is_numeric($v) ? (int)$v : (int)$default;
    }

    protected function redirect($path) {
        header('Location: ' . APP_PATH . '/' . ltrim($path, '/'));
        exit();
    }

    /** Pastikan request POST + CSRF valid. $ajax=true -> balas JSON. */
    protected function requirePost($ajax = false) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            if ($ajax) $this->json(['success' => false, 'message' => 'Gunakan metode POST.'], 405);
            $this->abort(405);
        }
        if (!$this->verify_csrf_token()) {
            if ($ajax) $this->json(['success' => false, 'message' => 'Token keamanan (CSRF) tidak valid. Muat ulang halaman.'], 403);
            flash('danger', 'Token keamanan (CSRF) tidak valid. Silakan ulangi aksi.');
            $back = $_SERVER['HTTP_REFERER'] ?? (APP_PATH . '/dashboard/');
            header('Location: ' . $back);
            exit();
        }
    }

    protected function render($view, $data = []) {
        $this->start_default_session_app();
        $data['user'] = $data['user'] ?? $this->currentUser();
        $data['csrf_token'] = $this->get_csrf_token();
        $data['flash'] = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_message']);
        $this->display($view, $data);
    }

    protected function logActivity($aktivitas, $detail = '') {
        try {
            $m = new Aktivitas_model();
            $m->log($_SESSION['user-id'] ?? null, $_SESSION['user-username'] ?? 'guest', $aktivitas, $detail);
        } catch (Throwable $e) {}
    }

    protected function hariIni() {
        $h = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        return $h[date('l')];
    }

    /** Catat penggunaan AI ke ai_analytics_logs. */
    protected function logAi($jenis, $input, $output, $result) {
        try {
            Database::shared()->exec(
                "INSERT INTO ai_analytics_logs (jenis, input_summary, output_json, model, sumber, user_id) VALUES (?,?,?,?,?,?)",
                [$jenis, mb_substr((string)$input, 0, 480), json_encode($output, JSON_UNESCAPED_UNICODE), (string)($result['model'] ?? ''), (string)($result['source'] ?? ''), (int)($_SESSION['user-id'] ?? 0) ?: null]
            );
        } catch (Throwable $e) {}
    }
}
