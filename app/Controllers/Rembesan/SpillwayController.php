<?php
namespace App\Controllers\Rembesan;

use App\Controllers\BaseController;
use App\Models\Rembesan\PerhitunganSpillwayModel;

class SpillwayController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new PerhitunganSpillwayModel();
    }

    /**
     * Proses spillway, panggil dari controller tetap sama
     */
    public function proses($pengukuranId)
    {
        return $this->model->proses($pengukuranId);
    }
}
