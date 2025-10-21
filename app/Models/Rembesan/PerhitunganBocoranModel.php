<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;

class PerhitunganBocoranModel extends Model
{
    protected $table      = 'p_bocoran_baru';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'pengukuran_id',
        'talang1',
        'talang2',
        'pipa'
    ];

    protected $validationRules = [
        'pengukuran_id' => 'required|numeric|is_not_unique[t_data_pengukuran.id]',
        'talang1'       => 'permit_empty|numeric',
        'talang2'       => 'permit_empty|numeric',
        'pipa'          => 'permit_empty|numeric'
    ];

    protected $useTimestamps = true;

    /**
     * Hitung langsung data bocoran untuk satu pengukuran_id
     */
    public function hitungLangsung($bocoran)
    {
        if (!$bocoran) return false;

        helper('rembesan/rumus_bocoran');

        $talang1 = perhitunganQ_bocoran($bocoran['elv_624_t1'], $bocoran['elv_624_t1_kode']);
        $talang2 = perhitunganQ_bocoran($bocoran['elv_615_t2'], $bocoran['elv_615_t2_kode']);
        $pipa    = perhitunganQ_bocoran($bocoran['pipa_p1'], $bocoran['pipa_p1_kode']);

        $data = [
            'pengukuran_id' => $bocoran['pengukuran_id'],
            'talang1'       => $talang1,
            'talang2'       => $talang2,
            'pipa'          => $pipa
        ];

        $existing = $this->where('pengukuran_id', $bocoran['pengukuran_id'])->first();
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }

        return $data;
    }
}
