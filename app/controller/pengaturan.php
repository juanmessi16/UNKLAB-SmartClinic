<?php
/**
 * Controller Pengaturan Sistem & Konfigurasi Google Gemini AI - UNKLAB SmartClinic
 */
class pengaturan extends ClinicController {

    public function index() {
        $this->auth(['superadmin']);
        $cModel = new Content_model();
        $gem    = new GeminiService();
        $stats  = new Stats_model();

        $this->render('pengaturan/index', [
            'title'       => 'Pengaturan Sistem & Konfigurasi Gemini AI - ' . APP_NAME,
            'active_menu' => 'pengaturan',
            'settings'    => $cModel->settings(),
            'gemini'      => $gem->getStatus(),
            'ai_usage'    => $stats->aiUsage(),
        ]);
    }

    public function simpan() {
        $this->auth(['superadmin']);
        $this->requirePost();

        $cModel = new Content_model();
        $keys   = ['app_name', 'instansi', 'tahun_akademik', 'kontak_email', 'kontak_telp', 'jam_operasional', 'kuota_izin_sakit', 'gemini_model', 'gemini_enabled'];
        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $cModel->saveSetting($k, trim((string)$_POST[$k]));
            }
        }
        $apiKey = trim((string)($_POST['gemini_api_key'] ?? ''));
        if ($apiKey !== '' && strpos($apiKey, '••••') === false) {
            $cModel->saveSetting('gemini_api_key', $apiKey);
        } elseif (isset($_POST['clear_api_key']) && $_POST['clear_api_key'] === '1') {
            $cModel->saveSetting('gemini_api_key', '');
        }

        $this->logActivity('Simpan Pengaturan', 'Memperbarui konfigurasi aplikasi & Gemini AI');
        flash('success', 'Pengaturan sistem dan konfigurasi Google Gemini AI berhasil disimpan.');
        $this->redirect('pengaturan/');
    }
}
