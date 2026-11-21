<?php
/**
 * Controller Konsultasi AI & Pengambilan Antrean (FIFO) - UNKLAB SmartClinic
 */
class triase extends ClinicController {

    public function index() {
        $u = $this->auth();
        if ($u['role'] === 'student') {
            $this->redirect('dashboard/');
        }

        $patModel = new Patient_model();
        $qModel   = new Queue_model();

        $patient = $u['patient_id'] ? $patModel->find($u['patient_id']) : $patModel->findByUser($u['id']);
        if ($patient && empty($_SESSION['patient-id'])) {
            $_SESSION['patient-id'] = (int)$patient['id'];
        }

        $isStaff  = in_array($u['role'], ['superadmin', 'clinicadmin', 'medstaff']);
        $patients = $isStaff ? $patModel->dropdown() : [];
        $activeQ  = ($patient && !empty($patient['id'])) ? $qModel->activeForPatient($patient['id']) : null;

        $this->render('triase/index', [
            'title'       => 'Konsultasi AI & Ambil Antrean - ' . APP_NAME,
            'active_menu' => 'triase',
            'patient'     => $patient,
            'patients'    => $patients,
            'is_staff'    => $isStaff,
            'active_q'    => $activeQ,
            'last_triage' => $_SESSION['last_triage'] ?? null,
            'samples'     => [
                ['Batuk pilek & tenggorokan sakit', 'Batuk berdahak dan pilek sejak 3 hari, tenggorokan nyeri saat menelan dan badan agak meriang.'],
                ['Nyeri ulu hati / maag saat ujian', 'Perut perih dan nyeri di ulu hati sejak tadi malam karena telat makan dan minum kopi saat belajar ujian, disertai mual.'],
                ['Demam tinggi 38.8°C & menggigil', 'Demam tinggi suhu 38.8 derajat sejak 2 hari, kepala pusing, menggigil, dan seluruh badan terasa pegal lemas.'],
                ['Diare berulang & lemas', 'BAB cair sudah 5 kali sejak pagi, perut mules melilit, badan terasa lemas dan haus.'],
                ['Sakit kepala tegang karena laptop', 'Sakit kepala berdenyut di pelipis dan tengkuk setelah begadang mengerjakan tugas di depan laptop.'],
                ['Sesak napas & nyeri dada', 'Sesak napas mendadak disertai nyeri dada dan keringat dingin.'],
            ],
        ]);
    }

    /** POST /triase/ambil_antrean - Ambil nomor antrean berurutan (FIFO) berdasarkan konsultasi AI */
    public function ambil_antrean() {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        $u = $isAjax ? $this->authJson() : $this->auth();
        $this->requirePost($isAjax);

        $patModel  = new Patient_model();
        $qModel    = new Queue_model();
        $isStaff   = in_array($u['role'], ['superadmin', 'clinicadmin', 'medstaff']);
        $patientId = $isStaff ? $this->intInput('patient_id', (int)$u['patient_id']) : (int)$u['patient_id'];

        $patient = $patientId ? $patModel->find($patientId) : $patModel->findByUser($u['id']);
        if (!$patient) {
            if ($isAjax) $this->json(['success' => false, 'message' => 'Profil pasien tidak ditemukan.'], 404);
            flash('danger', 'Profil pasien tidak ditemukan.');
            $this->redirect('dashboard/');
        }

        if (!$isStaff) {
            $existing = $qModel->activeForPatient($patient['id']);
            if ($existing) {
                if ($isAjax) {
                    $this->json([
                        'success'      => true,
                        'already'      => true,
                        'queue_number' => $existing['queue_number'],
                        'status'       => $existing['status'],
                        'position'     => $qModel->position($existing),
                        'message'      => 'Anda sudah memiliki nomor antrean aktif hari ini (' . $existing['queue_number'] . ').',
                    ]);
                }
                flash('info', 'Anda sudah memiliki nomor antrean aktif hari ini (' . $existing['queue_number'] . ').');
                $this->redirect('antrean/');
            }
        }

        $complaint   = $this->input('complaint', '');
        $consultJson = $_POST['triage_json'] ?? '';
        $consult     = json_decode($consultJson, true);

        if ($complaint === '' && !empty($_SESSION['last_triage']['complaint'])) {
            $complaint = $_SESSION['last_triage']['complaint'];
        }
        if (!is_array($consult) || empty($consult['kategori'])) {
            $consult = (new GeminiService())->triage($complaint ?: 'Pemeriksaan kesehatan umum', $patient);
        }

        $created = $qModel->create($patient, $u['id'], $complaint ?: 'Pemeriksaan kesehatan', $consult);
        $this->logActivity('Ambil Antrean', 'Nomor ' . $created['number'] . ' (' . ($consult['kategori'] ?? 'Umum') . ') untuk ' . $patient['nama']);

        if ($isAjax) {
            $qRow = $qModel->find($created['id']);
            $this->json([
                'success'      => true,
                'already'      => false,
                'queue_id'     => $created['id'],
                'queue_number' => $created['number'],
                'status'       => 'Menunggu',
                'kategori'     => $consult['kategori'] ?? 'Keluhan Umum',
                'position'     => $qRow ? $qModel->position($qRow) : 0,
                'live'         => $qModel->liveStatus(),
                'message'      => 'Nomor antrean ' . $created['number'] . ' berhasil diterbitkan.',
            ]);
        }

        flash('success', 'Nomor antrean ' . $created['number'] . ' berhasil diterbitkan. Silakan menunggu giliran Anda dipanggil.');
        $this->redirect($u['role'] === 'student' ? 'dashboard/' : 'antrean/');
    }
}
