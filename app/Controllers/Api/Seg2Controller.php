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

    public function editSeg2($id){
        // Find existing record
        $existing = $this->seg2Model->find($id);

        if (!$existing) {
            return $this->respond([
                'status' => false,
                'message' => 'Seg2 record not found'
            ], 404); // HTTP 404 Not Found
        }


        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'updated_by'  => auth()->user()->id,
            'updated_at'  => date('Y-m-d H:i:s') // Or use Time::now() if using CI4's Time class
        ];

        // Dynamic validation for name uniqueness (ignore current ID)
        $rules = $this->seg2Model->getValidationRules();
        
        if (isset($data['name'])) {
            // required|string|max_length[50]|is_unique[seg_2.name]
            $rules['name'] = 'required|string|max_length[50]|is_unique[seg_2.name,id,' . intval($id) . ']';
        }

        $this->seg2Model->setValidationRules($rules);

        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        try {
            // Update the record
            if ($this->seg2Model->update($id, $data)) {
                return $this->respond([
                    'status' => true,
                    'message' => 'Updated successfully'
                ], 200);
            } else { 
                return $this->respond([ 
                    'status' => false, 
                    'message' => 'Failed to update', 
                    'errors' => $this->seg2Model->errors() 
                ], 500); // HTTP 500 Internal Server Error 
            }
        } catch (\Exception $e) {
            return $this->respond([
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        }

    }

    public function getAllSeg2(){
        $seg2 = $this->seg2Model->select('id, name, created_at')->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'data' => $seg2
        ], 200); // HTTP 200 OK
    }
}
