<?php
/**
 * Controller AI Endpoints (AJAX JSON) - UNKLAB SmartClinic
 * Melayani Chatbot UKESMAS, Semantic Search, Symptom Triage, dan Forecasting.
 */
class ai extends ClinicController {

    public function index() {
        $this->json(['success' => true, 'service' => 'UNKLAB SmartClinic Gemini AI Gateway']);
    }

    /** POST /ai/chat - Konsultasi AI 24 Jam berbasis Google Gemini LLM Multi-Turn (publik & semua role) */
    public function chat() {
        $this->start_default_session_app();
        $this->requirePost(true);

        $msg = $this->input('message', '');
        if ($msg === '') {
            $this->json(['success' => false, 'message' => 'Pesan tidak boleh kosong.']);
        }

        // Ambil 4-6 pesan terakhir dari jendela chat saat ini (fallback ke session)
        $history = [];
        $rawHistory = $_POST['history'] ?? '';
        if (is_string($rawHistory) && trim($rawHistory) !== '') {
            $decoded = json_decode($rawHistory, true);
            if (is_array($decoded)) {
                foreach (array_slice($decoded, -6) as $item) {
                    if (!is_array($item)) continue;
                    $role = (($item['role'] ?? '') === 'user') ? 'user' : 'assistant';
                    $text = trim((string)($item['text'] ?? ''));
                    if ($text !== '') {
                        $history[] = ['role' => $role, 'text' => mb_substr($text, 0, 800)];
                    }
                }
            }
        } elseif (!empty($_SESSION['chat_history']) && is_array($_SESSION['chat_history'])) {
            $history = array_slice($_SESSION['chat_history'], -6);
        }

        $gem = new GeminiService();
        $res = $gem->chat($msg, $history);

        if (($res['source'] ?? '') === 'gemini' && !empty($res['reply'])) {
            $history[] = ['role' => 'user', 'text' => $msg];
            $history[] = ['role' => 'assistant', 'text' => $res['reply']];
            $_SESSION['chat_history'] = array_slice($history, -10);
        }

        $this->logAi('Chat', $msg, $res, $res);
        $this->json(['success' => true] + $res);
    }

    /** GET/POST /ai/search?q=... - AI Semantic Search (publik maupun login) */
    public function search() {
        $this->start_default_session_app();
        $q = $this->input('q', '');
        if ($q === '') {
            $this->json(['success' => false, 'message' => 'Masukkan kata kunci atau pertanyaan.']);
        }
        $docs = (new Content_model())->searchDocs(true);
        $gem  = new GeminiService();
        $res  = $gem->search($q, $docs);
        $this->logAi('Search', $q, ['count' => count($res['results'] ?? [])], $res);
        $this->json(['success' => true, 'query' => $q] + $res);
    }

    /** POST /ai/triage - Analisis keluhan gejala dengan AI */
    public function triage() {
        $u = $this->authJson();
        $this->requirePost(true);

        $complaint = $this->input('complaint', '');
        $patientId = $this->intInput('patient_id', (int)$u['patient_id']);
        if (mb_strlen($complaint) < 5) {
            $this->json(['success' => false, 'message' => 'Tuliskan keluhan Anda secara lebih jelas (minimal 5 karakter).']);
        }

        $patModel = new Patient_model();
        $patient  = $patientId ? $patModel->find($patientId) : $patModel->findByUser($u['id']);
        if (!$patient) {
            $patient = ['nama' => $u['nama'], 'tipe' => $u['role'] === 'lecturer' ? 'dosen' : 'mahasiswa'];
        }

        $gem = new GeminiService();
        $res = $gem->triage($complaint, $patient);
        $_SESSION['last_triage'] = [
            'complaint'  => $complaint,
            'patient_id' => $patient['id'] ?? 0,
            'result'     => $res,
            'time'       => time(),
        ];

        $this->logAi('Triage', $complaint, $res, $res);
        $this->json(['success' => true, 'triage' => $res]);
    }

    /** POST /ai/forecast - Prediksi tren penyakit & rekomendasi restock obat */
    public function forecast() {
        $this->authJson(['superadmin', 'clinicadmin', 'medstaff']);
        $this->requirePost(true);

        $stats = new Stats_model();
        $agg   = $stats->forecastAggregate();
        $gem   = new GeminiService();
        $res   = $gem->forecast($agg);

        $this->logAi('Forecast', 'Agregat 14 hari (' . $agg['periode'] . ')', $res, $res);
        $this->logActivity('AI Health Forecasting', 'Menjalankan analisis prediksi penyakit & stok obat (' . ($res['source'] ?? 'ai') . ')');
        $this->json(['success' => true, 'forecast' => $res, 'aggregate' => $agg]);
    }
}
