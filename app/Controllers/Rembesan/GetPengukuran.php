<?php

namespace App\Controllers\Rembesan;

use CodeIgniter\Controller;
use Config\Database;

class GetPengukuran extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
        
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
    }

    public function index()
    {
        try {
            $query = $this->db->table("t_data_pengukuran")
                ->select("id, tanggal")
                ->orderBy("tanggal", "DESC")
                ->get();
            
            $data = $query->getResultArray();

            return $this->response->setJSON([
                "status" => "success",
                "data" => $data
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                "status" => "error",
                "message" => "Gagal mengambil data: " . $e->getMessage()
            ]);
        }
    }
}