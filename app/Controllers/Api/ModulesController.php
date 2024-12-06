<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\ModulesModel;

class ModulesController extends ResourceController
{
    protected $modulesModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->modulesModel = new ModulesModel();
    }

    public function addModule(){
        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'created_by'      => auth()->user()->id
        ];

        // Validate the input data
        if (!$this->validate($this->modulesModel->validationRules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        // Save the name
        if ($this->modulesModel->insert($data)) {
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

    public function getAllModules(){
        $modules = $this->modulesModel->select('id, name')->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'data' => $modules
        ], 200); // HTTP 200 OK
    }
}
