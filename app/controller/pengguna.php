<?php
/**
 * Controller Manajemen Pengguna (RBAC 5 Role) - UNKLAB SmartClinic
 */
class pengguna extends ClinicController {

    public function index() {
        $this->auth(['superadmin']);
        $uModel = new User_model();

        $this->render('pengguna/index', [
            'title'       => 'Manajemen Pengguna (RBAC 5 Role) - ' . APP_NAME,
            'active_menu' => 'pengguna',
            'users'       => $uModel->get_all_users(),
            'roles'       => User_model::ROLES,
        ]);
    }

    public function simpan() {
        $this->auth(['superadmin']);
        $this->requirePost();

        $uModel = new User_model();
        $id     = $this->intInput('id', 0);
        $d      = [
            'username'     => $this->input('username'),
            'nama_lengkap' => $this->input('nama_lengkap'),
            'email'        => $this->input('email'),
            'role'         => $this->input('role', 'student'),
            'password'     => (string)($_POST['password'] ?? ''),
            'status'       => $this->input('status', 'active'),
        ];

        if ($d['nama_lengkap'] === '' || $d['email'] === '') {
            flash('danger', 'Nama lengkap dan email wajib diisi.');
            $this->redirect('pengguna/');
        }

        if ($id > 0) {
            $uModel->update_user($id, $d);
            $this->logActivity('Update Pengguna', 'Memperbarui akun ' . $d['nama_lengkap']);
            flash('success', 'Data pengguna berhasil diperbarui.');
        } else {
            if ($uModel->exists($d['username'], $d['email'])) {
                flash('danger', 'Username atau email sudah terdaftar.');
                $this->redirect('pengguna/');
            }
            $uModel->create_user($d);
            $this->logActivity('Tambah Pengguna', 'Menambahkan akun ' . $d['username'] . ' (' . $d['role'] . ')');
            flash('success', 'Pengguna baru berhasil ditambahkan.');
        }
        $this->redirect('pengguna/');
    }

    public function toggle($id = 0) {
        $u = $this->auth(['superadmin']);
        $this->requirePost();

        $id = (int)$id;
        if ($id === (int)$u['id']) {
            flash('warning', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            $this->redirect('pengguna/');
        }
        $uModel = new User_model();
        $target = $uModel->get_user_by_id($id);
        if ($target) {
            $newStatus = $target['status'] === 'active' ? 'inactive' : 'active';
            $uModel->set_status($id, $newStatus);
            $this->logActivity('Ubah Status Pengguna', $target['username'] . ' -> ' . $newStatus);
            flash('info', 'Status akun ' . $target['username'] . ' diubah menjadi ' . $newStatus . '.');
        }
        $this->redirect('pengguna/');
    }
}
