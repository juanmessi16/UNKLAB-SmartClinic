<?php
/**
 * Controller Log Keamanan (WAF), Audit Postur PHP & Aktivitas - UNKLAB SmartClinic
 */
class keamanan extends ClinicController {

    public function index() {
        $this->auth(['superadmin']);
        $db       = Database::shared();
        $aktModel = new Aktivitas_model();

        $incidents = $db->fetchAll("SELECT * FROM security_incidents ORDER BY id DESC LIMIT 40");
        $logins    = $db->fetchAll("SELECT * FROM login_logs ORDER BY id DESC LIMIT 30");

        $this->render('keamanan/index', [
            'title'       => 'Monitor Keamanan WAF & Log Aktivitas - ' . APP_NAME,
            'active_menu' => 'keamanan',
            'audit'       => Security::audit_php2shell_posture(),
            'sessions'    => $this->get_active_sessions_list(),
            'activities'  => $aktModel->getRecent(40),
            'incidents'   => $incidents,
            'logins'      => $logins,
        ]);
    }

    public function cabut_sesi($id = 0) {
        $u = $this->auth(['superadmin']);
        $this->requirePost();

        $this->revoke_user_session_db((int)$id, $u['nama']);
        $this->logActivity('Revoke Sesi', 'Mencabut sesi aktif #' . (int)$id);
        flash('warning', 'Sesi pengguna berhasil dicabut.');
        $this->redirect('keamanan/');
    }
}
