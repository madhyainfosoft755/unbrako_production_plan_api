<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\Seg3Model;

class Seg3Controller extends ResourceController
{
    protected $seg3Model;

    public function __construct()
    {
        // Load models in the constructor
        $this->seg3Model = new Seg3Model();
    }

    public function addSeg3(){
        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'created_by'      => auth()->user()->id
        ];

        // Validate the input data
        if (!$this->validate($this->seg3Model->validationRules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        // Save the name
        if ($this->seg3Model->insert($data)) {
            return $this->respond([
                'status' => true,
                'message' => 'Added successfully'
            ], 201); // HTTP 201 Created
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Failed to add'
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    public function getAllSeg3(){
        $seg3 = $this->seg3Model->select('id, name')->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'data' => $seg3
        ], 200); // HTTP 200 OK
    }
}
