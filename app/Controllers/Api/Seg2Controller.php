<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\Seg2Model;

class Seg2Controller extends ResourceController
{
    protected $seg2Model;

    public function __construct()
    {
        // Load models in the constructor
        $this->seg2Model = new Seg2Model();
    }

    public function addSeg2(){
        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'created_by'      => auth()->user()->id
        ];

        // Validate the input data
        if (!$this->validate($this->seg2Model->validationRules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        // Save the roles
        if ($this->seg2Model->insert($data)) {
            return $this->respond([
                'status' => true,
                'message' => 'added successfully'
            ], 201); // HTTP 201 Created
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Failed to add'
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    public function getAllSeg2(){
        $seg2 = $this->seg2Model->select('id, name, created_at')->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'data' => $seg2
        ], 200); // HTTP 200 OK
    }
}
