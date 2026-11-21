<?php
/** Model Konten: artikel kesehatan, jadwal dokter, pengaturan, konteks chatbot, dokumen pencarian */
class Content_model {
    private $db;
    public function __construct() { $this->db = Database::shared(); }

    public function articles($kategori = '') {
        if ($kategori !== '') return $this->db->fetchAll("SELECT * FROM health_articles WHERE kategori = ? ORDER BY judul", [$kategori]);
        return $this->db->fetchAll("SELECT * FROM health_articles ORDER BY kategori, judul");
    }
    public function article($id) { return $this->db->fetchOne("SELECT * FROM health_articles WHERE id = ?", [(int)$id]); }
    public function articleCategories() { return array_column($this->db->fetchAll("SELECT DISTINCT kategori FROM health_articles ORDER BY kategori"), 'kategori'); }

    public function schedules() { return $this->db->fetchAll("SELECT * FROM doctor_schedules ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jam_mulai, peran"); }
    public function saveSchedule($id, $d) {
        if ($id) return $this->db->exec("UPDATE doctor_schedules SET hari=?, jam_mulai=?, jam_selesai=?, nama_tenaga=?, peran=?, lokasi=? WHERE id=?", [$d['hari'], $d['jam_mulai'], $d['jam_selesai'], $d['nama_tenaga'], $d['peran'], $d['lokasi'], (int)$id]);
        return $this->db->insert("INSERT INTO doctor_schedules (hari, jam_mulai, jam_selesai, nama_tenaga, peran, lokasi) VALUES (?,?,?,?,?,?)", [$d['hari'], $d['jam_mulai'], $d['jam_selesai'], $d['nama_tenaga'], $d['peran'], $d['lokasi']]);
    }
    public function deleteSchedule($id) { return $this->db->exec("DELETE FROM doctor_schedules WHERE id = ?", [(int)$id]); }

    public function setting($key, $default = '') {
        $v = $this->db->fetchValue("SELECT setting_value FROM pengaturan WHERE setting_key = ?", [$key], null);
        return $v === null ? $default : $v;
    }
    public function settings() {
        return array_column($this->db->fetchAll("SELECT setting_key, setting_value FROM pengaturan"), 'setting_value', 'setting_key');
    }
    public function saveSetting($key, $value) {
        return $this->db->exec("INSERT INTO pengaturan (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", [$key, $value]);
    }

    /** Konteks RAG real-time untuk floating Asisten Klinik (data aktual dari MySQL db_project). */
    public function chatContext() {
        $q        = (new Queue_model())->liveStatus();
        $settings = $this->settings();
        $hariIni  = $this->hari();

        // 1. Struktur Pimpinan & Staf UKESMAS (hanya nama_lengkap & role, tanpa password/token)
        $stafUkesmas = $this->db->fetchAll(
            "SELECT nama_lengkap, role
             FROM users
             WHERE role IN ('clinicadmin', 'medstaff')
               AND status = 'active'
             ORDER BY FIELD(role, 'clinicadmin', 'medstaff'), id ASC"
        );

        // 2. Jadwal dokter/perawat jaga hari ini + jadwal mingguan lengkap
        $jadwalHariIni = $this->db->fetchAll(
            "SELECT hari, jam_mulai, jam_selesai, nama_tenaga, peran, lokasi
             FROM doctor_schedules
             WHERE hari = ?
             ORDER BY jam_mulai ASC, peran ASC",
            [$hariIni]
        );

        // 3. Katalog obat tersedia (stok > 0 dan belum kedaluwarsa, tanpa data sensitif pasien)
        $obatTersedia = $this->db->fetchAll(
            "SELECT id, nama, kategori, satuan, stok, stok_minimum, indikasi
             FROM medicines
             WHERE stok > 0
               AND (tanggal_kedaluwarsa IS NULL OR tanggal_kedaluwarsa >= CURDATE())
             ORDER BY nama ASC"
        );

        return [
            'profil_klinik'   => 'UKESMAS (Unit Kesehatan Mahasiswa) Universitas Klabat berlokasi di kampus Universitas Klabat, Airmadidi, Minahasa Utara, Sulawesi Utara. UKESMAS menyediakan layanan kesehatan kampus terpadu meliputi konsultasi keluhan awal berbasis AI, antrean online berurutan (FIFO), pemeriksaan langsung dokter/perawat, apotek & resep obat digital, serta penerbitan dan verifikasi surat keterangan sakit digital ber-QR.',
            'jam_operasional' => $settings['jam_operasional'] ?? 'Senin–Jumat 08:00–16:00 WITA',
            'kontak_telp'     => $settings['kontak_telp'] ?? '',
            'hari_ini'        => $hariIni,
            'tanggal_hari_ini'=> date('d-m-Y'),
            'staf_ukesmas'    => $stafUkesmas,
            'antrean'         => [
                'menunggu'       => (int)($q['menunggu'] ?? 0),
                'dipanggil'      => $q['dipanggil'] ?? null,
                'berikutnya'     => $q['berikutnya'] ?? null,
                'selesai'        => (int)($q['selesai'] ?? 0),
                'estimasi_menit' => (int)($q['estimasi_menit'] ?? 0),
            ],
            'jadwal_hari_ini' => $jadwalHariIni,
            'jadwal'          => $this->schedules(),
            'obat'            => $obatTersedia,
            'artikel'         => $this->articles(),
        ];
    }

    private function hari() {
        $h = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        return $h[date('l')];
    }

    /** Dokumen untuk pencarian semantik (artikel + katalog obat). */
    public function searchDocs($includeMedicines = true) {
        $docs = [];
        foreach ($this->articles() as $a) {
            $docs[] = ['type' => 'artikel', 'id' => $a['id'], 'title' => $a['judul'], 'text' => $a['isi'], 'tags' => $a['tags'] . ' ' . $a['kategori'], 'url' => url('pencarian/artikel/' . $a['id'])];
        }
        if ($includeMedicines) {
            foreach ($this->db->fetchAll("SELECT id, nama, kategori, satuan, stok, stok_minimum, indikasi FROM medicines ORDER BY nama") as $m) {
                $docs[] = ['type' => 'obat', 'id' => $m['id'], 'title' => $m['nama'] . ' (' . $m['kategori'] . ')', 'text' => 'Indikasi: ' . $m['indikasi'] . '. Stok ' . $m['stok'] . ' ' . $m['satuan'] . ' - ' . (stock_status($m['stok'], $m['stok_minimum']) === 'ok' ? 'tersedia' : (stock_status($m['stok'], $m['stok_minimum']) === 'habis' ? 'habis' : 'menipis')), 'tags' => $m['kategori'], 'url' => url('pencarian/obat/' . $m['id'])];
            }
        }
        return $docs;
    }
}
