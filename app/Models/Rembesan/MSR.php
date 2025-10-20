<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

class MSR extends Model
{
    protected $table = 't_sr';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'pengukuran_id',
        'sr_1_kode', 'sr_1_nilai', 'sr_40_kode', 'sr_40_nilai', 'sr_66_kode', 'sr_66_nilai',
        'sr_68_kode', 'sr_68_nilai', 'sr_70_kode', 'sr_70_nilai', 'sr_79_kode', 'sr_79_nilai',
        'sr_81_kode', 'sr_81_nilai', 'sr_83_kode', 'sr_83_nilai', 'sr_85_kode', 'sr_85_nilai',
        'sr_92_kode', 'sr_92_nilai', 'sr_94_kode', 'sr_94_nilai', 'sr_96_kode', 'sr_96_nilai',
        'sr_98_kode', 'sr_98_nilai', 'sr_100_kode', 'sr_100_nilai', 'sr_102_kode', 'sr_102_nilai',
        'sr_104_kode', 'sr_104_nilai', 'sr_106_kode', 'sr_106_nilai'
    ];
    
    protected $validationRules = [
        'pengukuran_id' => 'required|numeric|is_not_unique[t_data_pengukuran.id]',
        'sr_1_nilai' => 'permit_empty|numeric',
        'sr_40_nilai' => 'permit_empty|numeric',
        'sr_66_nilai' => 'permit_empty|numeric',
        'sr_68_nilai' => 'permit_empty|numeric',
        'sr_70_nilai' => 'permit_empty|numeric',
        'sr_79_nilai' => 'permit_empty|numeric',
        'sr_81_nilai' => 'permit_empty|numeric',
        'sr_83_nilai' => 'permit_empty|numeric',
        'sr_85_nilai' => 'permit_empty|numeric',
        'sr_92_nilai' => 'permit_empty|numeric',
        'sr_94_nilai' => 'permit_empty|numeric',
        'sr_96_nilai' => 'permit_empty|numeric',
        'sr_98_nilai' => 'permit_empty|numeric',
        'sr_100_nilai' => 'permit_empty|numeric',
        'sr_102_nilai' => 'permit_empty|numeric',
        'sr_104_nilai' => 'permit_empty|numeric',
        'sr_106_nilai' => 'permit_empty|numeric'
    ];
    
    protected $validationMessages = [
        'pengukuran_id' => [
            'required' => 'pengukuran_id harus diisi',
            'numeric' => 'pengukuran_id harus berupa angka',
            'is_not_unique' => 'Data pengukuran dengan ID {value} tidak ditemukan'
        ]
    ];

    /**
     * Simpan data SR (seepage rate) ke tabel t_sr
     */
    public function simpanSR(array $data, int $pengukuran_id): array
    {
        $db = \Config\Database::connect();
        $get = fn($key) => (isset($data[$key]) && trim($data[$key]) !== '') ? $data[$key] : null;

        try {
            // Cek apakah sudah ada data SR untuk pengukuran_id ini
            $check = $this->where('pengukuran_id', $pengukuran_id)->first();
            if ($check) {
                return [
                    'status' => 'success',
                    'message' => 'Data SR sudah ada.'
                ];
            }

            $fields = [1,40,66,68,70,79,81,83,85,92,94,96,98,100,102,104,106];
            $insertData = ['pengukuran_id' => $pengukuran_id];

            $filledFields = [];
            $emptyFields  = [];

            foreach ($fields as $kode) {
                $kodeKey  = "sr_{$kode}_kode";
                $nilaiKey = "sr_{$kode}_nilai";

                $valKode  = $get($kodeKey);
                $valNilai = $get($nilaiKey);

                $isFilled = false;

                if ($valKode !== null && $valKode !== '') {
                    $insertData[$kodeKey] = $valKode;
                    $isFilled = true;
                }

                if ($valNilai !== null && $valNilai !== '' && is_numeric($valNilai)) {
                    $insertData[$nilaiKey] = floatval($valNilai);
                    $isFilled = true;
                }

                if ($isFilled) {
                    $filledFields[] = "SR {$kode}";
                } else {
                    $emptyFields[] = "SR {$kode}";
                }
            }

            // Tidak ada data sama sekali
            if (count($filledFields) === 0) {
                return [
                    'status' => 'warning',
                    'message' => 'Tidak ada data yang diinput.',
                    'filled' => $filledFields,
                    'empty'  => $emptyFields
                ];
            }

            // Konfirmasi sebelum simpan
            if (!isset($data['confirm']) || $data['confirm'] != 'yes') {
                return [
                    'status' => 'confirm',
                    'message' => 'Apakah Anda yakin ingin menyimpan data berikut?',
                    'filled' => $filledFields,
                    'empty'  => $emptyFields
                ];
            }

            // Simpan data jika sudah dikonfirmasi
            $db->transStart();
            if (!$this->insert($insertData)) {
                $db->transRollback();
                return [
                    'status' => 'error',
                    'message' => 'Gagal menyimpan data SR: ' .
                                 implode(', ', $this->errors())
                ];
            }

            Events::trigger('dataSR:insert', $pengukuran_id);
            $db->transComplete();

            return [
                'status' => 'success',
                'message' => 'Data SR berhasil disimpan.'
            ];
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[MSR::simpanSR] ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data SR: ' . $e->getMessage()
            ];
        }
    }
}
