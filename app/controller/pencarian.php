<?php
/**
 * Controller AI Semantic Health & Medicine Search - UNKLAB SmartClinic
 */
class pencarian extends ClinicController {

    public function index() {
        $this->start_default_session_app();
        $cModel = new Content_model();
        $mModel = new Medicine_model();

        $q   = $this->input('q', '');
        $res = null;
        if ($q !== '') {
            $docs = $cModel->searchDocs(true);
            $gem  = new GeminiService();
            $res  = $gem->search($q, $docs);
            $this->logAi('Search', $q, ['count' => count($res['results'] ?? [])], $res);
        }

        $this->render('pencarian/index', [
            'title'       => 'AI Semantic Health & Medicine Search - ' . APP_NAME,
            'active_menu' => 'pencarian',
            'query'       => $q,
            'search_res'  => $res,
            'articles'    => $cModel->articles(),
            'medicines'   => $mModel->all(),
        ]);
    }

    public function artikel($id = 0) {
        $this->start_default_session_app();
        $cModel = new Content_model();
        $art    = $cModel->article((int)$id);
        if (!$art) {
            $this->abort(404, 'Artikel kesehatan tidak ditemukan.');
        }
        $this->render('pencarian/artikel', [
            'title'       => $art['judul'] . ' - ' . APP_NAME,
            'active_menu' => 'pencarian',
            'article'     => $art,
            'others'      => array_slice($cModel->articles(), 0, 5),
        ]);
    }

    public function obat($id = 0) {
        $this->start_default_session_app();
        $mModel = new Medicine_model();
        $med    = $mModel->find((int)$id);
        if (!$med) {
            $this->abort(404, 'Data obat tidak ditemukan.');
        }
        $this->render('pencarian/obat', [
            'title'       => $med['nama'] . ' - Katalog Obat ' . APP_NAME,
            'active_menu' => 'pencarian',
            'med'         => $med,
        ]);
    }
}
