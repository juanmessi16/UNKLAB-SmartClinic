<?php
/**
 * Controller Electronic Medical Records (EMR) - UNKLAB SmartClinic
 */
class emr extends ClinicController {

    /** Daftar seluruh EMR (untuk dokter/perawat & admin) */
    public function index() {
        $this->auth(['superadmin', 'clinicadmin', 'medstaff']);
        $emrModel = new Emr_model();
        $patModel = new Patient_model();

        $filters = [
            'q'        => $this->input('q', ''),
            'kategori' => $this->input('kategori', ''),
            'fakultas' => $this->input('fakultas', ''),
            'dari'     => $this->input('dari', ''),
            'sampai'   => $this->input('sampai', ''),
        ];

        $this->render('emr/index', [
            'title'         => 'Electronic Medical Records (EMR) - ' . APP_NAME,
            'active_menu'   => 'emr',
            'is_staff'      => true,
            'records'       => $emrModel->search($filters, 200),
            'categories'    => $emrModel->categories(),
            'fakultas_list' => $patModel->fakultasList(),
            'filter'        => $filters,
        ]);
    }

    /** Riwayat pemeriksaan pribadi untuk mahasiswa & dosen */
    public function riwayat() {
        $u        = $this->auth();
        $emrModel = new Emr_model();
        $patModel = new Patient_model();

        $patient = $u['patient_id'] ? $patModel->find($u['patient_id']) : $patModel->findByUser($u['id']);
        $pid     = (int)($patient['id'] ?? 0);

        $this->render('emr/index', [
            'title'         => 'Riwayat Pemeriksaan Medis Saya - ' . APP_NAME,
            'active_menu'   => 'riwayat',
            'is_staff'      => false,
            'records'       => $pid ? $emrModel->search(['patient_id' => $pid], 100) : [],
            'categories'    => [],
            'fakultas_list' => [],
            'filter'        => ['q' => '', 'kategori' => '', 'fakultas' => '', 'dari' => '', 'sampai' => ''],
        ]);
    }

    /** Form pemeriksaan dokter dari antrean (atau pemeriksaan baru) */
    public function periksa($queueId = 0) {
        $this->auth(['superadmin', 'clinicadmin', 'medstaff']);
        $qModel   = new Queue_model();
        $medModel = new Medicine_model();
        $patModel = new Patient_model();

        $queue = $queueId ? $qModel->find((int)$queueId) : null;
        if (!$queue) {
            flash('warning', 'Pilih pasien dari antrean terlebih dahulu untuk memulai pemeriksaan.');
            $this->redirect('antrean/');
        }

        if ($queue['status'] === 'Menunggu') {
            $qModel->call($queue['id'], $_SESSION['user-id']);
            $queue['status'] = 'Dipanggil';
        }

        $triage = json_decode($queue['triage_json'] ?? '', true) ?: [];

        $this->render('emr/periksa', [
            'title'       => 'Pemeriksaan Medis ' . $queue['nama'] . ' - ' . APP_NAME,
            'active_menu' => 'emr',
            'queue'       => $queue,
            'triage'      => $triage,
            'medicines'   => $medModel->available(),
        ]);
    }

    /** POST /emr/generate_soap - AI Clinical Assistant: buat draft SOAP otomatis untuk EMR */
    public function generate_soap() {
        $u = $this->authJson(['superadmin', 'clinicadmin', 'medstaff']);
        $this->requirePost(true);

        $queueId = $this->intInput('queue_id', 0);
        $keluhan = $this->input('keluhan_utama', '');
        $ringkasanAwal = '';
        $penyakitKronis = '';

        if ($queueId > 0) {
            $queue = (new Queue_model())->find($queueId);
            if ($queue) {
                if ($keluhan === '') {
                    $keluhan = (string)($queue['complaint'] ?? '');
                }
                $triage = json_decode($queue['triage_json'] ?? '', true) ?: [];
                $ringkasanAwal = (string)($triage['ringkasan_dokter'] ?? ($triage['anamnesis_summary'] ?? ''));
                $penyakitKronis = (string)($queue['penyakit_kronis'] ?? '');
            }
        }

        if (mb_strlen(trim($keluhan)) < 3) {
            $this->json(['success' => false, 'message' => 'Isi Keluhan Utama pasien terlebih dahulu sebelum membuat draft SOAP.']);
        }

        $input = [
            'keluhan_utama'     => $keluhan,
            'ringkasan_awal'    => $ringkasanAwal,
            'penyakit_kronis'   => $penyakitKronis,
            'tekanan_sistolik'  => $this->intInput('tekanan_sistolik', 120),
            'tekanan_diastolik' => $this->intInput('tekanan_diastolik', 80),
            'suhu'              => (float)($_POST['suhu'] ?? 36.8),
            'detak_jantung'     => $this->intInput('detak_jantung', 80),
            'respirasi'         => $this->intInput('respirasi', 18),
            'saturasi_o2'       => $this->intInput('saturasi_o2', 98),
            'berat_badan'       => (float)($_POST['berat_badan'] ?? 60),
        ];

        $gem  = new GeminiService();
        $soap = $gem->generateSoapDraft($input);

        // Catat ke ai_logs bila tabel tersedia
        try {
            $db = Database::shared();
            if ($db->isConnected() && $db->hasTable('ai_logs')) {
                $db->exec(
                    "INSERT INTO ai_logs (user_id, feature, prompt_summary, response_json, model, source, status) VALUES (?,?,?,?,?,?,?)",
                    [
                        $u['id'],
                        'SOAP',
                        mb_substr($keluhan, 0, 250),
                        json_encode($soap, JSON_UNESCAPED_UNICODE),
                        $soap['model'] ?? 'clinical-soap-engine',
                        $soap['source'] ?? 'fallback',
                        'ok',
                    ]
                );
            }
        } catch (Throwable $e) {}

        $this->json(['success' => true, 'soap' => $soap]);
    }

