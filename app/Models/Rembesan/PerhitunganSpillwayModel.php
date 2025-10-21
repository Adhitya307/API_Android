<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;

class PerhitunganSpillwayModel extends Model
{
    protected $table = 'p_spillway';
    protected $primaryKey = 'id';
    protected $allowedFields = ['pengukuran_id', 'b3', 'ambang', 'created_at', 'updated_at'];
    
    protected $validationRules = [
        'pengukuran_id' => 'required|numeric|is_not_unique[t_data_pengukuran.id]',
        'b3' => 'permit_empty|numeric',
        'ambang' => 'permit_empty|numeric'
    ];
    
    protected $validationMessages = [
        'pengukuran_id' => [
            'required' => 'pengukuran_id harus diisi',
            'numeric' => 'pengukuran_id harus berupa angka',
            'is_not_unique' => 'Data pengukuran dengan ID {value} tidak ditemukan'
        ]
    ];
    
    protected $useTimestamps = true;

    /**
     * Proses hitung spillway
     */
    public function proses($pengukuranId)
    {
        helper('rembesan/rumus_spillway'); // panggil helper

        $db = \Config\Database::connect();

        // 1) Ambil data pengukuran (TMA Waduk)
        $pengukuran = $db->table('t_data_pengukuran')
            ->select('id, tma_waduk')
            ->where('id', $pengukuranId)
            ->get()
            ->getRowArray();

        if (!$pengukuran) {
            log_message('error', "[Spillway] ❌ Data pengukuran tidak ditemukan untuk ID {$pengukuranId}");
            return false;
        }
        $tma = (float) $pengukuran['tma_waduk'];

        // 2) Ambil data Thomson (B1, B3)
        $thomson = $db->table('p_thomson_weir')
            ->select('b1, b3')
            ->where('pengukuran_id', $pengukuranId)
            ->get()
            ->getRowArray();

        if (!$thomson) {
            log_message('error', "[Spillway] ❌ Data Thomson tidak ditemukan untuk pengukuran_id={$pengukuranId}");
            return false;
        }

        $B1        = (float) ($thomson['b1'] ?? 0);
        $B3Thomson = (float) ($thomson['b3'] ?? 0);

        // 3) Hitung Spillway (B3 final)
        $B3Final = hitungSpillway($B1, $B3Thomson);

        // 4) Ambil ambang spillway dari Excel
        $filePath = FCPATH . 'assets/excel/tabel_ambang.xlsx';
        if (!is_file($filePath)) {
            log_message('error', "[Spillway] ❌ File Excel tidak ditemukan di {$filePath}");
            return false;
        }

        $spillwayData = loadAmbangSpillway($filePath, 'spillway');
        $ambang       = cariAmbangSpillway($tma, $spillwayData);

        if ($ambang === null) {
            log_message('error', "[Spillway] ❌ Ambang spillway tidak ditemukan untuk TMA {$tma}");
            return false;
        }

        // 5) Siapkan hasil untuk DB
        $hasil = [
            'pengukuran_id' => $pengukuranId,
            'b3'            => $B3Final,
            'ambang'        => $ambang,
        ];

        // 6) Insert/update DB
        $exists = $this->where('pengukuran_id', $pengukuranId)->first();
        if ($exists) {
            $this->update($exists['id'], $hasil);
            log_message('debug', "[Spillway] 🔄 Update DB untuk ID {$pengukuranId}");
        } else {
            $this->insert($hasil);
            log_message('debug', "[Spillway] ✅ Insert DB untuk ID {$pengukuranId}");
        }

        log_message(
            'debug',
            "[Spillway] ✅ Proses selesai | ID={$pengukuranId}, B1={$B1}, B3Thomson={$B3Thomson}, B3Final={$B3Final}, ambang={$ambang}"
        );

        return $hasil;
    }
}
