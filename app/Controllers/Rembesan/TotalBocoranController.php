<?php
namespace App\Controllers\Rembesan;

use App\Controllers\BaseController;
use App\Models\Rembesan\TotalBocoranModel;

class TotalBocoranController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new TotalBocoranModel();
    }

    public function proses($pengukuran_id)
    {
        $hasil = $this->model->proses($pengukuran_id);

        if (!$hasil) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Data belum lengkap atau perhitungan gagal'
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $hasil
        ]);
    }
}
