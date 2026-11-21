<?php
/**
 * Controller Jadwal Dokter & Perawat Jaga - UNKLAB SmartClinic
 */
class jadwal extends ClinicController {

    public function index() {
        $u      = $this->auth();
        $cModel = new Content_model();

        $this->render('jadwal/index', [
            'title'       => 'Jadwal Dokter & Perawat Jaga - ' . APP_NAME,
            'active_menu' => 'jadwal',
            'can_edit'    => in_array($u['role'], ['superadmin', 'clinicadmin']),
            'schedules'   => $cModel->schedules(),
            'hari_ini'    => $this->hariIni(),
        ]);
    }

    public function simpan() {
        $this->auth(['superadmin', 'clinicadmin']);
        $this->requirePost();

        $cModel = new Content_model();
        $id     = $this->intInput('id', 0);
        $cModel->saveSchedule($id, [
            'hari'        => $this->input('hari', 'Senin'),
            'jam_mulai'   => $this->input('jam_mulai', '08:00'),
            'jam_selesai' => $this->input('jam_selesai', '16:00'),
            'nama_tenaga' => $this->input('nama_tenaga'),
            'peran'       => $this->input('peran', 'Dokter Umum'),
            'lokasi'      => $this->input('lokasi', 'Ruang Periksa 1'),
        ]);
        $this->logActivity('Simpan Jadwal Jaga', 'Memperbarui jadwal dokter/perawat');
        flash('success', 'Jadwal jaga berhasil disimpan.');
        $this->redirect('jadwal/');
    }

    public function hapus($id = 0) {
        $this->auth(['superadmin', 'clinicadmin']);
        $this->requirePost();

        (new Content_model())->deleteSchedule((int)$id);
        flash('info', 'Jadwal berhasil dihapus.');
        $this->redirect('jadwal/');
    }
}
