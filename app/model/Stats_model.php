<?php
/** Model Statistik, Laporan, & Agregat untuk AI Forecasting */
class Stats_model {
    private $db;
    public function __construct() { $this->db = Database::shared(); }

    public function overview() {
        $db = $this->db;
        return [
            'kunjungan_hari_ini' => (int)$db->fetchValue("SELECT COUNT(*) FROM appointments_queue WHERE queue_date = CURDATE() AND status <> 'Dibatalkan'"),
            'kunjungan_kemarin' => (int)$db->fetchValue("SELECT COUNT(*) FROM appointments_queue WHERE queue_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status <> 'Dibatalkan'"),
            'antrean_aktif' => (int)$db->fetchValue("SELECT COUNT(*) FROM appointments_queue WHERE queue_date = CURDATE() AND status IN ('Menunggu','Dipanggil','Diperiksa')"),
            'obat_kritis' => (int)$db->fetchValue("SELECT COUNT(*) FROM medicines WHERE stok <= stok_minimum"),
            'surat_aktif' => (int)$db->fetchValue("SELECT COUNT(*) FROM sick_leaves WHERE status='Aktif' AND tanggal_selesai >= CURDATE()"),
            'total_pasien' => (int)$db->fetchValue("SELECT COUNT(*) FROM patients_profile"),
            'total_rekam' => (int)$db->fetchValue("SELECT COUNT(*) FROM medical_records"),
            'kunjungan_7_hari' => (int)$db->fetchValue("SELECT COUNT(*) FROM medical_records WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"),
        ];
    }

