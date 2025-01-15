<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\ShiftModel;

class ShiftController extends ResourceController
{
    protected $shiftModel;
    protected $format    = 'json';

    public function __construct()
    {
        $this->shiftModel = new ShiftModel();
    }

    public function getAllShift()
    {
        $data = $this->shiftModel->select('number, from_time, to_time')
            ->orderBy('number', 'ASC')
            ->findAll();

        return $this->respond([
            'data' => $data
        ], 200); // HTTP 200 OK
    }

    
}
