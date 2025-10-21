<?php
namespace App\Controllers\Rembesan;

use App\Controllers\BaseController;
use App\Models\Rembesan\PerhitunganBatasMaksimalModel;

class BatasMaksimalController extends BaseController
{
    protected $modelBatas;

    public function __construct()
    {
        $this->modelBatas = new PerhitunganBatasMaksimalModel();
    }

    /**
     * Hitung & simpan batas maksimal untuk pengukuran_id tertentu
     */
    public function proses($pengukuran_id)
    {
        $hasil = $this->modelBatas->hitungBatas($pengukuran_id);

        if (!$hasil) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Gagal menghitung batas maksimal'
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $hasil
        ]);
    }
}
