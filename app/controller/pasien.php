<?php
/**
 * Controller Data Pasien & Profil Kesehatan - UNKLAB SmartClinic
 */
class pasien extends ClinicController {

    /** Daftar seluruh pasien (khusus staf medis & admin) */
    public function index() {
        $this->auth(['superadmin', 'clinicadmin', 'medstaff']);
        $patModel = new Patient_model();

        $q = $this->input('q', '');

        $this->render('pasien/index', [
            'title'       => 'Data Pasien Kampus - ' . APP_NAME,
            'active_menu' => 'pasien',
            'patients'    => $patModel->search($q),
            'filter'      => ['q' => $q],
        ]);
    }

    /** Detail pasien beserta seluruh riwayat rekam medis & surat sakit */
    public function detail($id = 0) {
        $u   = $this->auth();
        $id  = (int)$id;
        $own = ((int)$u['patient_id'] === $id);
        if (!$own && !in_array($u['role'], ['superadmin', 'clinicadmin', 'medstaff'])) {
            $this->abort(403, 'Anda hanya dapat melihat profil medis milik sendiri.');
        }

        $patModel = new Patient_model();
        $emrModel = new Emr_model();
        $patient  = $patModel->find($id);
        if (!$patient) {
            $this->abort(404, 'Data pasien tidak ditemukan.');
        }

        $this->render('pasien/detail', [
            'title'       => 'Profil Medis ' . $patient['nama'] . ' - ' . APP_NAME,
            'active_menu' => $own ? 'profil' : 'pasien',
            'patient'     => $patient,
            'records'     => $emrModel->search(['patient_id' => $id], 50),
            'leaves'      => $emrModel->leaveList(['patient_id' => $id], 50),
            'quota'       => $emrModel->quota($id),
        ]);
    }

    /** Halaman profil kesehatan mandiri untuk mahasiswa/dosen */
    public function profil() {
        $u        = $this->auth();
        $patModel = new Patient_model();
        $emrModel = new Emr_model();

        $patient = $u['patient_id'] ? $patModel->find($u['patient_id']) : $patModel->findByUser($u['id']);
        if (!$patient) {
            flash('warning', 'Akun Anda belum terhubung dengan profil pasien klinik.');
            $this->redirect('dashboard/');
        }
        $_SESSION['patient-id'] = (int)$patient['id'];

        $this->render('pasien/profil', [
            'title'       => 'Profil Kesehatan Saya - ' . APP_NAME,
            'active_menu' => 'profil',
            'patient'     => $patient,
            'quota'       => $emrModel->quota($patient['id']),
        ]);
    }

    /** POST /pasien/simpan_profil - Simpan perubahan data kontak & medis */
    public function simpan_profil() {
        $u = $this->auth();
        $this->requirePost();

        $patModel = new Patient_model();
        $id       = $this->intInput('id', (int)$u['patient_id']);
        $isStaff  = in_array($u['role'], ['superadmin', 'clinicadmin', 'medstaff']);
        if (!$isStaff && $id !== (int)$u['patient_id']) {
            $this->abort(403);
        }

        $existing = $patModel->find($id);

        $patModel->updateMedicalProfile($id, [
            'no_hp'           => $this->input('no_hp'),
            'golongan_darah'  => $this->input('golongan_darah', '') ?: ($existing['golongan_darah'] ?? null),
            'riwayat_alergi'  => $this->input('riwayat_alergi', '') ?: ($existing['riwayat_alergi'] ?? 'Tidak ada'),
            'penyakit_kronis' => $this->input('penyakit_kronis'),
            'kontak_darurat'  => $this->input('kontak_darurat'),
        ]);

        $this->logActivity('Update Profil Pasien', 'Memperbarui data pasien #' . $id);
        flash('success', 'Profil dan informasi kontak berhasil diperbarui.');
        $this->redirect($isStaff && $id !== (int)$u['patient_id'] ? 'pasien/detail/' . $id : 'pasien/profil');
    }

    /** GET /pasien/riwayat_kunjungan/{id} - Endpoint AJAX JSON semua riwayat kunjungan pasien */
    public function riwayat_kunjungan($id = 0) {
        $this->authJson(['superadmin', 'clinicadmin', 'medstaff']);
        $id = $this->intInput('id', (int)$id);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID Pasien tidak valid.'], 400);
        }

        $patModel = new Patient_model();
        $patient  = $patModel->find($id);
        if (!$patient) {
            $this->json(['success' => false, 'message' => 'Data pasien tidak ditemukan.'], 404);
        }

        $db = Database::shared();
        $sql = "SELECT r.id, r.visit_date, r.diagnosis, r.kategori_penyakit, r.kode_icd,
                       r.keluhan_utama,
                       COALESCE(u.nama_lengkap, 'Dokter UKESMAS') AS dokter,
                       q.queue_number
                FROM medical_records r
                LEFT JOIN users u ON u.id = r.doctor_id
                LEFT JOIN appointments_queue q ON q.id = r.queue_id
                WHERE r.patient_id = ?
                ORDER BY r.visit_date DESC, r.id DESC";
        $records = $db->fetchAll($sql, [$id]);

        $visits = [];
        foreach ($records as $i => $rec) {
            $visits[] = [
                'no'           => $i + 1,
                'id'           => (int)$rec['id'],
                'tanggal'      => tgl($rec['visit_date'], true),
                'diagnosis'    => (string)($rec['diagnosis'] ?: 'Pemeriksaan rutin'),
                'kategori'     => (string)($rec['kategori_penyakit'] ?: 'Umum'),
                'kode_icd'     => (string)($rec['kode_icd'] ?? ''),
                'dokter'       => (string)$rec['dokter'],
                'queue_number' => (string)($rec['queue_number'] ?? ''),
                'keluhan'      => (string)($rec['keluhan_utama'] ?? ''),
            ];
        }

        $this->json([
            'success' => true,
            'patient' => [
                'id'      => (int)$patient['id'],
                'nama'    => (string)$patient['nama'],
                'nim_nip' => (string)$patient['nim_nip'],
                'total'   => count($visits),
            ],
            'visits'  => $visits,
        ]);
    }
}
