<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;

class TebingKananModel extends Model
{
    protected $table      = 'p_tebingkanan';
    protected $primaryKey = 'id';
    protected $allowedFields = ['pengukuran_id', 'sr', 'ambang', 'B5', 'created_at', 'updated_at'];
    
    protected $validationRules = [
        'pengukuran_id' => 'required|numeric|is_not_unique[t_data_pengukuran.id]',
        'sr'            => 'permit_empty|numeric',
        'ambang'        => 'permit_empty|numeric',
        'B5'            => 'permit_empty|numeric'
    ];
    
    protected $validationMessages = [
        'pengukuran_id' => [
            'required'       => 'pengukuran_id harus diisi',
            'numeric'        => 'pengukuran_id harus berupa angka',
            'is_not_unique'  => 'Data pengukuran dengan ID {value} tidak ditemukan'
        ]
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function proses($pengukuranId)
    {
        helper(['rembesan/rumus_tebingkanan', 'rembesan/rumus_sr']);

        $db = \Config\Database::connect();

        // 1) Ambil data pengukuran
        $pengukuran = $db->table('t_data_pengukuran')
            ->select('id, tma_waduk')
            ->where('id', $pengukuranId)
            ->get()
            ->getRowArray();

        if (!$pengukuran) return false;
        $tma = (float) $pengukuran['tma_waduk'];

        // 2) Ambil data SR
        $dataSr = $db->table('p_sr')
            ->where('pengukuran_id', $pengukuranId)
            ->get()
            ->getRowArray();
        if (!$dataSr) return false;

        // 3) Daftar SR yang dipakai khusus Tebing Kanan
        $srFields = [
            1,40,66,67,68,69,70,71,72,73,74,75,76,77,78,
            79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,
            94,95,96,97,98,99,100,101,102,103,104,105,106
        ];

        // 4) Hitung total SR Tebing Kanan
        $srTotal = hitungSrTebingKanan($dataSr, $srFields);

        // 5) Ambang dari Excel
        $filePath = FCPATH . 'assets/excel/tabel_ambang.xlsx';
        if (!is_file($filePath)) return false;

        $ambangData = getAmbangTebingKanan($filePath, 'AMBANG TIAP CM');
        $ambang     = cariAmbangTebingKanan($tma, $ambangData);
        if ($ambang === null) return false;

        // 6) Ambil nilai B5
        $thomsonRow = $db->table('p_thomson_weir')
            ->select('B5')
            ->where('pengukuran_id', $pengukuranId)
            ->get()
            ->getRowArray();
        $B5 = (float) ($thomsonRow['B5'] ?? 0);

        // 7) Insert/update DB
        $data = [
            'pengukuran_id' => $pengukuranId,
            'sr'            => $srTotal,
            'ambang'        => $ambang,
            'B5'            => $B5
        ];

        $existing = $this->where('pengukuran_id', $pengukuranId)->first();
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }

        return $data;
    }
}
