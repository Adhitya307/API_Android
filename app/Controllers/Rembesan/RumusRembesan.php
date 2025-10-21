<?php

namespace App\Controllers\Rembesan;

use App\Controllers\BaseController;
use App\Helpers\Rembesan\AnalisaLookBurtHelper;
use CodeIgniter\API\ResponseTrait;

use App\Models\Rembesan\AnalisaLookBurtModel;
use App\Models\Rembesan\MBocoranBaru;
use App\Models\Rembesan\PerhitunganBocoranModel;
use App\Models\Rembesan\PerhitunganThomsonModel;
use App\Models\Rembesan\PerhitunganSRModel;
use App\Models\Rembesan\PerhitunganIntiGaleryModel;
use App\Models\Rembesan\PerhitunganSpillwayModel;
use App\Models\Rembesan\TebingKananModel;
use App\Models\Rembesan\TotalBocoranModel;
use App\Models\Rembesan\PerhitunganBatasMaksimalModel;
use App\Models\Rembesan\DataGabunganModel;

class RumusRembesan extends BaseController
{
    use ResponseTrait;

    protected $lookBurtHelper;
    protected $lookBurtModel;
    protected $dataGabunganModel;

    public function __construct()
    {
        $this->lookBurtHelper = new AnalisaLookBurtHelper();
        $this->lookBurtModel  = new AnalisaLookBurtModel();
        $this->dataGabunganModel = new DataGabunganModel();
    }

