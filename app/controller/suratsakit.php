<?php
/**
 * Controller Digital Sick Leave (Surat Keterangan Sakit) - UNKLAB SmartClinic
 */
class suratsakit extends ClinicController {

    public function index() {
        $u        = $this->auth();
        $emrModel = new Emr_model();
        $patModel = new Patient_model();
        $isStaff  = in_array($u['role'], ['superadmin', 'clinicadmin', 'medstaff']);

        $q      = $this->input('q', '');
        $status = $this->input('status', '');

        $filters = ['q' => $q, 'status' => $status];
        $quota   = null;
        if (!$isStaff) {
            $p = $u['patient_id'] ? $patModel->find($u['patient_id']) : $patModel->findByUser($u['id']);
            $filters['patient_id'] = (int)($p['id'] ?? -1);
            if ($p) $quota = $emrModel->quota($p['id']);
        }

        $this->render('suratsakit/index', [
            'title'       => 'Surat Keterangan Sakit Digital - ' . APP_NAME,
            'active_menu' => 'suratsakit',
            'is_staff'    => $isStaff,
            'leaves'      => $emrModel->leaveList($filters, 200),
            'quota'       => $quota,
            'filter'      => ['q' => $q, 'status' => $status],
        ]);
    }

    /** Halaman cetak Surat Keterangan Sakit ber-QR & kode verifikasi */
    public function cetak($id = 0) {
        $u        = $this->auth();
        $emrModel = new Emr_model();
        $leave    = $emrModel->leaveFind((int)$id);
        if (!$leave) {
            $this->abort(404, 'Surat keterangan sakit tidak ditemukan.');
        }

        $isStaff = in_array($u['role'], ['superadmin', 'clinicadmin', 'medstaff', 'lecturer']);
        if (!$isStaff && (int)$leave['patient_id'] !== (int)$u['patient_id']) {
            $this->abort(403, 'Anda hanya dapat mencetak surat keterangan sakit milik sendiri.');
        }

        $this->render('suratsakit/cetak', [
            'title'       => 'Surat Keterangan Sakit ' . $leave['kode_verifikasi'] . ' - ' . APP_NAME,
            'active_menu' => 'suratsakit',
            'leave'       => $leave,
        ]);
    }

    /** POST /suratsakit/cabut/{id} - Cabut surat sakit (khusus petugas) */
    public function cabut($id = 0) {
        $this->auth(['superadmin', 'clinicadmin', 'medstaff']);
        $this->requirePost();

        $emrModel = new Emr_model();
        $leave    = $emrModel->leaveFind((int)$id);
        if ($leave) {
            $emrModel->revoke((int)$id);
            $this->logActivity('Cabut Surat Sakit', 'Mencabut surat sakit ' . $leave['kode_verifikasi']);
            flash('warning', 'Surat sakit ' . $leave['kode_verifikasi'] . ' telah dicabut.');
        }
        $this->redirect('suratsakit/');
    }
}
