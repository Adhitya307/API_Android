<?php
namespace App\Controllers\Rembesan;

use App\Controllers\BaseController;
use App\Models\Rembesan\AnalisaLookBurtModel;

class AnalisaLookBurt extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new AnalisaLookBurtModel();
    }

    /**
     * Hitung semua data
     */
    public function hitungSemua()
    {
        $hasilAll = $this->model->prosesSemua();

        return $this->response->setJSON([
            'status' => 'success',
            'total'  => count($hasilAll),
            'data'   => $hasilAll
        ]);
    }

    /**
     * Hitung satu data berdasarkan pengukuran_id
     */
    public function hitung($pengukuran_id)
    {
        $hasil = $this->model->prosesSatu($pengukuran_id);

        if (!$hasil) {
            return $this->response->setJSON([
                'status' => 'error',
                'msg'    => "Data tidak ditemukan atau perhitungan gagal untuk pengukuran_id {$pengukuran_id}"
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'msg'    => 'Perhitungan Analisa Look Burt berhasil',
            'data'   => $hasil
        ]);
    }
}
