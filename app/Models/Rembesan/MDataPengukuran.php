<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

class MDataPengukuran extends Model
{
    protected $table = 't_data_pengukuran';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'tahun',
        'bulan',
        'periode',
        'tanggal',
        'tma_waduk',
        'curah_hujan',
        'temp_id'
    ];

    protected $validationRules = [
        'tahun'       => 'required|numeric',
        'bulan'       => 'permit_empty|string',
        'periode'     => 'permit_empty|string',
        'tanggal'     => 'required|valid_date',
        'tma_waduk'   => 'permit_empty|numeric',
        'curah_hujan' => 'permit_empty|numeric',
        'temp_id'     => 'permit_empty|string'
    ];

    protected $validationMessages = [
        'tahun' => [
            'required' => 'Tahun harus diisi',
            'numeric'  => 'Tahun harus berupa angka'
        ],
        'tanggal' => [
            'required'   => 'Tanggal harus diisi',
            'valid_date' => 'Format tanggal tidak valid'
        ]
    ];

    /**
     * Simpan atau update data pengukuran
     */
    public function simpanPengukuran(array $data, ?string $temp_id = null): array
    {
        $db = \Config\Database::connect();
        $get = fn($key) => (isset($data[$key]) && trim($data[$key]) !== '') ? $data[$key] : null;

        try {
            $pengukuran_id = $get('pengukuran_id');
            $tahun   = $get('tahun');
            $bulan   = $get('bulan');
            $periode = $get('periode');
            $tanggal = $get('tanggal');
            $tma     = $get('tma_waduk');
            $curah   = $get('curah_hujan');

            // STEP 1 → jika pengukuran_id dikirim → update TMA jika null
            if ($pengukuran_id) {
                $check = $this->find($pengukuran_id);
                if (!$check) {
                    return ['status' => 'error', 'message' => 'Data pengukuran tidak ditemukan!'];
                }

                if ($tma !== null) {
                    if ($check['tma_waduk'] === null) {
                        $this->update($pengukuran_id, ['tma_waduk' => $tma]);
                        return [
                            'status' => 'success',
                            'message' => 'TMA Waduk berhasil diperbarui.',
                            'pengukuran_id' => $pengukuran_id
                        ];
                    }
                    return [
                        'status' => 'info',
                        'message' => 'TMA Waduk sudah ada, tidak diperbarui.',
                        'pengukuran_id' => $pengukuran_id
                    ];
                }
                return ['status' => 'error', 'message' => 'Tidak ada nilai TMA yang dikirim!'];
            }

            // STEP 2 → jika belum ada ID, wajib tahun & tanggal
            if (!$tahun || !$tanggal) {
                return ['status' => 'error', 'message' => 'Tahun dan Tanggal wajib diisi!'];
            }

            // normalisasi periode
            if ($periode && !preg_match('/^TW-/i', $periode)) {
                if (is_numeric($periode)) {
                    $periode = 'TW-' . $periode;
                }
            }

            // STEP 3 → cek duplikasi berdasarkan tahun & tanggal
            $check = $db->table($this->table)
                ->where('tahun', $tahun)
                ->where('tanggal', $tanggal)
                ->get()
                ->getRow();

            if ($check) {
                if ($tma !== null) {
                    if ($check->tma_waduk === null) {
                        $db->table($this->table)
                           ->where('id', $check->id)
                           ->update(['tma_waduk' => $tma]);

                        return [
                            'status' => 'success',
                            'message' => 'TMA Waduk berhasil diperbarui.',
                            'pengukuran_id' => $check->id
                        ];
                    }
                    return [
                        'status' => 'info',
                        'message' => 'TMA Waduk sudah ada, tidak diperbarui.',
                        'pengukuran_id' => $check->id
                    ];
                }

                return [
                    'status' => 'info',
                    'message' => 'Data pengukuran sudah ada.',
                    'pengukuran_id' => $check->id
                ];
            }

            // STEP 4 → insert baru
            $insertData = [
                'tahun'       => $tahun,
                'bulan'       => $bulan,
                'periode'     => $periode,
                'tanggal'     => $tanggal,
                'tma_waduk'   => $tma,
                'curah_hujan' => $curah,
                'temp_id'     => $temp_id
            ];

            $db->transStart();
            if (!$this->insert($insertData)) {
                $db->transRollback();
                return [
                    'status' => 'error',
                    'message' => 'Gagal menyimpan data pengukuran: ' .
                                 implode(', ', $this->errors())
                ];
            }

            $pengukuran_id = $this->getInsertID();
            Events::trigger('dataPengukuran:insert', $pengukuran_id);
            $db->transComplete();

            return [
                'status' => 'success',
                'message' => 'Data pengukuran berhasil disimpan.',
                'pengukuran_id' => $pengukuran_id
            ];

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[MDataPengukuran::simpanPengukuran] ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }
    }
}
