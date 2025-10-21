<?php
namespace App\Controllers\Rembesan;

use App\Controllers\BaseController;
use App\Models\Rembesan\MSR;
use App\Models\Rembesan\PerhitunganSRModel;

class SRController extends BaseController
{
    protected $msr;
    protected $perhitungan;

    public function __construct()
    {
        $this->msr = new MSR();
        $this->perhitungan = new PerhitunganSRModel();
    }

    /**
     * Hitung SR berdasarkan pengukuran_id
     * Nama method tetap `hitung()` supaya Android tidak perlu diubah
     */
    public function hitung($pengukuran_id, $silent = false)
    {
        try {
            log_message('debug', "[SR] Mulai hitung untuk pengukuran_id={$pengukuran_id}");

            // ambil data mentah
            $dataMentah = $this->msr->where('pengukuran_id', $pengukuran_id)->first();

            if (!$dataMentah) {
                log_message('error', "[SR] Data tidak ditemukan untuk pengukuran_id={$pengukuran_id}");
                return ['status' => 'error', 'msg' => 'Data tidak ditemukan'];
            }

            // panggil method hitung() di Model
            $hasil = $this->perhitungan->hitung($pengukuran_id, $dataMentah);

            log_message('debug', "[SR] Perhitungan SR selesai untuk pengukuran_id={$pengukuran_id}");

            return ['status' => 'success', 'data' => $hasil];

        } catch (\Throwable $e) {
            log_message('error', "[SR] Error hitung SR: " . $e->getMessage());
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
}
