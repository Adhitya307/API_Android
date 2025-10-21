<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;
use App\Helpers\Rembesan\AnalisaLookBurtHelper;

class AnalisaLookBurtModel extends Model
{
    protected $table         = 'analisa_look_burt';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'pengukuran_id',
        'rembesan_bendungan',
        'panjang_bendungan',
        'rembesan_per_m',
        'nilai_ambang_ok',
        'nilai_ambang_notok',
        'keterangan'
    ];

    protected $returnType    = 'array';

    /**
     * Ambil data dengan join ke t_data_pengukuran
     */
    public function getAll()
    {
        return $this->select('analisa_look_burt.*, t_data_pengukuran.tanggal, t_data_pengukuran.tma_waduk')
                    ->join('t_data_pengukuran', 't_data_pengukuran.id = analisa_look_burt.pengukuran_id', 'left')
                    ->orderBy('t_data_pengukuran.tanggal', 'ASC')
                    ->findAll();
    }

    /**
     * Hitung semua data Analisa Look Burt
     */
    public function prosesSemua()
    {
        $helper = new AnalisaLookBurtHelper(); // buat instance helper
        $intiGaleryModel = new PerhitunganIntiGaleryModel();
        $list = $intiGaleryModel->findAll();
        $hasilAll = [];

        foreach ($list as $row) {
            $pengukuran_id = $row['pengukuran_id'] ?? null;
            if (!$pengukuran_id) continue;

            $hasil = $helper->hitungLookBurt($pengukuran_id); // panggil method non-static
            if (!$hasil) continue;

            $existing = $this->where('pengukuran_id', $pengukuran_id)->first();
            if ($existing) {
                $this->update($existing['id'], $hasil);
            } else {
                $this->insert($hasil);
            }

            $hasilAll[] = $hasil;
        }

        return $hasilAll;
    }

    /**
     * Hitung satu data Analisa Look Burt berdasarkan pengukuran_id
     */
    public function prosesSatu($pengukuran_id)
    {
        $helper = new AnalisaLookBurtHelper(); // buat instance helper
        $hasil = $helper->hitungLookBurt($pengukuran_id); // panggil method non-static
        if (!$hasil) return false;

        $existing = $this->where('pengukuran_id', $pengukuran_id)->first();
        if ($existing) {
            $this->update($existing['id'], $hasil);
        } else {
            $this->insert($hasil);
        }

        return $hasil;
    }
}
