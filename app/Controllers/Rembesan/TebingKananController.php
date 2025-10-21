<?php
namespace App\Controllers\Rembesan;

use App\Controllers\BaseController;
use App\Models\Rembesan\TebingKananModel;

class TebingKananController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new TebingKananModel();
    }

    public function proses($pengukuranId)
    {
        $hasil = $this->model->proses($pengukuranId);

        if (!$hasil) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Gagal menghitung Tebing Kanan'
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $hasil
        ]);
    }
}
