<?php
namespace App\Controllers\Rembesan;

use App\Controllers\BaseController;
use App\Models\Rembesan\MBocoranBaru;
use App\Models\Rembesan\PerhitunganBocoranModel;

class BocoranBaruController extends BaseController
{
    protected $bocoranModel;
    protected $perhitunganModel;

    public function __construct()
    {
        $this->bocoranModel       = new MBocoranBaru();
        $this->perhitunganModel   = new PerhitunganBocoranModel();
    }

    /**
     * Simpan data bocoran baru dan hitung perhitungan otomatis
     */
    public function simpanBocoran()
    {
        $data = $this->request->getPost();

        // Simpan data mentah bocoran lewat model
        $simpan = $this->bocoranModel->simpanBocoran($data, $data['pengukuran_id']);

        if ($simpan['status'] !== 'success') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $simpan['message']
            ]);
        }

        // Trigger perhitungan otomatis lewat model
        $bocoran = $this->bocoranModel->where('pengukuran_id', $data['pengukuran_id'])->first();
        $hasil   = $this->perhitunganModel->hitungLangsung($bocoran);

        return $this->response->setJSON([
            'status'          => 'success',
            'message'         => 'Data bocoran berhasil disimpan dan perhitungan dipicu',
            'pengukuran_id'   => $data['pengukuran_id'],
            'hasil_perhitungan' => $hasil
        ]);
    }
}
