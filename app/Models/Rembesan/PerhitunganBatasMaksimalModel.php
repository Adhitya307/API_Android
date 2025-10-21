<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PerhitunganBatasMaksimalModel extends Model
{
    protected $table         = 'p_batasmaksimal';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['pengukuran_id', 'batas_maksimal'];
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $batasData = [];

    public function __construct()
    {
        parent::__construct();
        helper('Rembesan/BatasMaksimalHelper');
    }

    /**
     * Load data batas maksimal dari file Excel
     *
     * @param string|null $filePath
     * @return array ['TMA' => 'batas maksimal']
     */
    public function loadFromExcel($filePath = null)
    {
        if (!$filePath) {
            $filePath = FCPATH . 'assets/excel/tabel_ambang.xlsx';
        }

        if (!file_exists($filePath)) {
            log_message('error', "[PerhitunganBatasMaksimalModel] File Excel tidak ditemukan: $filePath");
            return [];
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet       = $spreadsheet->getActiveSheet();
            $this->batasData = loadBatasMaksimal($sheet);
        } catch (\Exception $e) {
            log_message('error', "[PerhitunganBatasMaksimalModel] Gagal load Excel: " . $e->getMessage());
            return [];
        }

        return $this->batasData;
    }

    /**
     * Cari batas maksimal untuk TMA tertentu
     *
     * @param float $tma
     * @return float|null
     */
    public function getBatasMaksimal($tma)
    {
        if (empty($this->batasData)) {
            $this->loadFromExcel();
        }

        return cariBatasMaksimal($tma, $this->batasData);
    }

    /**
     * Hitung & simpan batas maksimal berdasarkan pengukuran_id
     *
     * @param int $pengukuran_id
     * @return array|null ['tma' => float, 'batas' => float]
     */
    public function hitungBatas($pengukuran_id)
    {
        $db = \Config\Database::connect();
        $query = $db->table('t_data_pengukuran')
                    ->select('id, tma_waduk')
                    ->where('id', $pengukuran_id)
                    ->get()
                    ->getRowArray();

        if (!$query || !isset($query['tma_waduk'])) {
            log_message('debug', "[PerhitunganBatasMaksimalModel] TMA untuk pengukuran_id={$pengukuran_id} tidak ditemukan");
            return null;
        }

        $tmaWaduk = (float) $query['tma_waduk'];
        $batas    = $this->getBatasMaksimal($tmaWaduk);

        // Insert atau update ke DB
        $dataInsert = [
            'pengukuran_id'  => $pengukuran_id,
            'batas_maksimal' => $batas
        ];

        $existing = $this->where('pengukuran_id', $pengukuran_id)->first();
        if ($existing) {
            $this->update($existing['id'], $dataInsert);
            log_message('debug', "[PerhitunganBatasMaksimalModel] Update DB batas maksimal untuk ID: $pengukuran_id");
        } else {
            $this->insert($dataInsert);
            log_message('debug', "[PerhitunganBatasMaksimalModel] Insert DB batas maksimal untuk ID: $pengukuran_id");
        }

        return [
            'tma'   => $tmaWaduk,
            'batas' => $batas
        ];
    }
}
