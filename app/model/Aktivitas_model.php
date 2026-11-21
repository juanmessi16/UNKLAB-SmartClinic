<?php
/**
 * Model Aktivitas Log - UNKLAB SmartClinic
 */
class Aktivitas_model {

    private $db;

    public function __construct() {
        $this->db = Database::shared();
    }

    public function log($userId, $username, $aktivitas, $detail = null) {
        if (!$this->db->isConnected()) return false;
        $ip = class_exists('Security') ? Security::get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
        try {
            $this->db->exec("INSERT INTO aktivitas_log (user_id, username, aktivitas, detail, ip_address, user_agent) VALUES (?,?,?,?,?,?)",
                [$userId ? (int)$userId : null, $username, $aktivitas, $detail, $ip, $ua]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getRecent($limit = 10, $filter = '') {
        if (!$this->db->isConnected()) return [];
        $limit = max(1, (int)$limit);
        if ($filter !== '') {
            return $this->db->fetchAll("SELECT * FROM aktivitas_log WHERE username LIKE ? OR aktivitas LIKE ? OR detail LIKE ? ORDER BY id DESC LIMIT $limit", ["%$filter%", "%$filter%", "%$filter%"]);
        }
        return $this->db->fetchAll("SELECT * FROM aktivitas_log ORDER BY id DESC LIMIT $limit");
    }
}
