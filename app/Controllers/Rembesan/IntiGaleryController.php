<?php
namespace App\Controllers\Rembesan;

use App\Controllers\BaseController;
use App\Models\Rembesan\PerhitunganIntiGaleryModel;

class IntiGaleryController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new PerhitunganIntiGaleryModel();
    }

    /**
     * Proses Inti Galery
     */
    public function proses($pengukuranId)
    {
        return $this->model->proses($pengukuranId);
    }
}
