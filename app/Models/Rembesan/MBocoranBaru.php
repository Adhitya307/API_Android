<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

class MBocoranBaru extends Model
{
    protected $table = 't_bocoran_baru';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'pengukuran_id',
        'elv_624_t1', 'elv_624_t1_kode',
        'elv_615_t2', 'elv_615_t2_kode',
        'pipa_p1', 'pipa_p1_kode'
    ];
    
    protected $validationRules = [
        'pengukuran_id' => 'required|numeric|is_not_unique[t_data_pengukuran.id]',
        'elv_624_t1'    => 'permit_empty|numeric',
        'elv_615_t2'    => 'permit_empty|numeric',
        'pipa_p1'       => 'permit_empty|numeric'
    ];
    
    protected $validationMessages = [
        'pengukuran_id' => [
            'required'     => 'pengukuran_id harus diisi',
            'numeric'      => 'pengukuran_id harus berupa angka',
            'is_not_unique'=> 'Data pengukuran dengan ID {value} tidak ditemukan'
        ]
    ];

    /**
     * Simpan data bocoran baru ke tabel t_bocoran_baru
     */
    public function simpanBocoran(array $data, int $pengukuran_id): array
    {
        $db = \Config\Database::connect();

        // Ambil nilai dan konversi "" menjadi NULL
        $get = function ($key) use ($data) {
            if (!isset($data[$key])) return null;
            $val = trim($data[$key]);
            return ($val === '' || strtolower($val) === 'null') ? null : $val;
        };

        // Bersihkan teks dalam tanda kurung, misal "s (200ml)" → "s"
        $clean = function ($value) {
            if ($value === null) return null;
            return trim(preg_replace('/\s*\(.*?\)\s*/', '', $value));
        };

        try {
            // Cek apakah sudah ada data bocoran untuk pengukuran ini
            $check = $this->where('pengukuran_id', $pengukuran_id)->first();
            if ($check) {
                return [
                    'status'  => 'success',
                    'message' => 'Data bocoran sudah ada.'
                ];
            }

            // Siapkan data untuk disimpan
            $bocoranData = [
                'pengukuran_id'    => $pengukuran_id,
                'elv_624_t1'       => $get('elv_624_t1'),
                'elv_624_t1_kode'  => $clean($get('elv_624_t1_kode')),
                'elv_615_t2'       => $get('elv_615_t2'),
                'elv_615_t2_kode'  => $clean($get('elv_615_t2_kode')),
                'pipa_p1'          => $get('pipa_p1'),
                'pipa_p1_kode'     => $clean($get('pipa_p1_kode'))
            ];

            // ✅ Tidak perlu semua kolom diisi — 1 pun boleh

            $db->transStart();
            if (!$this->insert($bocoranData)) {
                $db->transRollback();
                return [
                    'status'  => 'error',
                    'message' => 'Gagal menyimpan data bocoran: ' .
                                 implode(', ', $this->errors())
                ];
            }
            $db->transComplete();

            Events::trigger('dataPengukuran:insert', $pengukuran_id);

            return [
                'status'  => 'success',
                'message' => 'Data bocoran berhasil disimpan.'
            ];

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[MBocoranBaru::simpanBocoran] ' . $e->getMessage());
            return [
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data bocoran: ' . $e->getMessage()
            ];
        }
    }
}
