<?php
/**
 * Model Pengguna (User Model) - UNKLAB SmartClinic
 * Autentikasi 5 peran: superadmin, clinicadmin, medstaff, lecturer, student.
 */
class User_model {

    const ROLES = ['superadmin', 'clinicadmin', 'medstaff', 'lecturer', 'student'];

    private $db;

    public function __construct() {
        $this->db = Database::shared();
    }

    public function validate_credentials($username, $password) {
        if (!$this->db->isConnected()) return false;
        $user = $this->db->fetchOne("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1", [trim($username), trim($username)]);
        if ($user && password_verify($password, $user['password'])) {
            $this->db->exec("UPDATE users SET last_login = NOW() WHERE id = ?", [(int)$user['id']]);
            $patient = $this->db->fetchOne("SELECT id FROM patients_profile WHERE user_id = ? LIMIT 1", [(int)$user['id']]);
            $user['patient_id'] = $patient['id'] ?? 0;
            return $user;
        }
        return false;
    }

    public function get_user_by_id($id) {
        return $this->db->fetchOne("SELECT id, username, nama_lengkap, email, google_id, role, avatar, avatar_url, active_session_token, last_login_at, status, last_login, created_at FROM users WHERE id = ? LIMIT 1", [(int)$id]);
    }

    public function get_all_users() {
        return $this->db->fetchAll("SELECT id, username, nama_lengkap, email, google_id, role, avatar, avatar_url, active_session_token, last_login_at, status, last_login, created_at FROM users ORDER BY FIELD(role,'superadmin','clinicadmin','medstaff','lecturer','student'), id");
    }

    public function get_user_count() {
        return (int)$this->db->fetchValue("SELECT COUNT(*) FROM users");
    }

    public function exists($username, $email, $exceptId = 0) {
        return (int)$this->db->fetchValue("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id <> ?", [$username, $email, (int)$exceptId]) > 0;
    }

