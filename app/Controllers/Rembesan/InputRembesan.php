<?php
namespace App\Controllers\Rembesan;

use CodeIgniter\Controller;
use Config\Database;
use App\Models\Rembesan\MDataPengukuran;
use App\Models\Rembesan\MThomsonWeir;
use App\Models\Rembesan\MSR;
use App\Models\Rembesan\MBocoranBaru;

class InputRembesan extends Controller
{
    protected $dataModel, $thomsonModel, $srModel, $bocoranModel;

    public function __construct()
    {
        $this->dataModel    = new MDataPengukuran();
        $this->thomsonModel = new MThomsonWeir();
        $this->srModel      = new MSR();
        $this->bocoranModel = new MBocoranBaru();

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
    }

    public function index()
    {
        $data = json_decode($this->request->getBody(), true) ?: $this->request->getPost();
        if (!$data) return $this->response->setJSON(['status'=>'error','message'=>'Tidak ada data dikirim!']);

        $mode = $data['mode'] ?? null;
        $pengukuran_id = $data['pengukuran_id'] ?? null;
        $temp_id = $data['temp_id'] ?? null;

        if (!$mode) return $this->response->setJSON(['status'=>'error','message'=>'Parameter mode wajib dikirim!']);

        switch ($mode) {
            case 'pengukuran':
                return $this->response->setJSON($this->dataModel->simpanPengukuran($data, $temp_id));
            case 'thomson':
                return $this->response->setJSON($this->thomsonModel->simpanThomson($data, $pengukuran_id));
            case 'sr':
                return $this->response->setJSON($this->srModel->simpanSR($data, $pengukuran_id));
            case 'bocoran':
                return $this->response->setJSON($this->bocoranModel->simpanBocoran($data, $pengukuran_id));
            default:
                return $this->response->setJSON(['status'=>'error','message'=>"Mode tidak dikenali: $mode"]);
        }
    }
}
