<?php
/**
 * Controller Verifikasi Surat Keterangan Sakit (Dosen & Publik) - UNKLAB SmartClinic
 */
class verifikasi extends ClinicController {

    public function index($kodeParam = '') {
        $this->start_default_session_app();
        $emrModel = new Emr_model();

        $kode = trim((string)($kodeParam ?: ($_GET['kode'] ?? ($_POST['kode'] ?? ''))));
        $result = null;
        if ($kode !== '') {
            $result = $emrModel->leaveByCode($kode);
        }

        $isLecturer = !empty($_SESSION['user-id']) && in_array(strtolower($_SESSION['user-role'] ?? ''), ['lecturer', 'dosen'], true);
        $liveQueue = null;
        $geminiStatus = null;
        if ($isLecturer) {
            $this->validateSingleDeviceSession(false);
            $liveQueue = (new Queue_model())->liveStatus();
            $geminiStatus = (new GeminiService())->getStatus();
        }

        $this->render('verifikasi/index', [
            'title'         => 'Verifikasi Surat Keterangan Sakit - ' . APP_NAME,
            'active_menu'   => 'verifikasi',
            'kode'          => strtoupper($kode),
            'result'        => $result,
            'recent'        => !empty($_SESSION['user-id']) ? $emrModel->leaveList([], 6) : [],
            'is_lecturer'   => $isLecturer,
            'live_queue'    => $liveQueue,
            'gemini_status' => $geminiStatus,
        ]);
    }
}
