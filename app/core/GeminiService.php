<?php
/**
 * GeminiService - Integrasi Google Gemini AI (REST + cURL) untuk UNKLAB SmartClinic.
 *
 * Fitur:
 *  1. triage()   : Konsultasi AI Keluhan Awal (JSON terstruktur: kategori, ringkasan_dokter, saran_awal)
 *  2. forecast() : AI Stock & Disease Forecaster (JSON)
 *  3. chat()     : AI UKESMAS Chatbot (kontekstual dari data DB)
 *  4. search()   : AI Semantic Health & Medicine Search
 *
 * Dilengkapi Intelligent Contextual Fallback jika API Key kosong / limit / jaringan gagal.
 */
class GeminiService {

    const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/';

    private $apiKey = '';
    private $model = 'gemini-3.5-flash-lite';
    private $enabled = true;
    public  $lastError = '';
    public  $lastModelUsed = '';
    private $authFailed = false;

    public function __construct() {
        $key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : $this->model;
        try {
            $db = Database::shared();
            if ($db->isConnected() && $db->hasTable('pengaturan')) {
                $rows = $db->fetchAll("SELECT setting_key, setting_value FROM pengaturan WHERE setting_key IN ('gemini_api_key','gemini_model','gemini_enabled')");
                foreach ($rows as $r) {
                    if ($r['setting_key'] === 'gemini_api_key' && trim((string)$r['setting_value']) !== '') $key = trim($r['setting_value']);
                    if ($r['setting_key'] === 'gemini_model' && trim((string)$r['setting_value']) !== '') $model = trim($r['setting_value']);
                    if ($r['setting_key'] === 'gemini_enabled') $this->enabled = ($r['setting_value'] !== '0');
                }
            }
        } catch (Throwable $e) {}
        $this->apiKey = $key;
        $retiredModels = ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-2.5-flash-lite'];
        if (in_array($model, $retiredModels, true) || !preg_match('/^[a-zA-Z0-9\.\-_]+$/', $model)) {
            $model = 'gemini-3.5-flash-lite';
        }
        $this->model = $model;
    }

    public function isConfigured() {
        return $this->enabled && $this->apiKey !== '';
    }

    public function getModel() { return $this->model; }

    public function getStatus() {
        return [
            'configured' => $this->isConfigured(),
            'enabled'    => $this->enabled,
            'model'      => $this->model,
            'key_hint'   => $this->apiKey !== '' ? substr($this->apiKey, 0, 4) . '••••••' . substr($this->apiKey, -3) : '',
        ];
    }

    // =====================================================================
    // CORE: panggilan REST ke Gemini
    // =====================================================================
    private function call($prompt, $system = null, $json = true, $temperature = 0.4) {
        return $this->callContents([
            ['role' => 'user', 'parts' => [['text' => $prompt]]],
        ], $system, $json, $temperature);
    }

    private function callContents(array $contents, $system = null, $json = true, $temperature = 0.4) {
        $this->lastError = '';
        if (!$this->isConfigured()) {
            $this->lastError = $this->apiKey === '' ? 'API Key Gemini belum dikonfigurasi.' : 'Integrasi Gemini dinonaktifkan.';
            return null;
        }
        if ($this->authFailed || !function_exists('curl_init')) {
            return null;
        }

        $body = [
            'contents' => $contents,
            'generationConfig' => ['temperature' => $temperature, 'maxOutputTokens' => 2048],
        ];
        if ($json) $body['generationConfig']['responseMimeType'] = 'application/json';
        if ($system) $body['systemInstruction'] = ['parts' => [['text' => $system]]];
        $payload = json_encode($body, JSON_UNESCAPED_UNICODE);

        $models = array_values(array_unique([$this->model, 'gemini-3.5-flash-lite', 'gemini-flash-lite-latest', 'gemini-3.5-flash']));
        $attempts = 0;
        foreach ($models as $m) {
            if (++$attempts > 4) break;
            $ch = curl_init(self::ENDPOINT . rawurlencode($m) . ':generateContent');
            $opts = [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => defined('GEMINI_TIMEOUT') ? GEMINI_TIMEOUT : 25,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $this->apiKey],
                CURLOPT_SSL_VERIFYPEER => defined('GEMINI_SSL_VERIFY') ? (bool)GEMINI_SSL_VERIFY : true,
                CURLOPT_SSL_VERIFYHOST => (defined('GEMINI_SSL_VERIFY') && !GEMINI_SSL_VERIFY) ? 0 : 2,
            ];
            if (!ini_get('curl.cainfo')) {
                foreach (['C:\\xampp\\apache\\bin\\curl-ca-bundle.crt', 'C:\\xampp\\php\\extras\\ssl\\cacert.pem'] as $ca) {
                    if (file_exists($ca)) { $opts[CURLOPT_CAINFO] = $ca; break; }
                }
            }
            curl_setopt_array($ch, $opts);
            $resp = curl_exec($ch);
            $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);

            // Auto-recovery jika bundle sertifikat SSL lokal kadaluwarsa / bermasalah pada lingkungan localhost (XAMPP Windows)
            if ($resp === false && (stripos($cerr, 'certificate') !== false || stripos($cerr, 'SSL') !== false)) {
                $opts[CURLOPT_SSL_VERIFYPEER] = false;
                $opts[CURLOPT_SSL_VERIFYHOST] = 0;
                $chRetry = curl_init(self::ENDPOINT . rawurlencode($m) . ':generateContent');
                curl_setopt_array($chRetry, $opts);
                $resp = curl_exec($chRetry);
                $http = (int)curl_getinfo($chRetry, CURLINFO_HTTP_CODE);
                $cerr = curl_error($chRetry);
                curl_close($chRetry);
            }

