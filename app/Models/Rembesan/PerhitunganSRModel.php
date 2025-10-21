<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;

class PerhitunganSRModel extends Model
{
    protected $table = 'p_sr';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'pengukuran_id',
        'sr_1_q', 'sr_40_q', 'sr_66_q', 'sr_68_q', 'sr_70_q',
        'sr_79_q', 'sr_81_q', 'sr_83_q', 'sr_85_q', 'sr_92_q',
        'sr_94_q', 'sr_96_q', 'sr_98_q', 'sr_100_q', 'sr_102_q',
        'sr_104_q', 'sr_106_q',
        'created_at', 'updated_at'
    ];

    protected $useTimestamps = true;

    /**
     * Logika hitung SR dipindahkan ke sini
     */
    public function hitung($pengukuran_id, array $dataMentah)
    {
        helper('rumus/sr');

        $fields = [
            'sr_1', 'sr_40', 'sr_66', 'sr_68', 'sr_70',
            'sr_79', 'sr_81', 'sr_83', 'sr_85',
            'sr_92', 'sr_94', 'sr_96', 'sr_98',
            'sr_100', 'sr_102', 'sr_104', 'sr_106'
        ];

        $hasil = ['pengukuran_id' => $pengukuran_id];

        foreach ($fields as $f) {
            $kode = $dataMentah[$f . '_kode'] ?? null;
            $nilai = $dataMentah[$f . '_nilai'] ?? null;
            $hasil[$f . '_q'] = perhitunganQ_sr($nilai, $kode);
        }

        $existing = $this->where('pengukuran_id', $pengukuran_id)->first();

        if ($existing) {
            $this->update($existing['id'], $hasil);
        } else {
            $this->insert($hasil);
        }

        return $hasil;
    }
}
