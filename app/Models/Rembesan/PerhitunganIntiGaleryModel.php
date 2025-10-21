<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;

class PerhitunganIntiGaleryModel extends Model
{
    protected $table      = 'p_intigalery';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'pengukuran_id',
        'a1',
        'ambang_a1',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'pengukuran_id' => 'required|numeric|is_not_unique[t_data_pengukuran.id]',
        'a1'            => 'permit_empty|numeric',
        'ambang_a1'     => 'permit_empty|numeric'
    ];

    protected $validationMessages = [
        'pengukuran_id' => [
            'required'     => 'pengukuran_id harus diisi',
            'numeric'      => 'pengukuran_id harus berupa angka',
            'is_not_unique'=> 'Data pengukuran dengan ID {value} tidak ditemukan'
        ]
    ];

    /**
     * Proses hitung Inti Galery untuk 1 pengukuran
     */
    public function proses($pengukuranId)
    {
        helper('rembesan/rumus_intigalery'); // panggil helper

        log_message('debug', "[IntiGalery] ▶️ Mulai proses untuk ID {$pengukuranId}");

        $hasil = hitungIntiGalery((int) $pengukuranId);

        if ($hasil === false) {
            log_message('error', "[IntiGalery] ❌ Gagal hitung untuk ID {$pengukuranId}");
            return false;
        }

        // Tambahkan pengukuran_id
        $hasil['pengukuran_id'] = $pengukuranId;

        // Insert atau update DB
        $existing = $this->where('pengukuran_id', $pengukuranId)->first();
        if ($existing) {
            $this->update($existing['id'], $hasil);
            log_message('debug', "[IntiGalery] 🔄 Update DB untuk pengukuran_id={$pengukuranId} | Data=" . json_encode($hasil));
        } else {
            $this->insert($hasil);
            log_message('debug', "[IntiGalery] 🆕 Insert DB untuk pengukuran_id={$pengukuranId} | Data=" . json_encode($hasil));
        }

        log_message('debug', "[IntiGalery] ✅ Proses selesai untuk ID {$pengukuranId}");

        return $hasil;
    }
}
