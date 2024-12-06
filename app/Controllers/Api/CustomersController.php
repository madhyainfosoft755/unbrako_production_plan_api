<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\CustomersModel;

class CustomersController extends ResourceController
{
    protected $CustomersModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->CustomersModel = new CustomersModel();
    }

    public function addCustomer(){
        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'created_by'      => auth()->user()->id
        ];

        // Validate the input data
        if (!$this->validate($this->CustomersModel->validationRules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        // Save the name
        if ($this->CustomersModel->insert($data)) {
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

    public function getAllCustomers(){
        $customers = $this->CustomersModel->select('id, name')->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'data' => $customers
        ], 200); // HTTP 200 OK
    }
}