            if ($resp === false) {
                $this->lastError = 'Koneksi ke Gemini gagal: ' . $cerr;
                return null;
            }
            $data = json_decode($resp, true);
            if ($http === 200 && isset($data['candidates'][0]['content']['parts'])) {
                $text = '';
                foreach ($data['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['text'])) $text .= $part['text'];
                }
                if (trim($text) !== '') {
                    $this->lastModelUsed = $m;
                    return $text;
                }
                $this->lastError = 'Respons Gemini kosong / diblokir filter keamanan.';
                return null;
            }
            $msg = $data['error']['message'] ?? ('HTTP ' . $http);
            $this->lastError = 'Gemini API (' . $m . '): ' . $msg;
            if ($http === 400 || $http === 401 || $http === 403) {
                if ($http !== 400 || stripos($msg, 'API key') !== false) $this->authFailed = true;
                if ($http !== 400) return null;
                if (stripos($msg, 'API key') !== false) return null;
            }
        }
        return null;
    }

    private function callJson($prompt, $system = null, $temperature = 0.3) {
        $text = $this->call($prompt, $system, true, $temperature);
        if ($text === null) return null;
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);
        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            if (preg_match('/\{.*\}/s', $text, $mm)) $decoded = json_decode($mm[0], true);
        }
        if (!is_array($decoded)) {
            $this->lastError = 'Respons Gemini bukan JSON valid.';
            return null;
        }
        return $decoded;
    }

    // =====================================================================
    // 1. KONSULTASI AI KELUHAN AWAL (tanpa warna triase / tanpa asrama)
    // =====================================================================
    public function triage($complaint, $patient = []) {
        $ctx = 'Nama: ' . ($patient['nama'] ?? '-')
            . '; Status: ' . ($patient['tipe'] ?? 'mahasiswa')
            . '; Fakultas: ' . ($patient['fakultas'] ?? '-')
            . (!empty($patient['prodi']) ? ' / ' . $patient['prodi'] : '')
            . '; Riwayat alergi: ' . ($patient['riwayat_alergi'] ?? 'tidak ada')
            . '; Penyakit kronis: ' . ($patient['penyakit_kronis'] ?? 'tidak ada');

        $system = 'Anda adalah asisten konsultasi kesehatan awal UKESMAS Universitas Klabat. '
            . 'Analisis keluhan pasien dalam bahasa Indonesia secara ringkas, empatik, dan informatif. '
            . 'JANGAN membuat kategori warna triase (Hijau/Kuning/Merah) dan JANGAN memberi diagnosis pasti. '
            . 'Fokuskan jawaban pada: (1) Estimasi kategori keluhan, (2) Ringkasan keluhan terstruktur untuk dokter, dan (3) Saran penanganan/istirahat awal yang aman sebelum diperiksa di klinik. '
            . 'Balas HANYA JSON valid sesuai skema.';

        $prompt = "DATA PASIEN: $ctx\nKELUHAN: \"$complaint\"\n\n"
            . "Balas dengan JSON:\n"
            . "{\"kategori\":\"salah satu: ISPA, Demam, Pencernaan / Gastritis, Diare, Sakit Kepala, Cedera Ringan, Alergi / Kulit, Keluhan Umum\","
            . "\"confidence\":0-100,"
            . "\"ringkasan_dokter\":\"ringkasan anamnesis klinis terstruktur 2-3 kalimat untuk dokter UKESMAS\","
            . "\"saran_awal\":[\"saran penanganan/istirahat awal 1\",\"saran 2\",\"saran 3\",\"saran 4\"],"
            . "\"catatan\":\"catatan singkat sebelum pemeriksaan di klinik UKESMAS\"}";

        $r = $this->callJson($prompt, $system, 0.25);
        if ($r !== null) {
            $out = $this->normalizeConsultation($r, $complaint);
            $out['source'] = 'gemini';
            $out['model'] = $this->lastModelUsed;
            return $out;
        }
        $out = $this->ruleConsult($complaint, $patient);
        $out['source'] = 'fallback';
        $out['model'] = 'clinical-assistant';
        $out['fallback_reason'] = $this->lastError;
        return $out;
    }

    private function normalizeConsultation($r, $complaint) {
        $saran = $r['saran_awal'] ?? ($r['first_aid'] ?? []);
        if (!is_array($saran)) $saran = [(string)$saran];
        $ringkasan = (string)($r['ringkasan_dokter'] ?? ($r['anamnesis_summary'] ?? ('Pasien mengeluhkan: ' . $complaint)));
        $catatan = (string)($r['catatan'] ?? ($r['rekomendasi_tindakan'] ?? 'Silakan ambil nomor antrean UKESMAS untuk pemeriksaan langsung oleh dokter.'));
        return [
            'kategori'          => (string)($r['kategori'] ?? 'Keluhan Umum'),
            'confidence'        => max(0, min(100, (int)($r['confidence'] ?? 82))),
            'ringkasan_dokter'  => $ringkasan,
            'anamnesis_summary' => $ringkasan,
            'saran_awal'        => array_values(array_map('strval', $saran)),
            'first_aid'         => array_values(array_map('strval', $saran)),
            'catatan'           => $catatan,
        ];
    }

    private function ruleConsult($text, $patient = []) {
        $t = mb_strtolower($text);
        $cats = [
            'ISPA'                    => ['batuk', 'pilek', 'flu', 'bersin', 'tenggorokan', 'hidung', 'meler', 'radang', 'serak', 'ingus'],
            'Demam'                   => ['demam', 'panas', 'meriang', 'menggigil', 'suhu', 'badan panas'],
            'Pencernaan / Gastritis'  => ['maag', 'ulu hati', 'perih', 'lambung', 'asam lambung', 'kembung', 'mual', 'perut', 'sendawa', 'telat makan'],
            'Diare'                   => ['diare', 'mencret', 'bab cair', 'bab encer', 'mules'],
            'Sakit Kepala'            => ['sakit kepala', 'pusing', 'migrain', 'kepala', 'cenat'],
            'Cedera Ringan'           => ['luka', 'lecet', 'jatuh', 'terkilir', 'keseleo', 'memar', 'sayat', 'berdarah', 'tersayat', 'bengkak'],
            'Alergi / Kulit'          => ['gatal', 'ruam', 'biduran', 'bentol', 'kulit', 'alergi', 'kemerahan'],
            'Pernapasan / Dada'       => ['sesak', 'nyeri dada', 'napas', 'mengi', 'asma'],
        ];
        $scores = [];
        foreach ($cats as $c => $kws) {
            $s = 0;
            foreach ($kws as $k) if (mb_strpos($t, $k) !== false) $s++;
            $scores[$c] = $s;
        }
        arsort($scores);
        $kategori = key($scores);
        $top = current($scores);
        if ($top === 0) $kategori = 'Keluhan Umum';

        $saran = $this->initialAdviceFor($kategori);
        $nama = $patient['nama'] ?? 'Pasien';
        $fak  = !empty($patient['fakultas']) ? ' (' . $patient['fakultas'] . ')' : '';
        $alergiNote = (!empty($patient['riwayat_alergi']) && mb_strtolower($patient['riwayat_alergi']) !== 'tidak ada')
            ? ' Riwayat alergi tercatat: ' . $patient['riwayat_alergi'] . '.'
            : '';
        $ringkasan = "{$nama}{$fak} melaporkan keluhan: \"" . trim($text) . "\". "
            . "Estimasi kategori keluhan: {$kategori}.{$alergiNote} "
            . "Disarankan pemeriksaan tanda vital dan evaluasi klinis lanjutan oleh dokter UKESMAS.";

        return [
            'kategori'          => $kategori,
            'confidence'        => $top >= 2 ? 88 : ($top === 1 ? 76 : 65),
            'ringkasan_dokter'  => $ringkasan,
            'anamnesis_summary' => $ringkasan,
            'saran_awal'        => $saran,
            'first_aid'         => $saran,
            'catatan'           => 'Saran di atas bersifat penanganan awal. Silakan ambil nomor antrean untuk pemeriksaan langsung oleh dokter UKESMAS.',
        ];
    }

    private function initialAdviceFor($kat) {
        $map = [
            'ISPA'                   => ['Istirahat cukup dan kurangi aktivitas berat sementara waktu', 'Minum air hangat minimal 2 liter per hari untuk melegakan tenggorokan', 'Gunakan masker saat berinteraksi agar tidak menularkan kepada orang lain', 'Berkumur air garam hangat bila tenggorokan terasa nyeri'],
            'Demam'                  => ['Kompres hangat pada dahi, ketiak, dan lipat paha', 'Perbanyak minum air putih agar tubuh tidak dehidrasi', 'Kenakan pakaian yang nyaman dan istirahat yang cukup', 'Pantau suhu tubuh secara berkala sebelum pemeriksaan dokter'],
            'Pencernaan / Gastritis' => ['Makan dalam porsi kecil namun teratur, hindari perut kosong terlalu lama', 'Hindari kopi, minuman bersoda, makanan pedas, dan makanan asam', 'Hindari langsung berbaring setelah makan (tunggu 1-2 jam)', 'Minum air hangat secara perlahan untuk meredakan rasa tidak nyaman'],
            'Diare'                  => ['Minum oralit atau air putih sedikit demi sedikit untuk mencegah dehidrasi', 'Konsumsi makanan lunak dan mudah dicerna', 'Hindari makanan berminyak, pedas, dan produk susu sementara waktu', 'Cuci tangan dengan sabun secara rutin'],
            'Sakit Kepala'           => ['Beristirahat sejenak di ruangan yang tenang dan pencahayaan redup', 'Kompres dingin pada dahi atau tengkuk selama 15 menit', 'Cukupi kebutuhan cairan dan kurangi paparan layar gawai/laptop', 'Atur waktu tidur 7-8 jam agar pemulihan optimal'],
            'Cedera Ringan'          => ['Bersihkan luka dengan air mengalir dan sabun lembut', 'Tekan perlahan dengan kasa bersih bila ada perdarahan ringan', 'Oleskan antiseptik dan tutup luka dengan plester/kasa steril', 'Untuk terkilir: terapkan prinsip RICE (Istirahat, Kompres Es, Balut, Tinggikan)'],
            'Alergi / Kulit'         => ['Hindari pemicu alergi yang dicurigai (makanan, debu, atau produk tertentu)', 'Hindari menggaruk area kulit yang gatal agar tidak iritasi', 'Kompres dingin pada area ruam untuk mengurangi rasa gatal', 'Gunakan sabun lembut tanpa pewangi saat mandi'],
            'Pernapasan / Dada'      => ['Hentikan aktivitas segera dan duduk dalam posisi tegak yang nyaman', 'Longgarkan pakaian di area leher dan dada, atur napas perlahan', 'Hindari asap, debu, atau ruangan pengap', 'Segera menuju klinik UKESMAS atau fasilitas IGD terdekat bila keluhan memberat'],
        ];
        return $map[$kat] ?? [
            'Beristirahat cukup dan perbanyak minum air putih',
            'Hindari pemicu yang dapat memperberat keluhan',
            'Catat waktu munculnya gejala untuk disampaikan kepada dokter',
            'Ambil nomor antrean UKESMAS untuk pemeriksaan medis lebih lanjut',
        ];
    }

    // =====================================================================
    // 2. AI FORECASTER (penyakit & stok obat)
    // =====================================================================
    public function forecast($agg) {
        $system = 'Anda adalah analis kesehatan masyarakat kampus & apoteker klinik UKESMAS Universitas Klabat. '
            . 'Gunakan data agregat 14 hari untuk memprediksi tren penyakit minggu depan dan rekomendasi restock obat. Balas HANYA JSON valid.';
        $prompt = "DATA AGREGAT 14 HARI (JSON):\n" . json_encode($agg, JSON_UNESCAPED_UNICODE)
            . "\n\nKonteks: lingkungan kampus Universitas Klabat pada periode perkuliahan/ujian. Skema JSON balasan:\n"
            . '{"ringkasan":"insight utama 2-3 kalimat",'
            . '"prediksi_penyakit":[{"penyakit":"","tren":"Naik|Stabil|Turun","kasus_minggu_ini":0,"perkiraan_kasus_minggu_depan":0,"risiko":"Rendah|Sedang|Tinggi","alasan":""}],'
            . '"rekomendasi_restock":[{"obat_id":0,"obat":"","stok_saat_ini":0,"perkiraan_kebutuhan_7_hari":0,"rekomendasi_beli":0,"prioritas":"Tinggi|Sedang|Rendah","alasan":""}],'
            . '"peringatan":["..."],"saran_operasional":["..."]}'
            . "\nUrutkan restock berdasarkan prioritas; sertakan hanya obat dari data yang diberikan dan gunakan obat_id yang sama.";
        $r = $this->callJson($prompt, $system, 0.3);
        if ($r !== null && isset($r['prediksi_penyakit']) && is_array($r['prediksi_penyakit'])) {
            $r['source'] = 'gemini';
            $r['model'] = $this->lastModelUsed;
            $r['rekomendasi_restock'] = $this->normalizeRestock($r['rekomendasi_restock'] ?? [], $agg);
            $r['peringatan'] = isset($r['peringatan']) && is_array($r['peringatan']) ? $r['peringatan'] : [];
            $r['saran_operasional'] = isset($r['saran_operasional']) && is_array($r['saran_operasional']) ? $r['saran_operasional'] : [];
            return $r;
        }
        $out = $this->ruleForecast($agg);
        $out['source'] = 'fallback';
        $out['model'] = 'statistical-engine';
        $out['fallback_reason'] = $this->lastError;
        return $out;
    }

    private function normalizeRestock($list, $agg) {
        $byId = [];
        foreach (($agg['obat'] ?? []) as $m) $byId[(int)$m['id']] = $m;
        $out = [];
        foreach ((array)$list as $it) {
            if (!is_array($it)) continue;
            $id = (int)($it['obat_id'] ?? 0);
            if (!isset($byId[$id])) {
                foreach ($byId as $mid => $m) if (isset($it['obat']) && mb_strtolower($m['nama']) === mb_strtolower((string)$it['obat'])) { $id = $mid; break; }
            }
            if (!isset($byId[$id])) continue;
            $m = $byId[$id];
            $out[] = [
                'obat_id' => $id,
                'obat' => $m['nama'],
                'satuan' => $m['satuan'],
                'stok_saat_ini' => (int)$m['stok'],
                'stok_minimum' => (int)$m['stok_minimum'],
                'perkiraan_kebutuhan_7_hari' => (int)($it['perkiraan_kebutuhan_7_hari'] ?? 0),
                'rekomendasi_beli' => max(0, (int)($it['rekomendasi_beli'] ?? 0)),
                'prioritas' => in_array($it['prioritas'] ?? '', ['Tinggi', 'Sedang', 'Rendah']) ? $it['prioritas'] : 'Sedang',
                'alasan' => (string)($it['alasan'] ?? ''),
            ];
        }
        return $out;
    }

    private function ruleForecast($agg) {
        $cats = $agg['kasus_per_kategori'] ?? [];
        $pred = [];
        $totLast = 0; $totPrev = 0;
        foreach ($cats as $name => $c) {
            $last = (int)$c['minggu_ini']; $prev = (int)$c['minggu_lalu'];
            $totLast += $last; $totPrev += $prev;
            $growth = ($last - $prev) / max($prev, 1);
            $adj = max(-0.3, min(0.45, $growth * 0.6));
            $next = (int)round($last * (1 + $adj));
            if ($name === 'Gastritis' || $name === 'ISPA') $next = (int)round($next * 1.05);
            $trend = $growth > 0.15 ? 'Naik' : ($growth < -0.15 ? 'Turun' : 'Stabil');
            $risk = ($next >= 14 || $growth >= 0.5) ? 'Tinggi' : (($next >= 7 || $growth >= 0.2) ? 'Sedang' : 'Rendah');
            $reason = "Minggu ini $last kasus vs $prev kasus minggu lalu (" . ($growth >= 0 ? '+' : '') . round($growth * 100) . "%).";
            if ($name === 'ISPA') $reason .= ' Aktivitas perkuliahan padat dan perubahan cuaca mempercepat penyebaran.';
            if ($name === 'Gastritis') $reason .= ' Pola makan tidak teratur dan beban akademik saat ujian meningkatkan kasus maag.';
            if ($name === 'Demam') $reason .= ' Sering menyertai ISPA dan kelelahan fisik.';
            $pred[] = ['penyakit' => $name, 'tren' => $trend, 'kasus_minggu_ini' => $last, 'perkiraan_kasus_minggu_depan' => $next, 'risiko' => $risk, 'alasan' => $reason];
        }
        usort($pred, function ($a, $b) { return $b['perkiraan_kasus_minggu_depan'] <=> $a['perkiraan_kasus_minggu_depan']; });

        $overall = ($totLast - $totPrev) / max($totPrev, 1);
        $factor = 1 + max(0, min(0.5, $overall));
        $restock = [];
        foreach (($agg['obat'] ?? []) as $m) {
            $avg = $m['dipakai_14_hari'] / 14;
            $recent = $m['dipakai_7_hari'] / 7;
            $daily = max($avg, $recent) * $factor;
            $need7 = (int)ceil($daily * 7);
            $target = $need7 + (int)$m['stok_minimum'];
            $buy = max(0, $target - (int)$m['stok']);
            $buy = $buy > 0 ? (int)(ceil($buy / 10) * 10) : 0;
            $daysLeft = $daily > 0 ? $m['stok'] / $daily : 999;
            $prio = ($m['stok'] <= $m['stok_minimum'] || $daysLeft < 5) ? 'Tinggi' : ($daysLeft < 10 ? 'Sedang' : 'Rendah');
            if ($buy <= 0 && $prio === 'Rendah') continue;
            $reason = 'Stok ' . $m['stok'] . ' ' . $m['satuan'] . ' (min ' . $m['stok_minimum'] . '), pemakaian rata-rata ' . round($daily, 1) . '/hari'
                . ($daysLeft < 999 ? ', cukup ± ' . floor($daysLeft) . ' hari.' : '.');
            if (!empty($m['kedaluwarsa_hari']) && $m['kedaluwarsa_hari'] < 0) $reason .= ' Batch kedaluwarsa - ganti dengan stok baru.';
            $restock[] = ['obat_id' => (int)$m['id'], 'obat' => $m['nama'], 'satuan' => $m['satuan'], 'stok_saat_ini' => (int)$m['stok'], 'stok_minimum' => (int)$m['stok_minimum'],
                'perkiraan_kebutuhan_7_hari' => $need7, 'rekomendasi_beli' => $buy, 'prioritas' => $prio, 'alasan' => $reason, '_days' => $daysLeft];
        }
        $po = ['Tinggi' => 0, 'Sedang' => 1, 'Rendah' => 2];
        usort($restock, function ($a, $b) use ($po) { return [$po[$a['prioritas']], $a['_days']] <=> [$po[$b['prioritas']], $b['_days']]; });
        foreach ($restock as &$r) unset($r['_days']);
        unset($r);

        $warn = [];
        foreach (($agg['obat'] ?? []) as $m) {
            if ($m['stok'] <= $m['stok_minimum']) $warn[] = 'Stok kritis: ' . $m['nama'] . ' (' . $m['stok'] . ' ' . $m['satuan'] . ', minimum ' . $m['stok_minimum'] . ').';
            if (isset($m['kedaluwarsa_hari']) && $m['kedaluwarsa_hari'] !== null) {
                if ($m['kedaluwarsa_hari'] < 0) $warn[] = 'KEDALUWARSA: ' . $m['nama'] . ' sudah melewati tanggal kedaluwarsa - tarik dari rak.';
                elseif ($m['kedaluwarsa_hari'] <= 45) $warn[] = 'Segera kedaluwarsa: ' . $m['nama'] . ' (' . $m['kedaluwarsa_hari'] . ' hari lagi).';
            }
        }
        $top = $pred[0] ?? null;
        $rising = array_filter($pred, function ($p) { return $p['tren'] === 'Naik'; });
        $names = implode(', ', array_map(function ($p) { return $p['penyakit']; }, array_slice($rising, 0, 3)));
        $summary = 'Total kunjungan minggu ini ' . $totLast . ' kasus (' . ($overall >= 0 ? '+' : '') . round($overall * 100) . '% dibanding minggu lalu). '
            . ($names ? "Penyakit dengan tren naik: $names. " : 'Tren penyakit relatif stabil. ')
            . ($top ? 'Perkiraan kasus terbanyak minggu depan: ' . $top['penyakit'] . ' (±' . $top['perkiraan_kasus_minggu_depan'] . ' kasus). ' : '')
            . 'Prioritaskan restock ' . count(array_filter($restock, function ($r) { return $r['prioritas'] === 'Tinggi'; })) . ' obat berprioritas tinggi.';
        return [
            'ringkasan' => $summary,
            'prediksi_penyakit' => $pred,
            'rekomendasi_restock' => $restock,
            'peringatan' => array_slice($warn, 0, 8),
            'saran_operasional' => [
                'Siapkan sosialisasi pencegahan ISPA & edukasi pola makan teratur di lingkungan kampus pada pekan ujian.',
                'Tambah jam konsultasi perawat jaga pada hari dengan kunjungan terpadat.',
                'Lakukan stok opname mingguan untuk obat berprioritas tinggi.',
            ],
        ];
    }

    // =====================================================================
    // 2B. AI CLINICAL ASSISTANT — DRAFT SOAP OTOMATIS (EMR)
    // =====================================================================
    public function generateSoapDraft($input) {
        $keluhan   = trim((string)($input['keluhan_utama'] ?? ''));
        $ringkasan = trim((string)($input['ringkasan_awal'] ?? ''));
        $kronis    = trim((string)($input['penyakit_kronis'] ?? ''));
        $sistolik  = (int)($input['tekanan_sistolik'] ?? 120);
        $diastolik = (int)($input['tekanan_diastolik'] ?? 80);
        $suhu      = (float)($input['suhu'] ?? 36.8);
        $nadi      = (int)($input['detak_jantung'] ?? 80);
        $rr        = (int)($input['respirasi'] ?? 18);
        $spo2      = (int)($input['saturasi_o2'] ?? 98);
        $bb        = (float)($input['berat_badan'] ?? 60);

        $system = 'Anda adalah AI Clinical Assistant untuk dokter dan perawat di UKESMAS Universitas Klabat. '
            . 'Susun draf catatan rekam medis elektronik (EMR) dalam format SOAP (Subjective, Objective, Assessment, Plan) '
            . 'menggunakan bahasa Indonesia medis yang ringkas, jelas, dan profesional. '
            . 'Assessment bersifat estimasi diagnosis kerja sementara untuk ditinjau dokter. '
            . 'JANGAN sertakan data golongan darah atau riwayat alergi. '
            . 'Balas HANYA JSON valid sesuai skema.';

        $prompt = "DATA PEMERIKSAAN PASIEN:\n"
            . "- Keluhan Utama: \"{$keluhan}\"\n"
            . ($ringkasan !== '' ? "- Ringkasan Triase Awal: \"{$ringkasan}\"\n" : '')
            . ($kronis !== '' ? "- Riwayat Penyakit Kronis/Komorbid: {$kronis}\n" : '')
            . "- Tanda Vital:\n"
            . "  * Tekanan Darah: {$sistolik}/{$diastolik} mmHg\n"
            . "  * Suhu Tubuh: {$suhu} °C\n"
            . "  * Detak Jantung (Nadi): {$nadi} bpm\n"
            . "  * Respirasi (RR): {$rr} x/menit\n"
            . "  * Saturasi O2 (SpO2): {$spo2}%\n"
            . "  * Berat Badan: {$bb} kg\n\n"
            . "Balas HANYA dengan JSON valid berikut:\n"
            . '{"subjective":"Ringkasan keluhan utama pasien secara klinis (1-2 kalimat)",'
            . '"objective":"Ringkasan terstruktur tanda vital yang diinput dokter beserta interpretasi singkat (mis. normotensi/febris/takikardia)",'
            . '"assessment":"Estimasi diagnosis kerja dan kategori klinis sementara (1-2 kalimat)",'
            . '"plan":"Saran rencana penanganan, edukasi istirahat, hidrasi, dan opsi terapi awal (2-3 kalimat ringkas)",'
            . '"diagnosis_singkat":"Nama diagnosis kerja singkat (mis. Infeksi Saluran Pernapasan Akut (ISPA))",'
            . '"kode_icd":"Kode ICD-10 yang paling relevan (mis. J06.9)",'
            . '"kategori_penyakit":"Salah satu persis: ISPA, Demam, Gastritis, Diare, Sakit Kepala, Cedera, Dermatitis, Lainnya"}';

        $r = $this->callJson($prompt, $system, 0.25);
        if ($r !== null && !empty($r['subjective']) && !empty($r['assessment'])) {
            $out = $this->normalizeSoapDraft($r, $input);
            $out['source'] = 'gemini';
            $out['model']  = $this->lastModelUsed;
            return $out;
        }

        $out = $this->ruleSoapDraft($input);
        $out['source'] = 'fallback';
        $out['model']  = 'clinical-soap-engine';
        $out['fallback_reason'] = $this->lastError;
        return $out;
    }

    private function normalizeSoapDraft($r, $input) {
        $allowedCats = ['ISPA', 'Demam', 'Gastritis', 'Diare', 'Sakit Kepala', 'Cedera', 'Dermatitis', 'Lainnya'];
        $cat = trim((string)($r['kategori_penyakit'] ?? 'Lainnya'));
        if (!in_array($cat, $allowedCats, true)) {
            $lc = mb_strtolower($cat);
            if (mb_strpos($lc, 'ispa') !== false || mb_strpos($lc, 'napas') !== false) $cat = 'ISPA';
            elseif (mb_strpos($lc, 'demam') !== false || mb_strpos($lc, 'febris') !== false) $cat = 'Demam';
            elseif (mb_strpos($lc, 'gastr') !== false || mb_strpos($lc, 'maag') !== false || mb_strpos($lc, 'cerna') !== false) $cat = 'Gastritis';
            elseif (mb_strpos($lc, 'diare') !== false) $cat = 'Diare';
            elseif (mb_strpos($lc, 'kepala') !== false || mb_strpos($lc, 'cephal') !== false) $cat = 'Sakit Kepala';
            elseif (mb_strpos($lc, 'cedera') !== false || mb_strpos($lc, 'luka') !== false) $cat = 'Cedera';
            elseif (mb_strpos($lc, 'dermat') !== false || mb_strpos($lc, 'kulit') !== false) $cat = 'Dermatitis';
            else $cat = 'Lainnya';
        }

        $s = trim((string)($r['subjective'] ?? ''));
        $o = trim((string)($r['objective'] ?? ''));
        $a = trim((string)($r['assessment'] ?? ''));
        $p = trim((string)($r['plan'] ?? ''));

        $soapText = "S (Subjective): {$s}\n"
            . "O (Objective): {$o}\n"
            . "A (Assessment): {$a}\n"
            . "P (Plan): {$p}";

        return [
            'subjective'        => $s,
            'objective'         => $o,
            'assessment'        => $a,
            'plan'              => $p,
            'soap_text'         => $soapText,
            'diagnosis_singkat' => trim((string)($r['diagnosis_singkat'] ?? $a)),
            'kode_icd'          => trim((string)($r['kode_icd'] ?? '')),
            'kategori_penyakit' => $cat,
        ];
    }

    private function ruleSoapDraft($input) {
        $keluhan   = trim((string)($input['keluhan_utama'] ?? 'Keluhan umum'));
        $sistolik  = (int)($input['tekanan_sistolik'] ?? 120);
        $diastolik = (int)($input['tekanan_diastolik'] ?? 80);
        $suhu      = (float)($input['suhu'] ?? 36.8);
        $nadi      = (int)($input['detak_jantung'] ?? 80);
        $rr        = (int)($input['respirasi'] ?? 18);
        $spo2      = (int)($input['saturasi_o2'] ?? 98);
        $bb        = (float)($input['berat_badan'] ?? 60);

        $t = mb_strtolower($keluhan);
        $flags = [];
        if ($suhu >= 38.0) $flags[] = 'febris';
        elseif ($suhu >= 37.5) $flags[] = 'subfebris';
        else $flags[] = 'afebris';

        if ($sistolik >= 140 || $diastolik >= 90) $flags[] = 'tekanan darah meningkat';
        elseif ($sistolik < 90 || $diastolik < 60) $flags[] = 'hipotensi';
        else $flags[] = 'normotensi';

        if ($nadi > 100) $flags[] = 'takikardia';
        if ($spo2 < 95) $flags[] = 'desaturasi O2 (perlu evaluasi segera)';

        $s = "Pasien datang ke UKESMAS dengan keluhan utama: {$keluhan}.";
        $o = "TD {$sistolik}/{$diastolik} mmHg, Suhu " . number_format($suhu, 1) . "°C, Nadi {$nadi} bpm, RR {$rr}x/menit, SpO2 {$spo2}%, BB " . number_format($bb, 1) . " kg (" . implode(', ', $flags) . ").";

        // Deteksi kategori klinis & ICD-10
        $diag = 'Observasi Keluhan Klinis Umum';
        $icd  = 'R69';
        $cat  = 'Lainnya';
        $plan = 'Edukasi istirahat cukup, hidrasi optimal, terapi simptomatis sesuai indikasi klinis, dan kontrol ulang bila keluhan menetap >3 hari.';

        if (preg_match('/batuk|pilek|flu|tenggorokan|bersin|serak|radang/u', $t)) {
            $diag = 'Infeksi Saluran Pernapasan Akut (ISPA)';
            $icd  = 'J06.9';
            $cat  = 'ISPA';
            $plan = 'Edukasi istirahat cukup, perbanyak minum air hangat, gunakan masker saat beraktivitas, serta pertimbangkan terapi simptomatis (antipiretik/ekspektoran/dekongestan sesuai indikasi). Kontrol bila gejala >3 hari atau muncul sesak.';
        } elseif (preg_match('/maag|ulu hati|lambung|perih|kembung|mual|asam lambung/u', $t)) {
            $diag = 'Gastritis Akut / Dispepsia Fungsional';
            $icd  = 'K29.7';
            $cat  = 'Gastritis';
            $plan = 'Edukasi pola makan teratur porsi kecil, hindari kopi/pedas/asam dan tidak langsung berbaring setelah makan. Pertimbangkan pemberian antasida atau PPI sesuai klinis dokter.';
        } elseif (preg_match('/diare|mencret|bab cair|bab encer|mules/u', $t)) {
            $diag = 'Gastroenteritis Akut / Diare Non-Spesifik';
            $icd  = 'A09';
            $cat  = 'Diare';
            $plan = 'Rehidrasi oral (oralit/cairan cukup), diet lunak rendah serat sementara, edukasi higienitas tangan, serta pertimbangkan terapi suportif saluran cerna. Evaluasi tanda dehidrasi.';
        } elseif (preg_match('/sakit kepala|pusing|migrain|kepala/u', $t)) {
            $diag = 'Cephalgia / Tension-Type Headache';
            $icd  = 'R51';
            $cat  = 'Sakit Kepala';
            $plan = 'Edukasi istirahat cukup di ruangan tenang, kurangi paparan layar, hidrasi baik, dan pertimbangkan analgesik ringan sesuai klinis dokter.';
        } elseif (preg_match('/luka|lecet|jatuh|terkilir|keseleo|memar|bengkak|sayat/u', $t)) {
            $diag = 'Cedera Jaringan Lunak / Vulnus Ringan';
            $icd  = 'T14.1';
            $cat  = 'Cedera';
            $plan = 'Perawatan luka aseptik/antiseptik atau metode RICE (Rest, Ice, Compression, Elevation) untuk terkilir, edukasi jaga kebersihan luka, dan analgesik bila nyeri.';
        } elseif (preg_match('/gatal|ruam|biduran|bentol|kulit|dermatitis/u', $t)) {
            $diag = 'Dermatitis Kontak / Urtikaria Ringan';
            $icd  = 'L30.9';
            $cat  = 'Dermatitis';
            $plan = 'Edukasi hindari faktor pencetus dan tidak menggaruk area ruam, kompres dingin bila gatal, serta pertimbangkan antihistamin oral atau salep topikal sesuai indikasi.';
        } elseif (preg_match('/demam|panas|meriang|menggigil/u', $t) || $suhu >= 37.8) {
            $diag = 'Observasi Febris Akut';
            $icd  = 'R50.9';
            $cat  = 'Demam';
            $plan = 'Edukasi tirah baring (istirahat cukup), kompres hangat, tingkatkan asupan cairan, serta pertimbangkan antipiretik sesuai dosis. Evaluasi ulang bila demam >3 hari.';
        }

        $a = "Suspek {$diag} (ICD-10: {$icd}; Kategori: {$cat}).";

        return $this->normalizeSoapDraft([
            'subjective'        => $s,
            'objective'         => $o,
            'assessment'        => $a,
            'plan'              => $plan,
            'diagnosis_singkat' => $diag,
            'kode_icd'          => $icd,
            'kategori_penyakit' => $cat,
        ], $input);
    }

    // =====================================================================
    // 3. KONSULTASI AI 24 JAM (PURE MULTI-TURN LLM - GOOGLE GEMINI API)
    // =====================================================================
    public function chat($message, $history = [], $context = []) {
        $system = 'Anda adalah "Konsultasi AI 24 Jam", asisten konsultasi kesehatan berbasis AI yang ramah, empatik, informatif, dan profesional dalam Bahasa Indonesia untuk sivitas akademika dan pengguna UNKLAB SmartClinic. '
            . 'PANDUAN JAWABAN: '
            . '1) Jawab segala pertanyaan seputar keluhan medis, gejala penyakit, pertolongan pertama (P3K), pola hidup sehat, nutrisi, istirahat, kesehatan mental ringan, dan edukasi kesehatan secara jelas, terstruktur (gunakan poin singkat bila membantu), dan mudah dipahami. '
            . '2) Pahami konteks percakapan sebelumnya untuk menjawab pertanyaan lanjutan secara natural dan berkesinambungan. '
            . '3) Sertakan pengingat medis singkat yang wajar untuk memeriksakan diri langsung ke dokter atau UKESMAS apabila gejala berat, memburuk, atau tidak kunjung membaik, serta arahkan segera ke IGD bila ada tanda kegawatdaruratan (nyeri dada hebat, sesak berat, pingsan, kejang, perdarahan hebat). '
            . '4) Jika pengguna menanyakan topik yang sama sekali di luar kesehatan atau medis (seperti politik, koding, matematika, hiburan umum), tolak dengan sopan dan arahkan kembali ke topik konsultasi kesehatan. '
            . '5) Balas HANYA JSON valid dengan format: {"reply":"jawaban lengkap dalam bahasa Indonesia yang rapi","suggestions":["pertanyaan lanjutan kesehatan 1","pertanyaan lanjutan kesehatan 2","pertanyaan lanjutan kesehatan 3"]}';

        // Bangun array multi-turn native Gemini (4-6 pesan terakhir + pesan saat ini)
        $contents = [];
        foreach (array_slice((array)$history, -6) as $h) {
            if (!is_array($h)) continue;
            $role = (($h['role'] ?? '') === 'user') ? 'user' : 'model';
            $txt  = trim((string)($h['text'] ?? ''));
            if ($txt !== '') {
                $contents[] = ['role' => $role, 'parts' => [['text' => mb_substr($txt, 0, 800)]]];
            }
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => trim((string)$message)]]];

        $raw = $this->callContents($contents, $system, true, 0.55);
        if ($raw !== null) {
            $clean = trim($raw);
            $clean = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $clean);
            $decoded = json_decode($clean, true);
            if (!is_array($decoded) && preg_match('/\{.*\}/s', $clean, $mm)) {
                $decoded = json_decode($mm[0], true);
            }
            if (is_array($decoded) && !empty($decoded['reply'])) {
                return [
                    'reply'       => (string)$decoded['reply'],
                    'suggestions' => array_slice(array_map('strval', (array)($decoded['suggestions'] ?? [])), 0, 3),
                    'source'      => 'gemini',
                    'model'       => $this->lastModelUsed,
                ];
            }
            // Bila Gemini mengembalikan teks polos yang valid, tetap tampilkan
            if ($clean !== '') {
                return [
                    'reply'       => $clean,
                    'suggestions' => [
                        'Cara meredakan demam dan pusing?',
                        'Pertolongan pertama saat maag kambuh',
                        'Tips menjaga daya tahan tubuh saat begadang',
                    ],
                    'source'      => 'gemini',
                    'model'       => $this->lastModelUsed,
                ];
            }
        }

        // Penanganan error jujur & sopan bila API Key kosong / kuota habis / jaringan gagal
        $isKeyMissing = ($this->apiKey === '');
        $errReply = $isKeyMissing
            ? 'Maaf, layanan Konsultasi AI 24 Jam belum dapat terhubung karena API Key Google Gemini belum dikonfigurasi pada sistem. Silakan hubungi administrator atau kunjungi langsung UKESMAS Universitas Klabat untuk bantuan medis.'
            : 'Maaf, layanan Konsultasi AI 24 Jam sedang mengalami kendala koneksi ke server AI saat ini. Silakan coba beberapa saat lagi, atau kunjungi langsung UKESMAS Universitas Klabat bila Anda memerlukan pemeriksaan medis segera.';

        return [
            'reply'           => $errReply,
            'suggestions'     => [
                'Cara meredakan demam dan pusing?',
                'Pertolongan pertama saat maag kambuh',
                'Tips menjaga daya tahan tubuh saat begadang',
            ],
            'source'          => 'unavailable',
            'model'           => $this->model,
            'fallback_reason' => $this->lastError,
        ];
    }

    private function compactContext($c) {
        $lines = [];
        $hari  = $c['hari_ini'] ?? '';
        $tgl   = $c['tanggal_hari_ini'] ?? date('d-m-Y');

        // Konteks 1: Profil Singkat UKESMAS UNKLAB
        $lines[] = '[1. PROFIL SINGKAT UKESMAS UNIVERSITAS KLABAT]';
        $lines[] = $c['profil_klinik'] ?? 'UKESMAS (Unit Kesehatan Mahasiswa) Universitas Klabat di Airmadidi melayani konsultasi AI, antrean online FIFO, pemeriksaan dokter, apotek/obat, dan surat sakit digital.';
        $lines[] = 'Jam Operasional: ' . ($c['jam_operasional'] ?? 'Senin–Jumat 08:00–16:00 WITA') . '; Kontak: ' . ($c['kontak_telp'] ?? '-');
        $lines[] = 'Waktu Sekarang: ' . $hari . ', ' . $tgl . ' pukul ' . date('H:i') . ' WITA';

        // Konteks 2: Struktur Pimpinan & Staf UKESMAS (dari tabel users)
        $lines[] = '';
        $lines[] = '[2. STRUKTUR PIMPINAN & STAF UKESMAS (REAL-TIME DB)]';
        $stafList = $c['staf_ukesmas'] ?? [];
        if (!empty($stafList)) {
            foreach ($stafList as $st) {
                $jabatan = $st['role'] === 'clinicadmin' ? 'Kepala UKESMAS' : 'Dokter / Tenaga Medis';
                $lines[] = "- {$st['nama_lengkap']} — Jabatan: {$jabatan}";
            }
        } else {
            $lines[] = '- Data pimpinan & staf belum tercatat.';
        }

        // Konteks 3: Jadwal Dokter & Perawat Jaga Hari Ini & Mingguan
        $lines[] = '';
        $lines[] = "[3. JADWAL DOKTER & PERAWAT JAGA HARI INI ({$hari})]";
        $todaySched = $c['jadwal_hari_ini'] ?? [];
        if (!empty($todaySched)) {
            foreach ($todaySched as $j) {
                $lok = !empty($j['lokasi']) ? ' - ' . $j['lokasi'] : '';
                $lines[] = "- {$j['nama_tenaga']} ({$j['peran']}): {$j['jam_mulai']}–{$j['jam_selesai']} WITA{$lok}";
            }
        } else {
            $lines[] = "- Tidak ada jadwal dokter/perawat jaga reguler pada hari {$hari}.";
        }

        if (!empty($c['jadwal'])) {
            $lines[] = 'Jadwal Mingguan Lengkap:';
            foreach ($c['jadwal'] as $j) {
                $lines[] = "  * {$j['hari']} {$j['jam_mulai']}-{$j['jam_selesai']}: {$j['nama_tenaga']} ({$j['peran']})";
            }
        }

        // Konteks 4: Katalog Obat Tersedia (stok > 0 & belum kedaluwarsa)
        $lines[] = '';
        $lines[] = '[4. KATALOG OBAT TERSEDIA DI APOTEK UKESMAS (STOK > 0 & BELUM KEDALUWARSA)]';
        $obatList = $c['obat'] ?? [];
        if (!empty($obatList)) {
            foreach ($obatList as $m) {
                $lines[] = "- {$m['nama']} [Kategori: {$m['kategori']}] | Stok: {$m['stok']} {$m['satuan']} | Indikasi: {$m['indikasi']}";
            }
        } else {
            $lines[] = '- Saat ini tidak ada stok obat tersedia yang tercatat.';
        }

        // Konteks 5: Status Antrean Live Hari Ini
        $a = $c['antrean'] ?? [];
        $dipanggil = !empty($a['dipanggil']) ? $a['dipanggil'] : 'Belum ada yang dipanggil';
        $menunggu  = (int)($a['menunggu'] ?? 0);
        $selesai   = (int)($a['selesai'] ?? 0);
        $est       = (int)($a['estimasi_menit'] ?? ($menunggu * 8));
        $lines[] = '';
        $lines[] = '[5. STATUS ANTREAN LIVE HARI INI]';
        $lines[] = "- Nomor sedang dipanggil: {$dipanggil}";
        $lines[] = "- Jumlah pasien menunggu: {$menunggu} orang";
        $lines[] = "- Pasien selesai diperiksa hari ini: {$selesai} orang";
        $lines[] = "- Estimasi waktu tunggu: ± {$est} menit";

        if (!empty($c['artikel'])) {
            $lines[] = '';
            $lines[] = '[PANDUAN KESEHATAN KAMPUS]';
            foreach ($c['artikel'] as $art) {
                $lines[] = "- {$art['judul']}: " . mb_substr($art['isi'], 0, 220);
            }
        }
        return implode("\n", $lines);
    }

    private function ruleChat($msg, $c) {
        $t = mb_strtolower(trim($msg));
        $has = function ($arr) use ($t) { foreach ($arr as $k) if (mb_strpos($t, $k) !== false) return true; return false; };
        $sugg = ['Siapa Kepala UKESMAS UNKLAB?', 'Dokter siapa yang jaga hari ini?', 'Obat maag apa yang tersedia?'];
        $jam = $c['jam_operasional'] ?? 'Senin–Jumat 08:00–16:00 WITA';

        if ($has(['halo', 'hai ', 'hi ', 'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam']) && mb_strlen($t) < 40) {
            return ['reply' => 'Halo! Saya Asisten Klinik UKESMAS UNKLAB. Saya dapat membantu informasi pimpinan & staf klinik, jadwal dokter jaga hari ini, stok obat tersedia, status antrean live, serta informasi kesehatan kampus. Ada yang bisa saya bantu?', 'suggestions' => $sugg];
        }
        if ($has(['darurat', 'nyeri dada', 'pingsan', 'kejang', 'sesak berat', 'muntah darah', 'tidak sadar', 'perdarahan hebat', 'igd'])) {
            return ['reply' => "Bila mengalami kondisi gawat darurat, segera hubungi petugas kampus atau menuju IGD terdekat. Jangan tinggalkan pasien sendirian dan longgarkan pakaian. Kontak UKESMAS: " . ($c['kontak_telp'] ?? '') . '.', 'suggestions' => ['Jadwal dokter jaga hari ini?', 'Bagaimana prosedur berobat di UKESMAS?']];
        }

        // Kumpulkan jawaban multi-intent berbasis data RAG real-time
        $parts = [];

        // 0. Intent Struktur Pimpinan & Staf UKESMAS (WAJIB dipisahkan dari "sakit kepala")
        $asksLeadership = $has(['kepala ukesmas', 'kepala klinik', 'siapa kepala', 'pimpinan', 'ketua ukesmas', 'staf ukesmas', 'staff ukesmas', 'tenaga medis', 'daftar dokter', 'petugas ukesmas', 'siapa saja dokter']);
        if ($asksLeadership) {
            $staf = $c['staf_ukesmas'] ?? [];
            $kepala = [];
            $medis  = [];
            foreach ($staf as $st) {
                if ($st['role'] === 'clinicadmin') $kepala[] = $st['nama_lengkap'];
                else $medis[] = $st['nama_lengkap'];
            }
            // Lengkapi nama dokter/perawat dari tabel jadwal bila belum tercantum
            foreach (($c['jadwal'] ?? []) as $j) {
                $nm = $j['nama_tenaga'] . ' (' . $j['peran'] . ')';
                $already = false;
                foreach ($medis as $mName) {
                    if (mb_stripos($mName, $j['nama_tenaga']) !== false || mb_stripos($j['nama_tenaga'], $mName) !== false) {
                        $already = true; break;
                    }
                }
                if (!$already && !in_array($nm, $medis, true)) $medis[] = $nm;
            }
            $stafLines = [];
            if (!empty($kepala)) $stafLines[] = '• Kepala UKESMAS: ' . implode(', ', $kepala);
            if (!empty($medis))  $stafLines[] = '• Dokter & Tenaga Medis: ' . implode('; ', $medis);
            if (!empty($stafLines)) {
                $parts[] = "Struktur Pimpinan & Staf UKESMAS Universitas Klabat:\n" . implode("\n", $stafLines);
            } else {
                $parts[] = 'Data pimpinan dan staf UKESMAS saat ini belum tersedia di database.';
            }
        }

        // 1. Intent Jadwal Dokter Jaga
        $asksSchedule = $has(['jadwal', 'dokter jaga', 'siapa yang jaga', 'dokter siapa', 'jam buka', 'jam operasional', 'buka jam', 'tutup', 'praktik', 'perawat jaga'])
            || ($has(['dokter']) && !$asksLeadership);
        if ($asksSchedule) {
            $hari = $c['hari_ini'] ?? '';
            $today = $c['jadwal_hari_ini'] ?? [];
            if (empty($today)) {
                foreach (($c['jadwal'] ?? []) as $j) {
                    if (strcasecmp($j['hari'], $hari) === 0) $today[] = $j;
                }
            }
            if (!empty($today)) {
                $jLines = [];
                foreach ($today as $j) {
                    $lok = !empty($j['lokasi']) ? ' (' . $j['lokasi'] . ')' : '';
                    $jLines[] = "• {$j['nama_tenaga']} — {$j['peran']} ({$j['jam_mulai']}–{$j['jam_selesai']} WITA){$lok}";
                }
                $parts[] = "Jadwal Dokter/Perawat Jaga Hari Ini ({$hari}):\n" . implode("\n", $jLines);
            } else {
                $parts[] = "Hari ini ({$hari}) belum ada jadwal dokter/perawat reguler yang tercatat di sistem UKESMAS (Jam operasional: {$jam}).";
            }
        }

        // 2. Intent Status Antrean Live
        $asksQueue = $has(['antrean', 'antri', 'nomor', 'ambil nomor', 'tunggu', 'dipanggil']);
        if ($asksQueue) {
            $a = $c['antrean'] ?? [];
            $dip = !empty($a['dipanggil']) ? $a['dipanggil'] : 'Belum ada yang dipanggil';
            $wait = (int)($a['menunggu'] ?? 0);
            $est = (int)($a['estimasi_menit'] ?? ($wait * 8));
            $parts[] = "Status Antrean Live UKESMAS Hari Ini:\n• Nomor dipanggil: {$dip}\n• Pasien menunggu: {$wait} orang (estimasi tunggu ± {$est} menit)\n• Selesai dilayani: " . (int)($a['selesai'] ?? 0) . " orang.";
        }

        // 3. Intent Katalog Obat Tersedia (stok > 0 & belum kedaluwarsa)
        $matchedMed = [];
        foreach (($c['obat'] ?? []) as $m) {
            $nm = mb_strtolower($m['nama']);
            $first = explode(' ', $nm)[0];
            if (mb_strpos($t, $nm) !== false || (mb_strlen($first) > 3 && mb_strpos($t, $first) !== false)) {
                $matchedMed[] = $m;
            }
        }
        $symptomMap = [
            'maag'   => ['maag', 'asam lambung', 'gastritis', 'dispepsia', 'antasida', 'omeprazole', 'gastro', 'tukak', 'refluks', 'ulu hati'],
            'demam'  => ['demam', 'panas', 'antipiretik', 'paracetamol'],
            'batuk'  => ['batuk', 'pilek', 'flu', 'ispa', 'dahak'],
            'diare'  => ['diare', 'mencret', 'oralit'],
            'alergi' => ['alergi', 'gatal', 'antihistamin', 'biduran'],
            'nyeri'  => ['nyeri', 'sakit kepala', 'pusing', 'migrain', 'analgesik'],
            'vitamin'=> ['vitamin', 'suplemen', 'stamina'],
            'luka'   => ['luka', 'antiseptik', 'p3k'],
        ];
        $matchedSymptoms = [];
        foreach ($symptomMap as $k => $syns) {
            if ($has($syns)) $matchedSymptoms[] = $k;
        }
        $asksMed = !empty($matchedMed) || $has(['obat', 'stok', 'tersedia', 'apotek', 'resep', 'vitamin']) || (!empty($matchedSymptoms) && !$asksLeadership && !$asksSchedule && !$asksQueue);

        if ($asksMed) {
            if (!empty($matchedMed)) {
                $mLines = [];
                foreach (array_slice($matchedMed, 0, 5) as $m) {
                    $mLines[] = "• {$m['nama']} ({$m['kategori']}) — tersedia {$m['stok']} {$m['satuan']} | Indikasi: {$m['indikasi']}";
                }
                $parts[] = "Obat Tersedia di Apotek UKESMAS:\n" . implode("\n", $mLines) . "\n(Pemberian obat berdasarkan pemeriksaan dokter/perawat UKESMAS).";
            } elseif (!empty($matchedSymptoms)) {
                $list = [];
                foreach (($c['obat'] ?? []) as $m) {
                    $hay = mb_strtolower($m['nama'] . ' ' . $m['indikasi'] . ' ' . $m['kategori']);
                    foreach ($matchedSymptoms as $sym) {
                        if ($sym === 'maag' && mb_strpos(mb_strtolower($m['kategori']), 'analgesik') !== false) continue;
                        $syns = $symptomMap[$sym] ?? [$sym];
                        foreach ($syns as $s) {
                            if (mb_strpos($hay, $s) !== false) {
                                $list[$m['id']] = "• {$m['nama']} ({$m['kategori']}) — stok {$m['stok']} {$m['satuan']}";
                                break 2;
                            }
                        }
                    }
                }
                if (!empty($list)) {
                    $parts[] = "Obat Terkait yang Tersedia di UKESMAS Saat Ini:\n" . implode("\n", array_slice(array_values($list), 0, 6)) . "\n(Diberikan sesuai resep dokter/perawat UKESMAS).";
                } else {
                    $parts[] = "Mohon maaf, saat ini belum ditemukan stok obat tersedia yang cocok untuk keluhan tersebut di database apotek UKESMAS.";
                }
            } else {
                $totalObat = count($c['obat'] ?? []);
                $parts[] = "Saat ini tercatat {$totalObat} item obat aktif yang tersedia di apotek UKESMAS. Sebutkan nama obat atau keluhan spesifik (misal: obat maag, paracetamol, obat batuk) agar saya cek stoknya secara tepat.";
            }
        }

        // 4. Intent Profil / Layanan UKESMAS UNKLAB
        if (empty($parts) && $has(['apa itu ukesmas', 'tentang ukesmas', 'lokasi ukesmas', 'dimana ukesmas', 'di mana ukesmas', 'layanan ukesmas', 'fasilitas ukesmas', 'smartclinic'])) {
            $parts[] = ($c['profil_klinik'] ?? 'UKESMAS adalah layanan kesehatan kampus Universitas Klabat di Airmadidi.') . "\nJam operasional: {$jam}.";
        }

        if (!empty($parts)) {
            return ['reply' => implode("\n\n", $parts), 'suggestions' => $sugg];
        }

        if ($has(['surat sakit', 'surat izin', 'izin sakit', 'sks', 'verifikasi surat', 'surat keterangan'])) {
            return ['reply' => 'Surat Keterangan Sakit diterbitkan dokter UKESMAS setelah pemeriksaan dan memiliki kode verifikasi unik + QR. Mahasiswa dapat melihat/mencetaknya di menu "Surat Sakit Saya"; dosen dapat memverifikasi keasliannya di menu "Verifikasi Surat". Surat tidak memuat diagnosis demi kerahasiaan medis.', 'suggestions' => ['Bagaimana prosedur berobat di UKESMAS?', 'Dokter siapa yang jaga hari ini?']];
        }
        if ($has(['prosedur', 'cara berobat', 'alur'])) {
            return ['reply' => "Prosedur berobat di UKESMAS:\n1) Login SmartClinic & tulis keluhan di halaman Konsultasi Gejala.\n2) Ambil nomor antrean online — dilayani berurutan sesuai nomor (FIFO).\n3) Datang ke UKESMAS saat nomor dipanggil ({$jam}).\n4) Dokter memeriksa, memberi resep digital, dan surat sakit bila diperlukan.", 'suggestions' => ['Berapa status antrean saat ini?', 'Dokter siapa yang jaga hari ini?']];
        }

        // Pencarian artikel panduan kesehatan kampus (cegah false-match "kepala" -> "Sakit Kepala")
        $docs = [];
        foreach (($c['artikel'] ?? []) as $a) {
            if (mb_stripos($a['judul'], 'sakit kepala') !== false && !$has(['sakit kepala', 'nyeri kepala', 'pusing', 'migrain', 'cephalgia'])) {
                continue;
            }
            $docs[] = ['type' => 'artikel', 'id' => $a['id'], 'title' => $a['judul'], 'text' => $a['isi'], 'tags' => $a['tags'] ?? '', 'url' => ''];
        }
        $res = $this->ruleSearch($msg, $docs);
        if ($res && $res[0]['score'] >= 38) {
            $best = null;
            foreach ($c['artikel'] as $a) if ($a['id'] == $res[0]['id']) $best = $a;
            if ($best) return ['reply' => 'Berdasarkan panduan kesehatan kampus ("' . $best['judul'] . '"): ' . mb_substr($best['isi'], 0, 420) . (mb_strlen($best['isi']) > 420 ? '…' : '') . "\nBila keluhan memburuk, segera konsultasi ke dokter UKESMAS.", 'suggestions' => $sugg];
        }

        return ['reply' => "Maaf, saya adalah Asisten Klinik UKESMAS UNKLAB yang khusus membantu topik kesehatan, medis, dan layanan UKESMAS (pimpinan & staf, jadwal dokter jaga, ketersediaan obat, status antrean, dan prosedur klinik). Silakan ajukan pertanyaan seputar kesehatan atau layanan UKESMAS.", 'suggestions' => $sugg];
    }

    // =====================================================================
    // 4. SEMANTIC SEARCH
    // =====================================================================
    public function search($query, $docs) {
        $list = [];
        foreach ($docs as $d) {
            $list[] = $d['type'] . ':' . $d['id'] . ' | ' . $d['title'] . ' | ' . mb_substr($d['tags'] . ' ' . $d['text'], 0, 160);
        }
        $system = 'Anda adalah mesin pencari semantik kesehatan kampus UNKLAB. Pahami maksud kueri bahasa natural (termasuk sinonim dan keluhan awam), '
            . 'lalu pilih dokumen paling relevan dari daftar. Balas HANYA JSON.';
        $prompt = "KUERI: \"$query\"\n\nDAFTAR DOKUMEN (format tipe:id | judul | cuplikan):\n" . implode("\n", $list)
            . "\n\nBalas JSON: {\"jawaban_singkat\":\"1-2 kalimat jawaban/arahan untuk kueri\",\"hasil\":[{\"ref\":\"tipe:id\",\"relevansi\":0-100,\"alasan\":\"kenapa relevan\"}]} maksimal 6 hasil, urut relevansi menurun.";
        $r = $this->callJson($prompt, $system, 0.2);
        $byRef = [];
        foreach ($docs as $d) $byRef[$d['type'] . ':' . $d['id']] = $d;
        if ($r !== null && !empty($r['hasil']) && is_array($r['hasil'])) {
            $results = [];
            foreach ($r['hasil'] as $h) {
                $ref = trim((string)($h['ref'] ?? ''));
                if (!isset($byRef[$ref])) continue;
                $d = $byRef[$ref];
                $results[] = ['type' => $d['type'], 'id' => $d['id'], 'title' => $d['title'], 'snippet' => mb_substr($d['text'], 0, 200), 'score' => (int)($h['relevansi'] ?? 70), 'reason' => (string)($h['alasan'] ?? ''), 'url' => $d['url']];
            }
            if ($results) {
                return ['answer' => (string)($r['jawaban_singkat'] ?? ''), 'results' => $results, 'source' => 'gemini', 'model' => $this->lastModelUsed];
            }
        }
        $results = $this->ruleSearch($query, $docs);
        $answer = $results
            ? 'Hasil terbaik untuk "' . $query . '": ' . $results[0]['title'] . '.'
            : 'Tidak ada dokumen yang cocok. Coba kata kunci lain, misalnya "perut perih", "batuk pilek", atau "obat demam".';
        return ['answer' => $answer, 'results' => $results, 'source' => 'fallback', 'model' => 'semantic-rule-engine'];
    }

    private function synonyms() {
        return [
            'maag' => ['gastritis', 'lambung', 'perut', 'perih', 'ulu', 'hati', 'asam', 'kembung', 'mual', 'antasida', 'omeprazole', 'dispepsia', 'sendawa'],
            'perut' => ['maag', 'gastritis', 'lambung', 'diare', 'mules', 'kembung', 'mual'],
            'perih' => ['maag', 'gastritis', 'lambung', 'perut', 'ulu'],
            'demam' => ['panas', 'meriang', 'menggigil', 'suhu', 'paracetamol', 'kompres', 'febris'],
            'panas' => ['demam', 'meriang', 'suhu', 'paracetamol'],
            'batuk' => ['ispa', 'pilek', 'flu', 'tenggorokan', 'ambroxol', 'obh', 'dahak', 'bersin'],
            'pilek' => ['ispa', 'batuk', 'flu', 'bersin', 'hidung', 'ctm'],
            'flu' => ['ispa', 'batuk', 'pilek', 'demam'],
            'diare' => ['mencret', 'bab', 'cair', 'oralit', 'loperamide', 'dehidrasi', 'mules'],
            'mencret' => ['diare', 'oralit', 'loperamide'],
            'migrain' => ['pusing', 'sakit kepala', 'cephalgia', 'paracetamol', 'ibuprofen'],
            'pusing' => ['sakit kepala', 'migrain', 'cephalgia', 'dehidrasi'],
            'luka' => ['lecet', 'sayat', 'p3k', 'antiseptik', 'betadine', 'kasa', 'plester', 'cedera'],
            'alergi' => ['gatal', 'biduran', 'ruam', 'kulit', 'cetirizine', 'ctm', 'dermatitis'],
            'gatal' => ['alergi', 'ruam', 'biduran', 'kulit', 'hydrocortisone'],
            'darurat' => ['igd', 'pingsan', 'kejang', 'sesak', 'nyeri', 'dada'],
            'sesak' => ['asma', 'napas', 'salbutamol', 'darurat'],
            'surat' => ['sakit', 'izin', 'sks', 'verifikasi', 'qr', 'dosen'],
            'prosedur' => ['berobat', 'antrean', 'alur'],
            'obat' => ['apotek', 'resep', 'dosis', 'stok', 'tablet'],
            'ujian' => ['stamina', 'stres', 'tidur', 'maag', 'begadang', 'vitamin'],
            'vitamin' => ['stamina', 'daya', 'tahan', 'ujian'],
        ];
    }

    private function tokens($s) {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
        $stop = ['yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'saya', 'aku', 'ada', 'apa', 'bagaimana', 'cara', 'dengan', 'atau', 'ini', 'itu', 'sudah', 'sejak', 'kok', 'nih', 'dong', 'banget', 'sekali', 'terasa', 'rasa', 'lagi', 'mau', 'bisa', 'tidak', 'tak'];
        $out = [];
        foreach (preg_split('/\s+/', $s) as $w) if (mb_strlen($w) > 2 && !in_array($w, $stop)) $out[] = $w;
        return array_values(array_unique($out));
    }

    private function ruleSearch($query, $docs) {
        $qt = $this->tokens($query);
        if (!$qt) return [];
        $syn = $this->synonyms();
        $expanded = [];
        foreach ($qt as $w) {
            $expanded[$w] = 1.0;
            foreach ($syn as $k => $list) {
                if ($w === $k || mb_strpos($w, $k) !== false) foreach ($list as $s) if (!isset($expanded[$s])) $expanded[$s] = 0.5;
            }
        }
        $res = [];
        foreach ($docs as $d) {
            $title = mb_strtolower($d['title']);
            $tags = mb_strtolower($d['tags']);
            $text = mb_strtolower($d['text']);
            $score = 0; $hits = [];
            foreach ($expanded as $w => $wt) {
                $s = 0;
                if (mb_strpos($title, $w) !== false) $s += 6;
                if (mb_strpos($tags, $w) !== false) $s += 5;
                if (mb_strpos($text, $w) !== false) $s += 2;
                if ($s) { $score += $s * $wt; if ($wt === 1.0) $hits[] = $w; else $hits[] = $w . '~'; }
            }
            if ($score > 0) {
                $pct = (int)min(99, round(($score / (count($qt) * 8 + 4)) * 100) + 20);
                $res[] = ['type' => $d['type'], 'id' => $d['id'], 'title' => $d['title'], 'snippet' => mb_substr($d['text'], 0, 200), 'score' => $pct, 'raw' => $score,
                    'reason' => 'Cocok secara konteks dengan: ' . implode(', ', array_slice(array_unique($hits), 0, 5)), 'url' => $d['url']];
            }
        }
        usort($res, function ($a, $b) { return $b['raw'] <=> $a['raw']; });
        foreach ($res as &$r) unset($r['raw']);
        unset($r);
        return array_slice($res, 0, 6);
    }
}
