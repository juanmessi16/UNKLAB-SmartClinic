<?php
/** Model EMR: rekam medis, resep (potong stok otomatis), surat sakit digital */
class Emr_model {
    private $db;
    public function __construct() { $this->db = Database::shared(); }

    public function find($id) {
        return $this->db->fetchOne("SELECT r.*, p.nama, p.nim_nip, p.fakultas, p.prodi, p.golongan_darah, p.gender, p.tanggal_lahir, p.tipe, u.nama_lengkap AS dokter
            FROM medical_records r JOIN patients_profile p ON p.id = r.patient_id LEFT JOIN users u ON u.id = r.doctor_id WHERE r.id = ?", [(int)$id]);
    }

    public function prescriptions($recordId) {
        return $this->db->fetchAll("SELECT x.*, m.nama, m.satuan, m.kode FROM prescriptions x JOIN medicines m ON m.id = x.medicine_id WHERE x.record_id = ? ORDER BY x.id", [(int)$recordId]);
    }

    public function leaveByRecord($recordId) {
        return $this->db->fetchOne("SELECT * FROM sick_leaves WHERE record_id = ? LIMIT 1", [(int)$recordId]);
    }

    public function search($filters = [], $limit = 300) {
        $sql = "SELECT r.id, r.visit_date, r.diagnosis, r.kategori_penyakit, r.suhu, r.tekanan_sistolik, r.tekanan_diastolik, r.detak_jantung, p.nama, p.nim_nip, p.fakultas, p.prodi,
                (SELECT COUNT(*) FROM sick_leaves s WHERE s.record_id = r.id) AS ada_surat
                FROM medical_records r JOIN patients_profile p ON p.id = r.patient_id WHERE 1=1";
        $params = [];
        if (!empty($filters['patient_id'])) { $sql .= " AND r.patient_id = ?"; $params[] = (int)$filters['patient_id']; }
        if (!empty($filters['q'])) { $sql .= " AND (p.nama LIKE ? OR p.nim_nip LIKE ? OR r.diagnosis LIKE ?)"; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; }
        if (!empty($filters['kategori'])) { $sql .= " AND r.kategori_penyakit = ?"; $params[] = $filters['kategori']; }
        if (!empty($filters['dari'])) { $sql .= " AND DATE(r.visit_date) >= ?"; $params[] = $filters['dari']; }
        if (!empty($filters['sampai'])) { $sql .= " AND DATE(r.visit_date) <= ?"; $params[] = $filters['sampai']; }
        if (!empty($filters['fakultas'])) { $sql .= " AND p.fakultas = ?"; $params[] = $filters['fakultas']; }
        $sql .= " ORDER BY r.visit_date DESC LIMIT " . (int)$limit;
        return $this->db->fetchAll($sql, $params);
    }

    public function categories() {
        return array_column($this->db->fetchAll("SELECT DISTINCT kategori_penyakit FROM medical_records WHERE kategori_penyakit IS NOT NULL ORDER BY 1"), 'kategori_penyakit');
    }

    /**
     * Simpan pemeriksaan lengkap secara atomik:
     * rekam medis + resep (potong stok + log mutasi) + surat sakit + tutup antrean.
     */
    public function saveExamination($queue, $doctorId, $d, $rx, $leaveDays) {
        $this->db->beginTransaction();
        try {
            $recId = $this->db->insert(
                "INSERT INTO medical_records (queue_id, patient_id, doctor_id, tekanan_sistolik, tekanan_diastolik, suhu, detak_jantung, respirasi, saturasi_o2, berat_badan, keluhan_utama, anamnesis, diagnosis, kode_icd, kategori_penyakit, riwayat_alergi, tindakan, catatan, visit_date)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
                [$queue['id'], (int)$queue['patient_id'], (int)$doctorId,
                 $d['sistolik'], $d['diastolik'], $d['suhu'], $d['detak'], $d['respirasi'], $d['spo2'], $d['bb'],
                 $d['keluhan'], $d['anamnesis'], $d['diagnosis'], $d['icd'] ?: null, $d['kategori'] ?: null, $d['alergi'] ?: 'Tidak ada', $d['tindakan'], $d['catatan']]
            );
            foreach ($rx as $line) {
                $mid = (int)$line['medicine_id'];
                $qty = (int)$line['jumlah'];
                if ($mid <= 0 || $qty <= 0) continue;
                $m = $this->db->fetchOne("SELECT id, nama, stok FROM medicines WHERE id = ? FOR UPDATE", [$mid]);
                if (!$m) throw new RuntimeException('Obat tidak ditemukan.');
                if ((int)$m['stok'] < $qty) throw new RuntimeException('Stok ' . $m['nama'] . ' tidak mencukupi (tersedia ' . $m['stok'] . ', diminta ' . $qty . ').');
                $after = (int)$m['stok'] - $qty;
                $this->db->exec("INSERT INTO prescriptions (record_id, medicine_id, jumlah, aturan_pakai) VALUES (?,?,?,?)", [$recId, $mid, $qty, $line['aturan']]);
                $this->db->exec("UPDATE medicines SET stok = ? WHERE id = ?", [$after, $mid]);
                $this->db->exec("INSERT INTO medicine_stock_logs (medicine_id, tipe, jumlah, stok_sebelum, stok_sesudah, referensi_id, keterangan, user_id) VALUES (?,?,?,?,?,?,?,?)",
                    [$mid, 'Keluar', $qty, (int)$m['stok'], $after, $recId, 'Resep digital EMR #' . $recId, (int)$doctorId]);
            }
            $code = null;
            if ($leaveDays > 0) {
                $code = $this->generateCode();
                $this->db->exec("INSERT INTO sick_leaves (record_id, patient_id, kode_verifikasi, tanggal_mulai, tanggal_selesai, jumlah_hari, alasan, issued_by, status) VALUES (?,?,?,CURDATE(),DATE_ADD(CURDATE(), INTERVAL ? DAY),?,?,?, 'Aktif')",
                    [$recId, (int)$queue['patient_id'], $code, (int)$leaveDays - 1, (int)$leaveDays, 'Sakit: ' . $d['diagnosis'] . ' - memerlukan istirahat.', (int)$doctorId]);
            }
            if (!empty($d['alergi']) && mb_strtolower($d['alergi']) !== 'tidak ada') {
                $this->db->exec("UPDATE patients_profile SET riwayat_alergi = ? WHERE id = ?", [$d['alergi'], (int)$queue['patient_id']]);
            }
            $this->db->exec("UPDATE appointments_queue SET status = 'Selesai', handled_by = ? WHERE id = ?", [(int)$doctorId, (int)$queue['id']]);
            $this->db->commit();
            return ['record_id' => $recId, 'leave_code' => $code];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function generateCode() {
        do {
            $code = 'SKS-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));
            $exists = $this->db->fetchValue("SELECT COUNT(*) FROM sick_leaves WHERE kode_verifikasi = ?", [$code]);
        } while ($exists);
        return $code;
    }

    // ---------------- Surat Sakit ----------------
    public function leaveFind($id) {
        return $this->db->fetchOne("SELECT s.*, p.nama, p.nim_nip, p.fakultas, p.prodi, p.tipe, p.gender, p.tanggal_lahir, u.nama_lengkap AS dokter, r.diagnosis
            FROM sick_leaves s JOIN patients_profile p ON p.id = s.patient_id LEFT JOIN users u ON u.id = s.issued_by LEFT JOIN medical_records r ON r.id = s.record_id WHERE s.id = ?", [(int)$id]);
    }

    public function leaveByCode($code) {
        return $this->db->fetchOne("SELECT s.*, p.nama, p.nim_nip, p.fakultas, p.prodi, p.tipe, u.nama_lengkap AS dokter
            FROM sick_leaves s JOIN patients_profile p ON p.id = s.patient_id LEFT JOIN users u ON u.id = s.issued_by WHERE s.kode_verifikasi = ?", [strtoupper(trim($code))]);
    }

    public function leaveList($filters = [], $limit = 300) {
        $sql = "SELECT s.*, p.nama, p.nim_nip, p.fakultas FROM sick_leaves s JOIN patients_profile p ON p.id = s.patient_id WHERE 1=1";
        $params = [];
        if (!empty($filters['patient_id'])) { $sql .= " AND s.patient_id = ?"; $params[] = (int)$filters['patient_id']; }
        if (!empty($filters['q'])) { $sql .= " AND (p.nama LIKE ? OR p.nim_nip LIKE ? OR s.kode_verifikasi LIKE ?)"; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'aktif') $sql .= " AND s.status = 'Aktif' AND s.tanggal_selesai >= CURDATE()";
            elseif ($filters['status'] === 'selesai') $sql .= " AND s.status = 'Aktif' AND s.tanggal_selesai < CURDATE()";
            elseif ($filters['status'] === 'dicabut') $sql .= " AND s.status = 'Dicabut'";
        }
        $sql .= " ORDER BY s.created_at DESC LIMIT " . (int)$limit;
        return $this->db->fetchAll($sql, $params);
    }

    public static function leaveState($s) {
        if ($s['status'] === 'Dicabut') return ['Dicabut', 'danger'];
        if ($s['tanggal_selesai'] < date('Y-m-d')) return ['Telah Berakhir', 'secondary'];
        return ['Aktif', 'success'];
    }

    public function revoke($id) {
        return $this->db->exec("UPDATE sick_leaves SET status = 'Dicabut' WHERE id = ?", [(int)$id]);
    }

    public function activeLeaveCount() {
        return (int)$this->db->fetchValue("SELECT COUNT(*) FROM sick_leaves WHERE status='Aktif' AND tanggal_selesai >= CURDATE()");
    }

    /** Kuota izin sakit semester berjalan */
    public function quota($patientId) {
        $m = (int)date('n'); $y = (int)date('Y');
        if ($m >= 8) { $start = "$y-08-01"; }
        elseif ($m === 1) { $start = ($y - 1) . '-08-01'; }
        else { $start = "$y-02-01"; }
        $q = (int)$this->db->fetchValue("SELECT setting_value FROM pengaturan WHERE setting_key = 'kuota_izin_sakit'", [], 10);
        if ($q <= 0) $q = 10;
        $used = (int)$this->db->fetchValue("SELECT COALESCE(SUM(jumlah_hari),0) FROM sick_leaves WHERE patient_id = ? AND status='Aktif' AND tanggal_mulai >= ?", [(int)$patientId, $start]);
        return ['kuota' => $q, 'terpakai' => $used, 'sisa' => max(0, $q - $used), 'sejak' => $start];
    }
}
