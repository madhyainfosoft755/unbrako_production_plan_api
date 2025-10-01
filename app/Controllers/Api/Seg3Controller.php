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

    public function editSeg3($id){
        // Find existing record
        $existing = $this->seg3Model->find($id);

        if (!$existing) {
            return $this->respond([
                'status' => false,
                'message' => 'Seg3 record not found'
            ], 404); // HTTP 404 Not Found
        }


        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'updated_by'  => auth()->user()->id,
            'updated_at'  => date('Y-m-d H:i:s') // Or use Time::now() if using CI4's Time class
        ];

        // Dynamic validation for name uniqueness (ignore current ID)
        $rules = $this->seg3Model->getValidationRules();
        
        if (isset($data['name'])) {
            // required|string|max_length[50]|is_unique[seg_3.name]
            $rules['name'] = 'required|string|max_length[50]|is_unique[seg_3.name,id,' . intval($id) . ']';
        }

        $this->seg3Model->setValidationRules($rules);

        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        try {
            // Update the record
            if ($this->seg3Model->update($id, $data)) {
                return $this->respond([
                    'status' => true,
                    'message' => 'Updated successfully'
                ], 200);
            } else { 
                return $this->respond([ 
                    'status' => false, 
                    'message' => 'Failed to update', 
                    'errors' => $this->seg3Model->errors() 
                ], 500); // HTTP 500 Internal Server Error 
            }
        } catch (\Exception $e) {
            return $this->respond([
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        }

    }

    public function getAllSeg3(){
        $seg3 = $this->seg3Model->select('id, name, created_at')->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'data' => $seg3
        ], 200); // HTTP 200 OK
    }
}
