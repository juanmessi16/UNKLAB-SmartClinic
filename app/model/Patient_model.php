<?php
/** Model Pasien (patients_profile) */
class Patient_model {
    private $db;
    public function __construct() { $this->db = Database::shared(); }

    public function find($id) {
        return $this->db->fetchOne("SELECT * FROM patients_profile WHERE id = ?", [(int)$id]);
    }

    public function findByUser($userId) {
        return $this->db->fetchOne("SELECT * FROM patients_profile WHERE user_id = ? LIMIT 1", [(int)$userId]);
    }

    public function search($q = '', $fakultas = '') {
        $sql = "SELECT p.*, (SELECT COUNT(*) FROM medical_records r WHERE r.patient_id = p.id) AS total_kunjungan,
                       (SELECT MAX(r.visit_date) FROM medical_records r WHERE r.patient_id = p.id) AS kunjungan_terakhir
                FROM patients_profile p WHERE 1=1";
        $params = [];
        if ($q !== '') { $sql .= " AND (p.nama LIKE ? OR p.nim_nip LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
        if ($fakultas !== '') { $sql .= " AND p.fakultas = ?"; $params[] = $fakultas; }
        $sql .= " ORDER BY p.nama ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function fakultasList() {
        return array_column($this->db->fetchAll("SELECT DISTINCT fakultas FROM patients_profile WHERE fakultas IS NOT NULL ORDER BY fakultas"), 'fakultas');
    }

    public function updateMedicalProfile($id, $d) {
        return $this->db->exec(
            "UPDATE patients_profile SET no_hp = ?, golongan_darah = ?, riwayat_alergi = ?, penyakit_kronis = ?, kontak_darurat = ? WHERE id = ?",
            [$d['no_hp'], $d['golongan_darah'], $d['riwayat_alergi'], $d['penyakit_kronis'] ?: null, $d['kontak_darurat'], (int)$id]
        );
    }

    public function dropdown() {
        return $this->db->fetchAll("SELECT id, nim_nip, nama FROM patients_profile ORDER BY nama");
    }
}
