<?php
/** Model Obat & Inventaris Apotek (medicines, medicine_stock_logs) */
class Medicine_model {
    private $db;
    public function __construct() { $this->db = Database::shared(); }

    public function find($id) { return $this->db->fetchOne("SELECT * FROM medicines WHERE id = ?", [(int)$id]); }

    public function search($q = '', $kategori = '', $status = '') {
        $sql = "SELECT * FROM medicines WHERE 1=1";
        $p = [];
        if ($q !== '') { $sql .= " AND (nama LIKE ? OR kode LIKE ? OR indikasi LIKE ?)"; $p[] = "%$q%"; $p[] = "%$q%"; $p[] = "%$q%"; }
        if ($kategori !== '') { $sql .= " AND kategori = ?"; $p[] = $kategori; }
        if ($status === 'kritis') $sql .= " AND stok <= stok_minimum";
        elseif ($status === 'aman') $sql .= " AND stok > stok_minimum";
        elseif ($status === 'kedaluwarsa') $sql .= " AND tanggal_kedaluwarsa IS NOT NULL AND tanggal_kedaluwarsa <= DATE_ADD(CURDATE(), INTERVAL 45 DAY)";
        $sql .= " ORDER BY nama ASC";
        return $this->db->fetchAll($sql, $p);
    }

    public function all() { return $this->search(); }

    public function available() {
        return $this->db->fetchAll("SELECT id, nama, satuan, stok, stok_minimum, kategori, indikasi FROM medicines WHERE stok > 0 AND (tanggal_kedaluwarsa IS NULL OR tanggal_kedaluwarsa >= CURDATE()) ORDER BY nama");
    }

    public function categories() {
        return array_column($this->db->fetchAll("SELECT DISTINCT kategori FROM medicines ORDER BY kategori"), 'kategori');
    }

    public function counts() {
        return [
            'total' => (int)$this->db->fetchValue("SELECT COUNT(*) FROM medicines"),
            'kritis' => (int)$this->db->fetchValue("SELECT COUNT(*) FROM medicines WHERE stok <= stok_minimum"),
            'kedaluwarsa' => (int)$this->db->fetchValue("SELECT COUNT(*) FROM medicines WHERE tanggal_kedaluwarsa IS NOT NULL AND tanggal_kedaluwarsa < CURDATE()"),
            'segera_kedaluwarsa' => (int)$this->db->fetchValue("SELECT COUNT(*) FROM medicines WHERE tanggal_kedaluwarsa IS NOT NULL AND tanggal_kedaluwarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 45 DAY)"),
            'nilai' => (int)$this->db->fetchValue("SELECT COALESCE(SUM(stok*harga),0) FROM medicines"),
        ];
    }

    public function criticalList($limit = 6) {
        return $this->db->fetchAll("SELECT * FROM medicines WHERE stok <= stok_minimum ORDER BY (stok / GREATEST(stok_minimum,1)) ASC LIMIT " . (int)$limit);
    }

    public function save($id, $d) {
        $exp = !empty($d['tanggal_kedaluwarsa']) ? $d['tanggal_kedaluwarsa'] : null;
        if ($id) {
            return $this->db->exec("UPDATE medicines SET nama=?, kategori=?, satuan=?, stok_minimum=?, harga=?, tanggal_kedaluwarsa=?, lokasi_rak=?, indikasi=? WHERE id=?",
                [$d['nama'], $d['kategori'], $d['satuan'], (int)$d['stok_minimum'], (int)$d['harga'], $exp, $d['lokasi_rak'], $d['indikasi'], (int)$id]);
        }
        $next = (int)$this->db->fetchValue("SELECT COALESCE(MAX(id),0)+1 FROM medicines");
        $kode = 'OBT-' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
        $newId = $this->db->insert("INSERT INTO medicines (kode, nama, kategori, satuan, stok, stok_minimum, harga, tanggal_kedaluwarsa, lokasi_rak, indikasi) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$kode, $d['nama'], $d['kategori'], $d['satuan'], 0, (int)$d['stok_minimum'], (int)$d['harga'], $exp, $d['lokasi_rak'], $d['indikasi']]);
        $initial = (int)($d['stok_awal'] ?? 0);
        if ($initial > 0) $this->restock($newId, $initial, 'Stok awal obat baru', $_SESSION['user-id'] ?? null, false);
        return $newId;
    }

    public function delete($id) {
        $used = (int)$this->db->fetchValue("SELECT COUNT(*) FROM prescriptions WHERE medicine_id = ?", [(int)$id]);
        if ($used > 0) return false;
        $this->db->exec("DELETE FROM medicines WHERE id = ?", [(int)$id]);
        return true;
    }

    /** Tambah stok (restock) + catat mutasi. */
    public function restock($id, $qty, $ket, $userId, $updateExpiry = null) {
        $qty = (int)$qty;
        if ($qty <= 0) return false;
        $m = $this->find($id);
        if (!$m) return false;
        $before = (int)$m['stok'];
        $after = $before + $qty;
        $this->db->exec("UPDATE medicines SET stok = ? WHERE id = ?", [$after, (int)$id]);
        if (is_string($updateExpiry) && $updateExpiry !== '') {
            $this->db->exec("UPDATE medicines SET tanggal_kedaluwarsa = ? WHERE id = ?", [$updateExpiry, (int)$id]);
        }
        $this->db->exec("INSERT INTO medicine_stock_logs (medicine_id, tipe, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id) VALUES (?,?,?,?,?,?,?)",
            [(int)$id, 'Masuk', $qty, $before, $after, $ket ?: 'Restock', $userId ?: null]);
        return true;
    }

    /** Penyesuaian stok (stok opname) ke nilai absolut. */
    public function adjust($id, $newStock, $ket, $userId) {
        $m = $this->find($id);
        if (!$m) return false;
        $before = (int)$m['stok'];
        $newStock = max(0, (int)$newStock);
        $this->db->exec("UPDATE medicines SET stok = ? WHERE id = ?", [$newStock, (int)$id]);
        $this->db->exec("INSERT INTO medicine_stock_logs (medicine_id, tipe, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id) VALUES (?,?,?,?,?,?,?)",
            [(int)$id, 'Penyesuaian', abs($newStock - $before), $before, $newStock, $ket ?: 'Stok opname', $userId ?: null]);
        return true;
    }

    public function logs($medicineId = 0, $tipe = '', $limit = 150) {
        $sql = "SELECT l.*, m.nama AS obat, m.satuan, u.nama_lengkap AS petugas FROM medicine_stock_logs l JOIN medicines m ON m.id = l.medicine_id LEFT JOIN users u ON u.id = l.user_id WHERE 1=1";
        $p = [];
        if ($medicineId) { $sql .= " AND l.medicine_id = ?"; $p[] = (int)$medicineId; }
        if ($tipe !== '') { $sql .= " AND l.tipe = ?"; $p[] = $tipe; }
        $sql .= " ORDER BY l.created_at DESC, l.id DESC LIMIT " . (int)$limit;
        return $this->db->fetchAll($sql, $p);
    }
}