    public function create_user($d) {
        $hash = password_hash($d['password'], PASSWORD_BCRYPT);
        $role = in_array($d['role'], self::ROLES) ? $d['role'] : 'student';
        return $this->db->insert("INSERT INTO users (username, password, nama_lengkap, email, role, status) VALUES (?,?,?,?,?,?)",
            [trim($d['username']), $hash, trim($d['nama_lengkap']), trim($d['email']), $role, ($d['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active']);
    }

    public function update_user($id, $d) {
        $role = in_array($d['role'], self::ROLES) ? $d['role'] : 'student';
        $this->db->exec("UPDATE users SET nama_lengkap = ?, email = ?, role = ? WHERE id = ?", [trim($d['nama_lengkap']), trim($d['email']), $role, (int)$id]);
        if (!empty($d['password'])) $this->reset_password($id, $d['password']);
        return true;
    }

    public function reset_password($id, $newPassword) {
        return $this->db->exec("UPDATE users SET password = ? WHERE id = ?", [password_hash($newPassword, PASSWORD_BCRYPT), (int)$id]);
    }

    public function set_status($id, $status) {
        return $this->db->exec("UPDATE users SET status = ? WHERE id = ?", [$status === 'inactive' ? 'inactive' : 'active', (int)$id]);
    }

    public function change_own_password($id, $old, $new) {
        $u = $this->db->fetchOne("SELECT password FROM users WHERE id = ?", [(int)$id]);
        if (!$u || !password_verify($old, $u['password'])) return false;
        $this->reset_password($id, $new);
        return true;
    }

    /**
     * Cari atau buat otomatis akun mahasiswa dari Google OAuth 2.0 (@unklab.ac.id / @student.unklab.ac.id).
     * Mengembalikan array ['ok' => true, 'user' => ...] atau ['ok' => false, 'error' => ...].
     */
    public function find_or_create_google_student($googleId, $email, $fullName, $avatarUrl = '') {
        if (!$this->db->isConnected()) {
            return ['ok' => false, 'error' => 'Database tidak terhubung.'];
        }

        $email     = strtolower(trim((string)$email));
        $googleId  = trim((string)$googleId);
        $avatarUrl = trim((string)$avatarUrl);
        $prefix    = explode('@', $email)[0] ?? 'mahasiswa';
        $fullName  = trim((string)$fullName) !== '' ? trim((string)$fullName) : ucfirst($prefix);

        // 1. Cari berdasarkan google_id atau email
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1",
            [$googleId, $email]
        );

        if ($user) {
            if ($user['status'] !== 'active') {
                return ['ok' => false, 'error' => 'Akun Anda sedang dinonaktifkan. Hubungi administrator UKESMAS.'];
            }
            // Pastikan login Google hanya untuk role mahasiswa (student), tidak boleh mengakses role admin/medis/dosen
            if ($user['role'] !== 'student') {
                return ['ok' => false, 'error' => 'Login Google UNKLAB khusus untuk mahasiswa. Akun ' . role_label($user['role']) . ' silakan masuk melalui form login manual.'];
            }
            // Perbarui nama, google_id, avatar_url, dan last_login
            $this->db->exec(
                "UPDATE users SET google_id = ?, nama_lengkap = ?, avatar_url = ?, last_login = NOW() WHERE id = ?",
                [$googleId, $fullName, $avatarUrl ?: null, (int)$user['id']]
            );
            $user['google_id']    = $googleId;
            $user['nama_lengkap'] = $fullName;
            $user['avatar_url']   = $avatarUrl;
        } else {
            // 2. Auto-provisioning akun baru mahasiswa di tabel users
            $baseUsername = preg_replace('/[^a-zA-Z0-9._-]/', '', $prefix);
            if ($baseUsername === '') $baseUsername = 'mhs' . mt_rand(1000, 9999);
            $baseUsername = substr($baseUsername, 0, 42);
            $username     = $baseUsername;
            $suffix       = 1;
            while ((int)$this->db->fetchValue("SELECT COUNT(*) FROM users WHERE username = ?", [$username]) > 0) {
                $username = substr($baseUsername, 0, 38) . $suffix++;
            }

            $randomHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
            $userId = $this->db->insert(
                "INSERT INTO users (username, password, nama_lengkap, email, google_id, role, avatar, avatar_url, status, last_login)
                 VALUES (?, ?, ?, ?, ?, 'student', 'default-avatar.png', ?, 'active', NOW())",
                [$username, $randomHash, $fullName, $email, $googleId, $avatarUrl ?: null]
            );

            $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        }

        // 3. Pastikan baris profil pasien di patients_profile ada & namanya sinkron
        $patient = $this->db->fetchOne("SELECT * FROM patients_profile WHERE user_id = ? LIMIT 1", [(int)$user['id']]);
        if (!$patient) {
            // Gunakan prefix email bila menyerupai NIM (mis. s22110012 / 22110012), atau fallback MHS-{id}
            $nimCandidate = preg_match('/^[sS]?\d{6,15}$/', $prefix) ? strtoupper($prefix) : ('MHS-' . str_pad((string)$user['id'], 4, '0', STR_PAD_LEFT));
            if ((int)$this->db->fetchValue("SELECT COUNT(*) FROM patients_profile WHERE nim_nip = ?", [$nimCandidate]) > 0) {
                $nimCandidate = 'MHS-' . $user['id'] . '-' . mt_rand(10, 99);
            }
            $patientId = $this->db->insert(
                "INSERT INTO patients_profile (user_id, nim_nip, nama, tipe, gender, fakultas, prodi, riwayat_alergi)
                 VALUES (?, ?, ?, 'mahasiswa', 'L', 'Universitas Klabat', 'Mahasiswa Aktif', 'Tidak ada')",
                [(int)$user['id'], $nimCandidate, $fullName]
            );
        } else {
            $patientId = (int)$patient['id'];
            if ($patient['nama'] !== $fullName) {
                $this->db->exec("UPDATE patients_profile SET nama = ? WHERE id = ?", [$fullName, $patientId]);
            }
        }

        $user['patient_id'] = (int)$patientId;
        return ['ok' => true, 'user' => $user];
    }
}