    public function hitungSemua()
    {
        $json = $this->request->getJSON(true);
        $pengukuran_id = $json['pengukuran_id'] ?? null;

        if (!$pengukuran_id) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'pengukuran_id wajib diisi'
            ], 400);
        }

        log_message('debug', "[HitungSemua] START proses semua perhitungan untuk ID={$pengukuran_id}");

        $results = [];
        $allSuccess = true;
        $hasilLookBurt = null;

        try {
            // 🔹 Ambil data gabungan dari DataGabunganModel
            $dataGabungan = $this->dataGabunganModel->getDataById($pengukuran_id);
            
            if (!$dataGabungan) {
                log_message('error', "[HitungSemua] Data gabungan tidak ditemukan untuk ID: {$pengukuran_id}");
                return $this->respond([
                    'status'  => 'error',
                    'message' => "Data gabungan tidak ditemukan untuk ID: {$pengukuran_id}"
                ], 400);
            }

            log_message('debug', "[HitungSemua] Data gabungan ditemukan untuk ID: {$pengukuran_id}");

            // 🔹 Thomson - method: hitung() DENGAN parameter dataGabungan
            $thomsonModel = new PerhitunganThomsonModel();
            $hasilThomson = $thomsonModel->hitung($pengukuran_id, $dataGabungan);
            $results['Thomson'] = ($hasilThomson['success'] ?? false)
                ? "Perhitungan Thomson berhasil"
                : "Perhitungan Thomson gagal: " . ($hasilThomson['message'] ?? 'Tidak diketahui');
            if (!($hasilThomson['success'] ?? false)) $allSuccess = false;

            // 🔹 SR - method: hitung() DENGAN parameter dataMentah (dari dataGabungan['sr'])
            $srModel = new PerhitunganSRModel();
            $dataMentah = $dataGabungan['sr'] ?? []; // Ambil data SR dari data gabungan
            $hasilSR = $srModel->hitung($pengukuran_id, $dataMentah);
            $results['SR'] = (!empty($hasilSR))
                ? "Perhitungan SR berhasil"
                : "Perhitungan SR gagal: Data SR tidak ditemukan";
            if (empty($hasilSR)) $allSuccess = false;

            // 🔹 Bocoran Baru - method: hitungLangsung()
            $mbocoranBaruModel = new MBocoranBaru();
            $perhitunganBocoranModel = new PerhitunganBocoranModel();
            $bocoranData = $mbocoranBaruModel->where('pengukuran_id', $pengukuran_id)->first();
            if ($bocoranData) {
                $hasilBocoran = $perhitunganBocoranModel->hitungLangsung($bocoranData);
                $results['BocoranBaru'] = "Perhitungan Bocoran Baru berhasil";
            } else {
                $results['BocoranBaru'] = "Perhitungan Bocoran Baru gagal: Data bocoran tidak ditemukan";
                $allSuccess = false;
            }

            // 🔹 Inti Galery - method: proses()
            $intiModel = new PerhitunganIntiGaleryModel();
            $hasilInti = $intiModel->proses($pengukuran_id);
            $results['IntiGalery'] = ($hasilInti !== false)
                ? "Perhitungan IntiGalery berhasil"
                : "Perhitungan IntiGalery gagal";
            if ($hasilInti === false) $allSuccess = false;

            // 🔹 Spillway - method: proses()
            $spillwayModel = new PerhitunganSpillwayModel();
            $hasilSpillway = $spillwayModel->proses($pengukuran_id);
            $results['Spillway'] = ($hasilSpillway !== false)
                ? "Perhitungan Spillway berhasil"
                : "Perhitungan Spillway gagal";
            if ($hasilSpillway === false) $allSuccess = false;

            // 🔹 Tebing Kanan - method: proses()
            $tebingModel = new TebingKananModel();
            $hasilTebing = $tebingModel->proses($pengukuran_id);
            $results['TebingKanan'] = ($hasilTebing !== false)
                ? "Perhitungan Tebing Kanan berhasil"
                : "Perhitungan Tebing Kanan gagal";
            if ($hasilTebing === false) $allSuccess = false;

            // 🔹 Total Bocoran - method: proses()
            $totalBocoranModel = new TotalBocoranModel();
            $hasilTotal = $totalBocoranModel->proses($pengukuran_id);
            $results['TotalBocoran'] = ($hasilTotal !== false)
                ? "Perhitungan Total Bocoran berhasil"
                : "Perhitungan Total Bocoran gagal";
            if ($hasilTotal === false) $allSuccess = false;

            // 🔹 Batas Maksimal - method: hitungBatas()
            $batasMaksimalModel = new PerhitunganBatasMaksimalModel();
            $hasilBatas = $batasMaksimalModel->hitungBatas($pengukuran_id);
            $results['BatasMaksimal'] = ($hasilBatas !== null)
                ? "Perhitungan Batas Maksimal berhasil"
                : "Perhitungan Batas Maksimal gagal: Data tidak ditemukan";
            if ($hasilBatas === null) $allSuccess = false;

            // 🔹 Analisa Look Burt
            $hasilLookBurt = $this->lookBurtHelper->hitungLookBurt($pengukuran_id);
            if ($hasilLookBurt) {
                $hasilLookBurt['rembesan_per_m'] = round($hasilLookBurt['rembesan_per_m'], 8);
                $existing = $this->lookBurtModel->where('pengukuran_id', $pengukuran_id)->first();
                if ($existing) {
                    $this->lookBurtModel->update($existing['id'], $hasilLookBurt);
                } else {
                    $this->lookBurtModel->insert($hasilLookBurt);
                }
                $results['AnalisaLookBurt'] = "Perhitungan Analisa Look Burt berhasil";
            } else {
                $results['AnalisaLookBurt'] = "Perhitungan Analisa Look Burt gagal: Data tidak ditemukan";
                $allSuccess = false;
            }

        } catch (\Throwable $e) {
            log_message('error', "[HitungSemua] Exception global | ID={$pengukuran_id} | Error: " . $e->getMessage());
            return $this->respond([
                'status'  => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }

        // 🔹 Ambil tanggal
        $db = db_connect();
        $tanggalData = $db->table('t_data_pengukuran')
                          ->select('tanggal')
                          ->where('id', $pengukuran_id)
                          ->get()
                          ->getRowArray();
        $tanggal = $tanggalData['tanggal'] ?? null;

        // 🔹 Hapus nilai ambang
        if ($hasilLookBurt) {
            unset($hasilLookBurt['nilai_ambang_ok'], $hasilLookBurt['nilai_ambang_notok']);
        }

        log_message('debug', "[HitungSemua] SELESAI proses untuk ID={$pengukuran_id}");

        $response = [
            'status'        => $allSuccess ? 'success' : 'partial_error',
            'pengukuran_id' => $pengukuran_id,
            'tanggal'       => $tanggal,
            'messages'      => $results,
        ];

        if ($hasilLookBurt) {
            $response['data'] = [
                'rembesan_bendungan' => $hasilLookBurt['rembesan_bendungan'] ?? null,
                'rembesan_per_m'     => $hasilLookBurt['rembesan_per_m'] ?? null,
                'keterangan'         => $hasilLookBurt['keterangan'] ?? null,
            ];
        }

        return $this->respond($response);
    }

    public function hitungThomsonSemua()
    {
        $db = \Config\Database::connect();
        
        // Ambil semua pengukuran_id yang ada di t_data_pengukuran
        $pengukuranIds = $db->table('t_data_pengukuran')
                           ->select('id')
                           ->orderBy('id', 'ASC')
                           ->get()
                           ->getResultArray();

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        log_message('debug', "[HitungThomsonSemua] START proses Thomson untuk " . count($pengukuranIds) . " data");

        foreach ($pengukuranIds as $row) {
            $pengukuran_id = $row['id'];
            
            try {
                log_message('debug', "[HitungThomsonSemua] Memproses ID: {$pengukuran_id}");
                
                // Ambil data gabungan
                $dataGabungan = $this->dataGabunganModel->getDataById($pengukuran_id);
                
                if (!$dataGabungan) {
                    $errorCount++;
                    $results[$pengukuran_id] = 'ERROR: Data gabungan tidak ditemukan';
                    continue;
                }

                // Hitung Thomson
                $thomsonModel = new PerhitunganThomsonModel();
                $hasilThomson = $thomsonModel->hitung($pengukuran_id, $dataGabungan);

                if ($hasilThomson['success'] ?? false) {
                    $successCount++;
                    $results[$pengukuran_id] = 'SUCCESS';
                    log_message('debug', "[HitungThomsonSemua] ✅ Thomson berhasil untuk ID: {$pengukuran_id}");
                } else {
                    $errorCount++;
                    $results[$pengukuran_id] = 'ERROR: ' . ($hasilThomson['message'] ?? 'Tidak diketahui');
                    log_message('error', "[HitungThomsonSemua] ❌ Thomson gagal untuk ID: {$pengukuran_id}");
                }
                
            } catch (\Exception $e) {
                $errorCount++;
                $results[$pengukuran_id] = 'EXCEPTION: ' . $e->getMessage();
                log_message('error', "[HitungThomsonSemua] Exception untuk ID {$pengukuran_id}: " . $e->getMessage());
            }
        }

        log_message('debug', "[HitungThomsonSemua] SELESAI - Success: {$successCount}, Error: {$errorCount}");

        return $this->respond([
            'status' => 'completed',
            'total_data' => count($pengukuranIds),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'results' => $results
        ]);
    }

}