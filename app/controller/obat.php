<?php
/**
 * Controller Medicine & Pharmacy Inventory Management - UNKLAB SmartClinic
 */
class obat extends ClinicController {

    public function index() {
        $u        = $this->auth(['superadmin', 'clinicadmin', 'medstaff']);
        $medModel = new Medicine_model();

        $q        = $this->input('q', '');
        $kategori = $this->input('kategori', '');
        $status   = $this->input('status', '');

        $this->render('obat/index', [
            'title'       => 'Inventaris Obat & Apotek - ' . APP_NAME,
            'active_menu' => 'obat',
            'can_manage'  => in_array($u['role'], ['superadmin', 'clinicadmin']),
            'counts'      => $medModel->counts(),
            'medicines'   => $medModel->search($q, $kategori, $status),
            'categories'  => $medModel->categories(),
            'logs'        => in_array($u['role'], ['superadmin', 'clinicadmin']) ? $medModel->logs(0, '', 25) : [],
            'filter'      => ['q' => $q, 'kategori' => $kategori, 'status' => $status],
        ]);
    }

    /** POST /obat/simpan - Tambah atau edit obat (khusus admin) */
    public function simpan() {
        $this->auth(['superadmin', 'clinicadmin']);
        $this->requirePost();

        $medModel = new Medicine_model();
        $id       = $this->intInput('id', 0);
        $d        = [
            'nama'                => $this->input('nama'),
            'kategori'            => $this->input('kategori', 'Analgesik & Antipiretik'),
            'satuan'              => $this->input('satuan', 'Tablet'),
            'stok_awal'           => $this->intInput('stok_awal', 0),
            'stok_minimum'        => $this->intInput('stok_minimum', 20),
            'harga'               => $this->intInput('harga', 500),
            'tanggal_kedaluwarsa' => $this->input('tanggal_kedaluwarsa'),
            'lokasi_rak'          => $this->input('lokasi_rak', 'Rak A-1'),
            'indikasi'            => $this->input('indikasi'),
        ];

        if ($d['nama'] === '') {
            flash('danger', 'Nama obat wajib diisi.');
            $this->redirect('obat/');
        }

        $medModel->save($id, $d);
        $this->logActivity($id ? 'Edit Obat' : 'Tambah Obat Baru', $d['nama']);
        flash('success', 'Data obat ' . $d['nama'] . ' berhasil disimpan.');
        $this->redirect('obat/');
    }

    /** POST /obat/restock - Pencatatan stok masuk (restock khusus admin) */
    public function restock() {
        $u = $this->auth(['superadmin', 'clinicadmin']);
        $this->requirePost();

        $medModel = new Medicine_model();
        $id       = $this->intInput('medicine_id', 0);
        $qty      = $this->intInput('jumlah', 0);
        $ket      = $this->input('keterangan', 'Restock pengadaan UKESMAS');
        $exp      = $this->input('tanggal_kedaluwarsa', '');

        $m = $medModel->find($id);
        if (!$m || $qty <= 0) {
            flash('danger', 'Pilih obat dan masukkan jumlah restock yang valid.');
            $this->redirect('obat/');
        }

        $medModel->restock($id, $qty, $ket, $u['id'], $exp ?: null);
        $this->logActivity('Restock Obat', $m['nama'] . ' +' . $qty . ' ' . $m['satuan']);
        flash('success', 'Restock ' . $m['nama'] . ' sebanyak +' . $qty . ' ' . $m['satuan'] . ' berhasil dicatat.');
        $back = $this->input('redirect_to', 'obat/');
        $this->redirect($back);
    }

    /** POST /obat/opname - Penyesuaian saldo obat (stok opname) */
    public function opname() {
        $u = $this->auth(['superadmin', 'clinicadmin']);
        $this->requirePost();

        $medModel = new Medicine_model();
        $id       = $this->intInput('medicine_id', 0);
        $stokBaru = $this->intInput('stok_baru', 0);
        $ket      = $this->input('keterangan', 'Stok opname fisik apotek');

        $m = $medModel->find($id);
        if ($m) {
            $medModel->adjust($id, $stokBaru, $ket, $u['id']);
            $this->logActivity('Stok Opname Obat', $m['nama'] . ' disesuaikan menjadi ' . $stokBaru);
            flash('success', 'Stok ' . $m['nama'] . ' disesuaikan menjadi ' . $stokBaru . ' ' . $m['satuan'] . '.');
        }
        $this->redirect('obat/');
    }

    /** POST /obat/hapus/{id} */
    public function hapus($id = 0) {
        $this->auth(['superadmin', 'clinicadmin']);
        $this->requirePost();

        $medModel = new Medicine_model();
        if ($medModel->delete((int)$id)) {
            $this->logActivity('Hapus Obat', 'Menghapus obat #' . (int)$id);
            flash('success', 'Obat berhasil dihapus.');
        } else {
            flash('warning', 'Obat tidak dapat dihapus karena sudah tercatat pada resep EMR.');
        }
        $this->redirect('obat/');
    }
}
