<?php
/**
 * Controller AI Health Analytics & Medicine Stock Forecasting - UNKLAB SmartClinic
 */
class analitik extends ClinicController {

    public function index() {
        $this->auth(['superadmin', 'clinicadmin', 'medstaff']);
        $stats = new Stats_model();
        $agg   = $stats->forecastAggregate();

        // Jalankan prediksi awal secara otomatis bila belum ada atau dipicu
        $gem      = new GeminiService();
        $forecast = $gem->forecast($agg);

        $this->render('analitik/index', [
            'title'       => 'AI Health Analytics & Forecast - ' . APP_NAME,
            'active_menu' => 'analitik',
            'charts'      => false,
            'aggregate'   => $agg,
            'forecast'    => $forecast,
        ]);
    }
}
