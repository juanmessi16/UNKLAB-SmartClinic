<?php
/**
 * Controller Laporan Klinik - UNKLAB SmartClinic
 */
class laporan extends ClinicController {

    public function index() {
        $this->auth(['superadmin', 'clinicadmin', 'medstaff']);
        $stats    = new Stats_model();
        $patModel = new Patient_model();
        $emrModel = new Emr_model();

        $dari     = $this->input('dari', date('Y-m-d', strtotime('-13 day')));
        $sampai   = $this->input('sampai', date('Y-m-d'));
        $kategori = $this->input('kategori', '');

        $rep = $stats->report($dari, $sampai, '', $kategori);

        $this->render('laporan/index', [
            'title'         => 'Laporan Operasional Klinik - ' . APP_NAME,
            'active_menu'   => 'laporan',
            'report'        => $rep,
            'categories'    => $emrModel->categories(),
            'filter'        => ['dari' => $dari, 'sampai' => $sampai, 'kategori' => $kategori],
        ]);
    }
}