    public function dailyTrend($days = 14) {
        $rows = $this->db->fetchAll("SELECT DATE(visit_date) d, COUNT(*) n FROM medical_records WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL " . ((int)$days - 1) . " DAY) GROUP BY DATE(visit_date)");
        $map = array_column($rows, 'n', 'd');
        $labels = []; $vals = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $labels[] = date('d M', strtotime($d));
            $vals[] = (int)($map[$d] ?? 0);
        }
        return ['labels' => $labels, 'values' => $vals];
    }

    public function monthlyTrend($months = 6) {
        $rows = $this->db->fetchAll("SELECT DATE_FORMAT(visit_date,'%Y-%m') ym, COUNT(*) n FROM medical_records WHERE visit_date >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL " . ((int)$months - 1) . " MONTH) GROUP BY ym");
        $map = array_column($rows, 'n', 'ym');
        $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $labels = []; $vals = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $t = strtotime(date('Y-m-01') . " -$i month");
            $labels[] = $bulan[(int)date('n', $t)] . ' ' . date('y', $t);
            $vals[] = (int)($map[date('Y-m', $t)] ?? 0);
        }
        return ['labels' => $labels, 'values' => $vals];
    }

    public function diseaseDistribution($days = 14) {
        $rows = $this->db->fetchAll("SELECT COALESCE(kategori_penyakit,'Lainnya') k, COUNT(*) n FROM medical_records WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL " . ((int)$days - 1) . " DAY) GROUP BY k ORDER BY n DESC");
        return ['labels' => array_column($rows, 'k'), 'values' => array_map('intval', array_column($rows, 'n'))];
    }

    /** Matriks penyakit x fakultas untuk stacked bar. */
    public function diseaseByGroup($by = 'fakultas', $days = 14) {
        $col = "REPLACE(p.fakultas,'Fakultas ','')";
        $rows = $this->db->fetchAll("SELECT $col g, COALESCE(r.kategori_penyakit,'Lainnya') k, COUNT(*) n FROM medical_records r JOIN patients_profile p ON p.id = r.patient_id
            WHERE r.visit_date >= DATE_SUB(CURDATE(), INTERVAL " . ((int)$days - 1) . " DAY) GROUP BY g, k");
        $groups = []; $cats = [];
        foreach ($rows as $r) { $groups[$r['g']] = ($groups[$r['g']] ?? 0) + $r['n']; $cats[$r['k']] = ($cats[$r['k']] ?? 0) + $r['n']; }
        arsort($groups); arsort($cats);
        $gl = array_keys($groups); $cl = array_keys($cats);
        $datasets = [];
        foreach ($cl as $c) {
            $arr = [];
            foreach ($gl as $g) $arr[] = 0;
            foreach ($rows as $r) if ($r['k'] === $c) $arr[array_search($r['g'], $gl)] = (int)$r['n'];
            $datasets[] = ['label' => $c, 'data' => $arr];
        }
        return ['labels' => $gl, 'datasets' => $datasets];
    }

    public function recentVisits($limit = 6) {
        return $this->db->fetchAll("SELECT r.id, r.visit_date, r.diagnosis, r.kategori_penyakit, p.nama, p.fakultas FROM medical_records r JOIN patients_profile p ON p.id = r.patient_id ORDER BY r.visit_date DESC LIMIT " . (int)$limit);
    }

    /** Antrean aktif hari ini urut FIFO (berurutan A-001, A-002, ...) */
    public function activeQueue($limit = 6) {
        return $this->db->fetchAll("SELECT q.*, p.nama, p.fakultas FROM appointments_queue q JOIN patients_profile p ON p.id = q.patient_id WHERE q.queue_date = CURDATE() AND q.status IN ('Menunggu','Dipanggil','Diperiksa')
            ORDER BY FIELD(q.status,'Dipanggil','Diperiksa','Menunggu'), q.id ASC LIMIT " . (int)$limit);
    }

    public function topMedicineUsage($days = 14, $limit = 5) {
        return $this->db->fetchAll("SELECT m.nama, m.satuan, SUM(x.jumlah) total FROM prescriptions x JOIN medicines m ON m.id = x.medicine_id JOIN medical_records r ON r.id = x.record_id
            WHERE r.visit_date >= DATE_SUB(CURDATE(), INTERVAL " . ((int)$days - 1) . " DAY) GROUP BY m.id ORDER BY total DESC LIMIT " . (int)$limit);
    }

    /** Laporan klinik dengan filter */
    public function report($dari, $sampai, $fakultas = '', $kategori = '') {
        $w = " WHERE DATE(r.visit_date) BETWEEN ? AND ?";
        $p = [$dari, $sampai];
        if ($fakultas !== '') { $w .= " AND p.fakultas = ?"; $p[] = $fakultas; }
        if ($kategori !== '') { $w .= " AND r.kategori_penyakit = ?"; $p[] = $kategori; }
        $rows = $this->db->fetchAll("SELECT r.id, r.visit_date, p.nim_nip, p.nama, p.fakultas, p.prodi, r.diagnosis, r.kategori_penyakit, r.suhu,
                (SELECT COALESCE(SUM(x.jumlah),0) FROM prescriptions x WHERE x.record_id = r.id) AS item_obat,
                (SELECT COUNT(*) FROM sick_leaves s WHERE s.record_id = r.id) AS surat
            FROM medical_records r JOIN patients_profile p ON p.id = r.patient_id $w ORDER BY r.visit_date DESC", $p);
        $byCat = $this->db->fetchAll("SELECT COALESCE(r.kategori_penyakit,'Lainnya') k, COUNT(*) n FROM medical_records r JOIN patients_profile p ON p.id = r.patient_id $w GROUP BY k ORDER BY n DESC", $p);
        $byFak = $this->db->fetchAll("SELECT p.fakultas k, COUNT(*) n FROM medical_records r JOIN patients_profile p ON p.id = r.patient_id $w GROUP BY k ORDER BY n DESC", $p);
        return ['rows' => $rows, 'by_kategori' => $byCat, 'by_fakultas' => $byFak];
    }

    // ----------------- AGREGAT UNTUK GEMINI FORECASTER -----------------
    public function forecastAggregate() {
        $db = $this->db;
        $daily = $db->fetchAll("SELECT DATE(visit_date) d, COALESCE(kategori_penyakit,'Lainnya') k, COUNT(*) n FROM medical_records WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d, k ORDER BY d");
        $harian = [];
        foreach ($daily as $r) $harian[$r['d']][$r['k']] = (int)$r['n'];
        $per = [];
        foreach ($daily as $r) {
            $isThisWeek = strtotime($r['d']) >= strtotime('-6 day', strtotime(date('Y-m-d')));
            $per[$r['k']] = $per[$r['k']] ?? ['minggu_ini' => 0, 'minggu_lalu' => 0];
            $per[$r['k']][$isThisWeek ? 'minggu_ini' : 'minggu_lalu'] += (int)$r['n'];
        }
        $fak = $db->fetchAll("SELECT p.fakultas g, COUNT(*) n FROM medical_records r JOIN patients_profile p ON p.id=r.patient_id WHERE r.visit_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY g ORDER BY n DESC");
        $obat = $db->fetchAll("SELECT m.id, m.nama, m.kategori, m.satuan, m.stok, m.stok_minimum, DATEDIFF(m.tanggal_kedaluwarsa, CURDATE()) AS kedaluwarsa_hari,
                COALESCE((SELECT SUM(l.jumlah) FROM medicine_stock_logs l WHERE l.medicine_id=m.id AND l.tipe='Keluar' AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)),0) AS dipakai_14_hari,
                COALESCE((SELECT SUM(l.jumlah) FROM medicine_stock_logs l WHERE l.medicine_id=m.id AND l.tipe='Keluar' AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)),0) AS dipakai_7_hari
            FROM medicines m ORDER BY m.id");
        foreach ($obat as &$o) {
            foreach (['id', 'stok', 'stok_minimum', 'dipakai_14_hari', 'dipakai_7_hari'] as $f) $o[$f] = (int)$o[$f];
            $o['kedaluwarsa_hari'] = $o['kedaluwarsa_hari'] === null ? null : (int)$o['kedaluwarsa_hari'];
        }
        unset($o);
        return [
            'tanggal_analisis' => date('Y-m-d'),
            'periode' => date('Y-m-d', strtotime('-13 day')) . ' s/d ' . date('Y-m-d'),
            'konteks' => 'Universitas Klabat, periode perkuliahan dan ujian semester',
            'kasus_harian' => $harian,
            'kasus_per_kategori' => $per,
            'kasus_per_fakultas' => $fak,
            'obat' => $obat,
        ];
    }

    public function lastAnalysis($jenis = 'Forecast') {
        $row = $this->db->fetchOne("SELECT * FROM ai_analytics_logs WHERE jenis = ? ORDER BY id DESC LIMIT 1", [$jenis]);
        if ($row) $row['data'] = json_decode($row['output_json'], true);
        return $row;
    }

    public function aiUsage() {
        return $this->db->fetchAll("SELECT jenis, sumber, COUNT(*) n FROM ai_analytics_logs GROUP BY jenis, sumber ORDER BY jenis");
    }
}
