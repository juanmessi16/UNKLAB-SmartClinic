<?php
/** Model Antrean FIFO (appointments_queue) - Urutan berurutan A-001, A-002, A-003 */
class Queue_model {
    private $db;
    public function __construct() { $this->db = Database::shared(); }

    /** Urutan murni FIFO: Dipanggil/Diperiksa di atas, lalu Menunggu urut berdasarkan ID/nomor antrean */
    private function order() {
        return "ORDER BY FIELD(q.status,'Dipanggil','Diperiksa','Menunggu','Selesai','Dibatalkan'), q.id ASC";
    }

    public function find($id) {
        return $this->db->fetchOne("SELECT q.*, p.nama, p.nim_nip, p.fakultas, p.prodi, p.riwayat_alergi, p.penyakit_kronis, p.golongan_darah, p.tipe, p.gender, p.tanggal_lahir
            FROM appointments_queue q JOIN patients_profile p ON p.id = q.patient_id WHERE q.id = ?", [(int)$id]);
    }

    /** Ringkasan live (publik) */
    public function liveStatus() {
        $today = date('Y-m-d');
        $called = $this->db->fetchOne("SELECT queue_number FROM appointments_queue WHERE queue_date = ? AND status IN ('Dipanggil','Diperiksa') ORDER BY called_at DESC LIMIT 1", [$today]);
        $wait = (int)$this->db->fetchValue("SELECT COUNT(*) FROM appointments_queue WHERE queue_date = ? AND status = 'Menunggu'", [$today]);
        $done = (int)$this->db->fetchValue("SELECT COUNT(*) FROM appointments_queue WHERE queue_date = ? AND status = 'Selesai'", [$today]);
        $next = $this->db->fetchOne("SELECT queue_number FROM appointments_queue q WHERE queue_date = ? AND status = 'Menunggu' ORDER BY q.id ASC LIMIT 1", [$today]);
        return [
            'dipanggil' => $called['queue_number'] ?? null,
            'menunggu' => $wait,
            'selesai' => $done,
            'berikutnya' => $next['queue_number'] ?? null,
            'estimasi_menit' => $wait * 8,
            'waktu' => date('H:i:s'),
        ];
    }

    public function listFiltered($date = '', $status = '') {
        $sql = "SELECT q.*, p.nama, p.nim_nip, p.fakultas, p.prodi FROM appointments_queue q JOIN patients_profile p ON p.id = q.patient_id WHERE 1=1";
        $params = [];
        if ($date !== '') { $sql .= " AND q.queue_date = ?"; $params[] = $date; }
        if ($status !== '') { $sql .= " AND q.status = ?"; $params[] = $status; }
        $sql .= " " . $this->order() . " LIMIT 300";
        return $this->db->fetchAll($sql, $params);
    }

    public function listForPatient($pid, $limit = 20) {
        return $this->db->fetchAll("SELECT q.* FROM appointments_queue q WHERE q.patient_id = ? ORDER BY q.created_at DESC LIMIT " . (int)$limit, [(int)$pid]);
    }

    public function activeForPatient($pid) {
        return $this->db->fetchOne("SELECT q.* FROM appointments_queue q WHERE q.patient_id = ? AND q.queue_date = ? AND q.status IN ('Menunggu','Dipanggil','Diperiksa') ORDER BY q.id DESC LIMIT 1", [(int)$pid, date('Y-m-d')]);
    }

    /** Posisi antrean pasien (FIFO): jumlah pasien menunggu yang mendaftar lebih dulu (id < id pasien). */
    public function position($q) {
        if ($q['status'] !== 'Menunggu') return 0;
        return (int)$this->db->fetchValue("SELECT COUNT(*) FROM appointments_queue WHERE queue_date = ? AND status = 'Menunggu' AND id < ?",
            [$q['queue_date'], (int)$q['id']]);
    }

    public function create($patient, $userId, $complaint, $consult = []) {
        $today = date('Y-m-d');
        $n = (int)$this->db->fetchValue("SELECT COUNT(*) FROM appointments_queue WHERE queue_date = ?", [$today]) + 1;
        $number = 'A-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
        $lokasi = trim(($patient['fakultas'] ?? '') . (!empty($patient['prodi']) ? ' - ' . $patient['prodi'] : ''));
        $id = $this->db->insert(
            "INSERT INTO appointments_queue (queue_number, queue_date, patient_id, user_id, complaint, triage_category, triage_json, status, asal_lokasi) VALUES (?,?,?,?,?,?,?,'Menunggu',?)",
            [$number, $today, (int)$patient['id'], $userId ?: null, $complaint, $consult['kategori'] ?? 'Keluhan Umum', json_encode($consult, JSON_UNESCAPED_UNICODE), $lokasi]
        );
        return ['id' => $id, 'number' => $number];
    }

    public function call($id, $staffId) {
        return $this->db->exec("UPDATE appointments_queue SET status = 'Dipanggil', called_at = NOW(), call_acknowledged = 0, call_acknowledged_at = NULL, handled_by = ? WHERE id = ? AND status = 'Menunggu'", [(int)$staffId, (int)$id]);
    }

    public function callNext($staffId) {
        $next = $this->db->fetchOne("SELECT q.id FROM appointments_queue q WHERE q.queue_date = ? AND q.status = 'Menunggu' ORDER BY q.id ASC LIMIT 1", [date('Y-m-d')]);
        if (!$next) return null;
        $this->call($next['id'], $staffId);
        return $this->find($next['id']);
    }

    /** Cek apakah pasien memiliki panggilan antrean hari ini yang belum dikonfirmasi */
    public function findUnacknowledgedCall($userId, $patientId = 0) {
        $today = date('Y-m-d');
        if ((int)$patientId > 0) {
            return $this->db->fetchOne(
                "SELECT id, queue_number, triage_category, called_at
                 FROM appointments_queue
                 WHERE queue_date = ?
                   AND status = 'Dipanggil'
                   AND call_acknowledged = 0
                   AND (user_id = ? OR patient_id = ?)
                 ORDER BY called_at DESC, id DESC
                 LIMIT 1",
                [$today, (int)$userId, (int)$patientId]
            );
        }
        return $this->db->fetchOne(
            "SELECT id, queue_number, triage_category, called_at
             FROM appointments_queue
             WHERE queue_date = ?
               AND status = 'Dipanggil'
               AND call_acknowledged = 0
               AND user_id = ?
             ORDER BY called_at DESC, id DESC
             LIMIT 1",
            [$today, (int)$userId]
        );
    }

    /** Tandai panggilan antrean sebagai sudah dikonfirmasi oleh pemiliknya */
    public function acknowledgeCall($queueId, $userId, $patientId = 0) {
        if ((int)$patientId > 0) {
            return $this->db->exec(
                "UPDATE appointments_queue
                 SET call_acknowledged = 1, call_acknowledged_at = NOW()
                 WHERE id = ? AND (user_id = ? OR patient_id = ?)",
                [(int)$queueId, (int)$userId, (int)$patientId]
            );
        }
        return $this->db->exec(
            "UPDATE appointments_queue
             SET call_acknowledged = 1, call_acknowledged_at = NOW()
             WHERE id = ? AND user_id = ?",
            [(int)$queueId, (int)$userId]
        );
    }

    public function setStatus($id, $status) {
        return $this->db->exec("UPDATE appointments_queue SET status = ? WHERE id = ?", [$status, (int)$id]);
    }
}
