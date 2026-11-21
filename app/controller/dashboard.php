<?php
/**
 * Controller Dashboard Role-Based - UNKLAB SmartClinic
 * Menampilkan ringkasan metrik, grafik tren & distribusi penyakit, serta aksi cepat sesuai peran pengguna.
 */
class dashboard extends ClinicController {

    public function index() {
        $u = $this->auth();
        $role = $u['role'];

        // Role Dosen tidak lagi memiliki halaman dashboard; langsung arahkan ke Verifikasi Surat Sakit
        if (in_array($role, ['lecturer', 'dosen'], true)) {
            $this->redirect('verifikasi/');
        }

        $statsModel = new Stats_model();
        $queueModel = new Queue_model();
        $medModel   = new Medicine_model();
        $emrModel   = new Emr_model();
        $patModel   = new Patient_model();

        $overview      = $statsModel->overview();
        $liveQueue     = $queueModel->liveStatus();
        $dailyTrend    = $statsModel->dailyTrend(14);
        $monthlyTrend  = $statsModel->monthlyTrend(6);
        $diseaseDist   = $statsModel->diseaseDistribution(14);
        $byFakultas    = $statsModel->diseaseByGroup('fakultas', 14);
        $criticalMeds  = $medModel->criticalList(5);
        $activeQueue   = $statsModel->activeQueue(6);
        $recentVisits  = $statsModel->recentVisits(6);
        $topMeds       = $statsModel->topMedicineUsage(14, 5);
        $lastForecast  = $statsModel->lastAnalysis('Forecast');

        // Data spesifik mahasiswa / dosen (jika punya profil pasien)
        $patientProfile = null;
        $myActiveQueue  = null;
        $myQueuePos     = 0;
        $myRecords      = [];
        $myLeaves       = [];
        $myQuota        = ['kuota' => 10, 'terpakai' => 0, 'sisa' => 10];

        $pid = (int)$u['patient_id'];
        if (!$pid) {
            $found = $patModel->findByUser($u['id']);
            if ($found) {
                $pid = (int)$found['id'];
                $_SESSION['patient-id'] = $pid;
            }
        }
        if ($pid > 0) {
            $patientProfile = $patModel->find($pid);
            $myActiveQueue  = $queueModel->activeForPatient($pid);
            if ($myActiveQueue) {
                $myQueuePos = $queueModel->position($myActiveQueue);
            }
            $myRecords = $emrModel->search(['patient_id' => $pid], 5);
            $myLeaves  = $emrModel->leaveList(['patient_id' => $pid], 5);
            $myQuota   = $emrModel->quota($pid);
        }

        // Untuk dosen: daftar surat sakit terbaru mahasiswa untuk diverifikasi cepat
        $recentLeaves = [];
        if (in_array($role, ['lecturer', 'superadmin', 'clinicadmin', 'medstaff'])) {
            $recentLeaves = $emrModel->leaveList([], 6);
        }

        // Log aktivitas terbaru untuk superadmin
        $recentLogs = [];
        if ($role === 'superadmin') {
            $recentLogs = (new Aktivitas_model())->getRecent(6);
        }

        $geminiStatus = (new GeminiService())->getStatus();

        // Tampilan khusus role mahasiswa: fokus Konsultasi AI & Ambil Antrean
        if ($role === 'student') {
            $this->render('dashboard/student', [
                'title'           => 'Konsultasi AI & Ambil Antrean - ' . APP_NAME,
                'active_menu'     => 'dashboard',
                'charts'          => false,
                'live_queue'      => $liveQueue,
                'patient_profile' => $patientProfile,
                'my_active_queue' => $myActiveQueue,
                'my_queue_pos'    => $myQueuePos,
                'gemini_status'   => $geminiStatus,
                'samples'         => [
                    ['Demam & menggigil', 'Demam tinggi suhu 38.8 derajat sejak 2 hari, kepala pusing, menggigil, dan badan terasa lemas.'],
                    ['Batuk pilek', 'Batuk berdahak dan pilek sejak 3 hari, tenggorokan nyeri saat menelan dan badan agak meriang.'],
                    ['Nyeri ulu hati / maag', 'Perut perih dan nyeri di ulu hati sejak tadi malam karena telat makan dan minum kopi, disertai mual.'],
                    ['Sakit kepala', 'Sakit kepala berdenyut di pelipis dan tengkuk setelah lama menatap laptop dan kurang tidur.'],
                ],
            ]);
            return;
        }

        $this->render('dashboard/index', [
            'title'           => 'Dashboard - ' . APP_NAME,
            'active_menu'     => 'dashboard',
            'charts'          => true,
            'overview'        => $overview,
            'live_queue'      => $liveQueue,
            'daily_trend'     => $dailyTrend,
            'monthly_trend'   => $monthlyTrend,
            'disease_dist'    => $diseaseDist,
            'by_fakultas'     => $byFakultas,
            'critical_meds'   => $criticalMeds,
            'active_queue'    => $activeQueue,
            'recent_visits'   => $recentVisits,
            'top_meds'        => $topMeds,
            'last_forecast'   => $lastForecast,
            'patient_profile' => $patientProfile,
            'my_active_queue' => $myActiveQueue,
            'my_queue_pos'    => $myQueuePos,
            'my_records'      => $myRecords,
            'my_leaves'       => $myLeaves,
            'my_quota'        => $myQuota,
            'recent_leaves'   => $recentLeaves,
            'recent_logs'     => $recentLogs,
            'gemini_status'   => $geminiStatus,
        ]);
    }
}
