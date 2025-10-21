<?php

namespace App\Models\Rembesan;

use CodeIgniter\Model;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PerhitunganThomsonModel extends Model
{
    protected $table            = 'p_thomson_weir';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'pengukuran_id',
        'a1_r',
        'a1_l',
        'b1',
        'b3',
        'b5'
    ];

    protected $validationRules = [
        'pengukuran_id' => 'required|numeric|is_not_unique[t_data_pengukuran.id]',
        'a1_r'          => 'permit_empty|numeric',
        'a1_l'          => 'permit_empty|numeric',
        'b1'            => 'permit_empty|numeric',
        'b3'            => 'permit_empty|numeric',
        'b5'            => 'permit_empty|numeric'
    ];

    protected $useTimestamps = false;

    /**
     * Hitung Thomson untuk pengukuran_id tertentu
     */
    public function hitung($pengukuran_id, $dataGabungan)
    {
        helper(['Rembesan/thomson']);

        log_message('debug', "[ThomsonModel] Mulai perhitungan untuk ID: {$pengukuran_id}");

        if (empty($dataGabungan['thomson']) || !is_array($dataGabungan['thomson'])) {
            log_message('error', "[ThomsonModel] Data Thomson kosong/tidak valid untuk ID: {$pengukuran_id}");
            return ['success' => false, 'message' => "Data Thomson kosong/tidak valid untuk ID: {$pengukuran_id}"];
        }

        // 🔹 Load Excel
        $thomsonPath = FCPATH . 'assets/excel/tabel_thomson.xlsx';
        if (!file_exists($thomsonPath)) {
            log_message('error', "[ThomsonModel] File Excel Thomson tidak ditemukan: {$thomsonPath}");
            return ['success' => false, 'message' => "File Excel Thomson tidak ditemukan: {$thomsonPath}"];
        }

        try {
            $spreadsheet = IOFactory::load($thomsonPath);
            $sheet = $spreadsheet->getSheetByName('Tabel Thomson');
            if (!$sheet) {
                log_message('error', "[ThomsonModel] Sheet 'Tabel Thomson' tidak ditemukan");
                return ['success' => false, 'message' => "Sheet 'Tabel Thomson' tidak ditemukan"];
            }
        } catch (\Exception $e) {
            log_message('error', "[ThomsonModel] Error load Excel Thomson: " . $e->getMessage());
            return ['success' => false, 'message' => "Error load Excel Thomson: " . $e->getMessage()];
        }

        // 🔹 Hitung pakai helper
        $thomson = [
            'r'  => !empty($dataGabungan['thomson']['a1_r']) ? perhitunganQ_thomson($dataGabungan['thomson']['a1_r'], $sheet) : 0,
            'l'  => !empty($dataGabungan['thomson']['a1_l']) ? perhitunganQ_thomson($dataGabungan['thomson']['a1_l'], $sheet) : 0,
            'b1' => !empty($dataGabungan['thomson']['b1'])   ? perhitunganQ_thomson($dataGabungan['thomson']['b1'], $sheet) : 0,
            'b3' => !empty($dataGabungan['thomson']['b3'])   ? perhitunganQ_thomson($dataGabungan['thomson']['b3'], $sheet) : 0,
            'b5' => !empty($dataGabungan['thomson']['b5'])   ? perhitunganQ_thomson($dataGabungan['thomson']['b5'], $sheet) : 0,
        ];

        foreach ($thomson as $k => $v) {
            if ($v === null) $thomson[$k] = 0;
        }

        log_message('debug', "[ThomsonModel] Hasil perhitungan: " . json_encode($thomson));

        $dataThomson = [
            'pengukuran_id' => $pengukuran_id,
            'a1_r' => $thomson['r'],
            'a1_l' => $thomson['l'],
            'b1'   => $thomson['b1'],
            'b3'   => $thomson['b3'],
            'b5'   => $thomson['b5'],
        ];

        try {
            $cek = $this->where('pengukuran_id', $pengukuran_id)->first();
            if ($cek) {
                $this->update($cek['id'], $dataThomson);
                log_message('debug', "[ThomsonModel] Data diupdate untuk ID: {$pengukuran_id}");
            } else {
                $this->insert($dataThomson);
                log_message('debug', "[ThomsonModel] Data diinsert untuk ID: {$pengukuran_id}");
            }
        } catch (\Exception $e) {
            log_message('error', "[ThomsonModel] Error simpan ke DB: " . $e->getMessage());
            return ['success' => false, 'message' => "Error simpan ke DB: " . $e->getMessage()];
        }

        log_message('debug', "[ThomsonModel] Perhitungan selesai untuk ID: {$pengukuran_id}");

        return [
            'success' => true,
            'message' => "Perhitungan Thomson selesai untuk ID {$pengukuran_id}",
            'thomson' => $dataThomson
        ];
    }
}