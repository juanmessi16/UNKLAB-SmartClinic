<?php
/**
 * Controller Profil Akun & Ubah Password - UNKLAB SmartClinic
 */
class profil extends ClinicController {

    public function index() {
        $u      = $this->auth();
        $uModel = new User_model();

        $this->render('profil/index', [
            'title'       => 'Pengaturan Akun & Password - ' . APP_NAME,
            'active_menu' => 'profil',
            'account'     => $uModel->get_user_by_id($u['id']),
        ]);
    }

    public function ubah_password() {
        $u = $this->auth();
        $this->requirePost();

        $old  = (string)($_POST['password_lama'] ?? '');
        $new  = (string)($_POST['password_baru'] ?? '');
        $conf = (string)($_POST['konfirmasi_password'] ?? '');

        if (strlen($new) < 6 || $new !== $conf) {
            flash('danger', 'Password baru minimal 6 karakter dan harus cocok dengan konfirmasi.');
            $this->redirect('profil/');
        }

        $uModel = new User_model();
        if ($uModel->change_own_password($u['id'], $old, $new)) {
            $this->logActivity('Ubah Password', 'Pengguna memperbarui password akun');
            flash('success', 'Password Anda berhasil diperbarui.');
        } else {
            flash('danger', 'Password lama yang Anda masukkan salah.');
        }
        $this->redirect('profil/');
    }
}
