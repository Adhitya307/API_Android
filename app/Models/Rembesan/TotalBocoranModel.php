<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;

class TotalBocoranModel extends Model
{
    protected $table      = 'p_totalbocoran';
    protected $primaryKey = 'id';
    protected $allowedFields = ['pengukuran_id', 'R1', 'created_at', 'updated_at'];
    
    protected $validationRules = [
        'pengukuran_id' => 'required|numeric|is_not_unique[t_data_pengukuran.id]',
        'R1'            => 'permit_empty|numeric'
    ];
    
    protected $validationMessages = [
        'pengukuran_id' => [
            'required'      => 'pengukuran_id harus diisi',
            'numeric'       => 'pengukuran_id harus berupa angka',
            'is_not_unique' => 'Data pengukuran dengan ID {value} tidak ditemukan'
        ]
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Proses perhitungan Total Bocoran (R1)
     */
    public function proses($pengukuran_id)
    {
        helper('totalbocoran'); // pastikan helper tersedia

        $db = \Config\Database::connect();

        // Ambil data A1
        $a1Row = $db->table('p_intigalery')->select('a1')
                    ->where('pengukuran_id', $pengukuran_id)
                    ->get()->getRow();

        // Ambil data B3
        $b3Row = $db->table('p_spillway')->select('b3')
                    ->where('pengukuran_id', $pengukuran_id)
                    ->get()->getRow();

        // Ambil data SR
        $srRow = $db->table('p_tebingkanan')->select('sr')
                    ->where('pengukuran_id', $pengukuran_id)
                    ->get()->getRow();

        if (!$a1Row || !$b3Row || !$srRow) return false;

        $a1 = (float) $a1Row->a1;
        $b3 = (float) $b3Row->b3;
        $sr = (float) $srRow->sr;

        // Hitung total bocoran
        $R1 = hitungTotalBocoran($a1, $b3, $sr);

        // Insert atau update DB
        $existing = $this->where('pengukuran_id', $pengukuran_id)->first();

        if ($existing) {
            $this->update($existing['id'], ['R1' => $R1]);
        } else {
            $this->insert([
                'pengukuran_id' => $pengukuran_id,
                'R1'            => $R1
            ]);
        }

        return [
            'pengukuran_id' => $pengukuran_id,
            'R1'            => $R1
        ];
    }
}
