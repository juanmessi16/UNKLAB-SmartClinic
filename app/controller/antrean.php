<?php
/**
 * Controller Manajemen Antrean (FIFO Berurutan) - UNKLAB SmartClinic
 */
class antrean extends ClinicController {

    public function index() {
        $u        = $this->auth();
        $qModel   = new Queue_model();
        $patModel = new Patient_model();
        $isStaff  = in_array($u['role'], ['superadmin', 'clinicadmin', 'medstaff']);

        $date   = $this->input('tanggal', date('Y-m-d'));
        $status = $this->input('status', '');

        $patient   = $u['patient_id'] ? $patModel->find($u['patient_id']) : $patModel->findByUser($u['id']);
        $myActive  = ($patient && !empty($patient['id'])) ? $qModel->activeForPatient($patient['id']) : null;
        $myPos     = $myActive ? $qModel->position($myActive) : 0;
        $myHistory = ($patient && !empty($patient['id'])) ? $qModel->listForPatient($patient['id'], 20) : [];

        $this->render('antrean/index', [
            'title'       => ($isStaff ? 'Manajemen Antrean Klinik' : 'Antrean Berobat Saya') . ' - ' . APP_NAME,
            'active_menu' => 'antrean',
            'is_staff'    => $isStaff,
            'live'        => $qModel->liveStatus(),
            'queues'      => $isStaff ? $qModel->listFiltered($date, $status) : [],
            'filter'      => ['tanggal' => $date, 'status' => $status],
            'my_active'   => $myActive,
            'my_pos'      => $myPos,
            'my_history'  => $myHistory,
        ]);
    }

    /** POST /antrean/panggil_berikutnya - Panggil pasien menunggu urutan berikutnya (FIFO) */
    public function panggil_berikutnya() {
        $u = $this->auth(['superadmin', 'clinicadmin', 'medstaff']);
        $this->requirePost();

        $qModel = new Queue_model();
        $called = $qModel->callNext($u['id']);
        if ($called) {
            $this->logActivity('Panggil Antrean', 'Memanggil nomor ' . $called['queue_number'] . ' (' . $called['nama'] . ')');
            flash('success', 'Memanggil antrean ' . $called['queue_number'] . ' — ' . $called['nama'] . '.');
        } else {
            flash('info', 'Tidak ada antrean berstatus Menunggu untuk hari ini.');
        }
        $this->redirect('antrean/');
    }

    /** POST /antrean/panggil/{id} - Panggil nomor antrean tertentu */
    public function panggil($id = 0) {
        $u = $this->auth(['superadmin', 'clinicadmin', 'medstaff']);
        $this->requirePost();

        $qModel = new Queue_model();
        $q      = $qModel->find((int)$id);
        if ($q) {
            $qModel->call((int)$id, $u['id']);
            $this->logActivity('Panggil Antrean', 'Memanggil nomor ' . $q['queue_number']);
            flash('success', 'Nomor antrean ' . $q['queue_number'] . ' (' . $q['nama'] . ') berhasil dipanggil.');
        }
        $this->redirect('antrean/');
    }

    /** POST /antrean/batal/{id} - Batalkan antrean */
    public function batal($id = 0) {
        $u = $this->auth();
        $this->requirePost();

        $qModel  = new Queue_model();
        $q       = $qModel->find((int)$id);
        $isStaff = in_array($u['role'], ['superadmin', 'clinicadmin', 'medstaff']);
        if ($q && ($isStaff || (int)$q['patient_id'] === (int)$u['patient_id'])) {
            $qModel->setStatus((int)$id, 'Dibatalkan');
            $this->logActivity('Batalkan Antrean', 'Antrean ' . $q['queue_number'] . ' dibatalkan');
            flash('warning', 'Antrean ' . $q['queue_number'] . ' telah dibatalkan.');
        }
        $back = $u['role'] === 'student' ? 'dashboard/' : 'antrean/';
        $this->redirect($back);
    }

    /** GET /antrean/check_call - Endpoint AJAX cek panggilan antrean aktif (student & lecturer) */
    public function check_call() {
        $u = $this->authJson(['student', 'lecturer']);
        $patientId = (int)$u['patient_id'];
        if ($patientId <= 0) {
            $pat = (new Patient_model())->findByUser($u['id']);
            $patientId = (int)($pat['id'] ?? 0);
        }

        $call = (new Queue_model())->findUnacknowledgedCall($u['id'], $patientId);
        if (!$call) {
            $this->json(['has_call' => false]);
        }

        $this->json([
            'has_call'           => true,
            'queue_id'           => (int)$call['id'],
            'queue_number'       => (string)$call['queue_number'],
            'complaint_category' => (string)($call['triage_category'] ?: 'Pemeriksaan Umum'),
            'called_at'          => (string)($call['called_at'] ?? ''),
        ]);
    }

    /** POST /antrean/acknowledge_call - Konfirmasi panggilan antrean oleh pasien (student & lecturer) */
    public function acknowledge_call() {
        $u = $this->authJson(['student', 'lecturer']);
        $this->requirePost(true);

        $queueId = $this->intInput('queue_id', 0);
        if ($queueId <= 0) {
            $this->json(['success' => false, 'message' => 'ID antrean tidak valid.'], 400);
        }

        $patientId = (int)$u['patient_id'];
        if ($patientId <= 0) {
            $pat = (new Patient_model())->findByUser($u['id']);
            $patientId = (int)($pat['id'] ?? 0);
        }

        $qModel = new Queue_model();
        $q      = $qModel->find($queueId);
        $isOwner = $q && (
            ((int)($q['user_id'] ?? 0) > 0 && (int)$q['user_id'] === (int)$u['id']) ||
            ($patientId > 0 && (int)$q['patient_id'] === $patientId)
        );

        if (!$isOwner) {
            $this->json(['success' => false, 'message' => 'Tiket antrean tidak ditemukan atau bukan milik Anda.'], 403);
        }

        $qModel->acknowledgeCall($queueId, $u['id'], $patientId);
        $this->logActivity('Konfirmasi Panggilan Antrean', 'Mengonfirmasi panggilan nomor ' . $q['queue_number']);
        $this->json(['success' => true]);
    }
}
