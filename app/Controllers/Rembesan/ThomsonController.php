<?php

namespace App\Controllers\Rembesan;

use App\Controllers\BaseController;
use App\Models\Rembesan\DataGabunganModel;
use App\Models\Rembesan\PerhitunganThomsonModel;

class ThomsonController extends BaseController
{
    protected $dataGabunganModel;
    protected $thomsonModel;

    public function __construct()
    {
        $this->dataGabunganModel = new DataGabunganModel();
        $this->thomsonModel      = new PerhitunganThomsonModel();
    }

    public function hitung($pengukuran_id, $returnArray = false)
    {
        log_message('debug', "[ThomsonController] Mulai perhitungan untuk ID: {$pengukuran_id}");

        $dataGabungan = $this->dataGabunganModel->getDataById($pengukuran_id);
        if (!$dataGabungan) {
            $msg = "Data tidak ditemukan untuk ID: {$pengukuran_id}";
            return $returnArray ? ['success' => false, 'message' => $msg] 
                                : $this->response->setJSON(['success' => false, 'message' => $msg]);
        }

        // Panggil model untuk hitung
        $result = $this->thomsonModel->hitung($pengukuran_id, $dataGabungan);

        return $returnArray ? $result : $this->response->setJSON($result);
    }
}
