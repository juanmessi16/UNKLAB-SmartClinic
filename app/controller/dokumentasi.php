<?php
/**
 * Controller Modul Dokumentasi & Analisis Sistem (Presentasi Akademik) - UNKLAB SmartClinic
 */
class dokumentasi extends ClinicController {

    public function index() {
        $this->start_default_session_app();
        $this->render('dokumentasi/index', [
            'title'       => 'Dokumentasi & Analisis Sistem (RCA, Fishbone, MVC+AI) - ' . APP_NAME,
            'active_menu' => 'dokumentasi',
        ]);
    }
}
