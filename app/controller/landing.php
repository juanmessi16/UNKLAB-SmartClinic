<?php
/**
 * Controller Landing Page & Portal Depan - UNKLAB SmartClinic
 */
class landing extends ClinicController {

    public function index() {
        $this->start_default_session_app();
        $db = Database::shared();
        $diag = $db->getDiagnostics();

        $data = [
            'title' => 'UNKLAB SmartClinic - UKESMAS Cerdas Universitas Klabat',
            'db_diagnostics' => $diag,
            'ready' => $diag['is_connected'] && $diag['is_schema_ready'],
            'live' => ['dipanggil' => null, 'menunggu' => 0, 'selesai' => 0, 'berikutnya' => null, 'estimasi_menit' => 0],
            'schedule_today' => [],
            'settings' => [],
            'stats' => ['pasien' => 0, 'kunjungan' => 0, 'obat' => 0],
            'topics' => [
                ['Perut perih & maag', 'perut perih dan mual setelah telat makan', 'bi-emoji-dizzy'],
                ['Batuk pilek', 'batuk pilek dan tenggorokan sakit', 'bi-wind'],
                ['Demam tinggi', 'demam tinggi badan meriang', 'bi-thermometer-high'],
                ['Diare', 'diare dan lemas, obat apa yang cocok', 'bi-droplet-half'],
                ['Sakit kepala', 'sakit kepala berdenyut karena begadang', 'bi-lightning-charge'],
                ['Luka & terkilir', 'luka lecet dan kaki terkilir pertolongan pertama', 'bi-bandaid'],
                ['Alergi & gatal', 'kulit gatal dan ruam setelah makan seafood', 'bi-flower2'],
                ['Surat sakit', 'cara mendapatkan surat sakit untuk dosen', 'bi-file-earmark-medical'],
            ],
            'flash_message' => $_SESSION['flash_message'] ?? null,
        ];
        $data['flash'] = $data['flash_message'];
        unset($_SESSION['flash_message']);

        if ($data['ready']) {
            try {
                $data['live'] = (new Queue_model())->liveStatus();
                $c = new Content_model();
                $hari = $this->hariIni();
                foreach ($c->schedules() as $s) if ($s['hari'] === $hari) $data['schedule_today'][] = $s;
                $data['settings'] = $c->settings();
                $data['stats'] = [
                    'pasien' => (int)$db->fetchValue("SELECT COUNT(*) FROM patients_profile"),
                    'kunjungan' => (int)$db->fetchValue("SELECT COUNT(*) FROM medical_records"),
                    'obat' => (int)$db->fetchValue("SELECT COUNT(*) FROM medicines"),
                ];
            } catch (Throwable $e) {
                $data['ready'] = false;
            }
        }
        $this->render('landing/index', $data);
    }

    /** JSON: status antrean live (polling dari landing). */
    public function antrean_live() {
        try {
            $this->json(['success' => true] + (new Queue_model())->liveStatus());
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => 'Data antrean belum tersedia.'], 200);
        }
    }
}