    /** POST /emr/simpan - Simpan EMR + resep (auto potong stok) + terbitkan surat sakit */
    public function simpan() {
        $u = $this->auth(['superadmin', 'clinicadmin', 'medstaff']);
        $this->requirePost();

        $qModel   = new Queue_model();
        $emrModel = new Emr_model();

        $queueId = $this->intInput('queue_id', 0);
        $queue   = $qModel->find($queueId);
        if (!$queue) {
            flash('danger', 'Data antrean tidak ditemukan.');
            $this->redirect('antrean/');
        }

        $d = [
            'sistolik'  => $this->intInput('tekanan_sistolik', 120),
            'diastolik' => $this->intInput('tekanan_diastolik', 80),
            'suhu'      => (float)($_POST['suhu'] ?? 36.8),
            'detak'     => $this->intInput('detak_jantung', 80),
            'respirasi' => $this->intInput('respirasi', 18),
            'spo2'      => $this->intInput('saturasi_o2', 98),
            'bb'        => (float)($_POST['berat_badan'] ?? 60),
            'keluhan'   => $this->input('keluhan_utama', $queue['complaint']),
            'anamnesis' => $this->input('anamnesis', ''),
            'diagnosis' => $this->input('diagnosis', ''),
            'icd'       => $this->input('kode_icd', ''),
            'kategori'  => $this->input('kategori_penyakit', 'Lainnya'),
            'alergi'    => $this->input('riwayat_alergi', 'Tidak ada'),
            'tindakan'  => $this->input('tindakan', ''),
            'catatan'   => $this->input('catatan', ''),
        ];

        if ($d['diagnosis'] === '') {
            flash('danger', 'Diagnosis dokter wajib diisi.');
            $this->redirect('emr/periksa/' . $queueId);
        }

        $medIds  = $_POST['rx_medicine_id'] ?? [];
        $qtys    = $_POST['rx_jumlah'] ?? [];
        $aturans = $_POST['rx_aturan'] ?? [];
        $rx      = [];
        if (is_array($medIds)) {
            foreach ($medIds as $idx => $mid) {
                $mid = (int)$mid;
                $qty = (int)($qtys[$idx] ?? 0);
                if ($mid > 0 && $qty > 0) {
                    $rx[] = [
                        'medicine_id' => $mid,
                        'jumlah'      => $qty,
                        'aturan'      => trim((string)($aturans[$idx] ?? '3x1 sesudah makan')),
                    ];
                }
            }
        }

        $leaveDays = $this->intInput('jumlah_hari_izin', 0);

        try {
            $res = $emrModel->saveExamination($queue, $u['id'], $d, $rx, $leaveDays);
            $this->logActivity('Simpan EMR', 'Rekam medis #' . $res['record_id'] . ' untuk ' . $queue['nama'] . ($res['leave_code'] ? ' + Surat Sakit ' . $res['leave_code'] : ''));
            flash('success', 'Rekam medis berhasil disimpan' . ($rx ? ', stok obat apotek otomatis dipotong' : '') . ($res['leave_code'] ? ', dan Surat Keterangan Sakit (' . $res['leave_code'] . ') diterbitkan.' : '.'));
            $this->redirect('emr/detail/' . $res['record_id']);
        } catch (Throwable $e) {
            flash('danger', 'Gagal menyimpan pemeriksaan: ' . $e->getMessage());
            $this->redirect('emr/periksa/' . $queueId);
        }
    }

    /** Detail 1 Rekam Medis Elektronik beserta resep & surat sakit */
    public function detail($id = 0) {
        $u        = $this->auth();
        $emrModel = new Emr_model();
        $rec      = $emrModel->find((int)$id);
        if (!$rec) {
            $this->abort(404, 'Rekam medis tidak ditemukan.');
        }

        $isStaff = in_array($u['role'], ['superadmin', 'clinicadmin', 'medstaff']);
        if (!$isStaff && (int)$rec['patient_id'] !== (int)$u['patient_id']) {
            $this->abort(403, 'Anda tidak berhak melihat rekam medis pasien lain.');
        }

        $this->render('emr/detail', [
            'title'         => 'Detail EMR #' . $rec['id'] . ' - ' . APP_NAME,
            'active_menu'   => $isStaff ? 'emr' : 'riwayat',
            'record'        => $rec,
            'prescriptions' => $emrModel->prescriptions($rec['id']),
            'leave'         => $emrModel->leaveByRecord($rec['id']),
            'is_staff'      => $isStaff,
        ]);
    }
}
