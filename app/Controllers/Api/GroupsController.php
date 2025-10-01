<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\GroupsModel;

class GroupsController extends ResourceController
{
    protected $groupsModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->groupsModel = new GroupsModel();
    }

    public function addGroup(){
        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'created_by'      => auth()->user()->id
        ];

        // Validate the input data
        if (!$this->validate($this->groupsModel->validationRules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        // Save the name
        if ($this->groupsModel->insert($data)) {
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

    public function editGroup($id){
        // Find existing record
        $existing = $this->groupsModel->find($id);

        if (!$existing) {
            return $this->respond([
                'status' => false,
                'message' => 'Group record not found'
            ], 404); // HTTP 404 Not Found
        }


        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'updated_by'  => auth()->user()->id,
            'updated_at'  => date('Y-m-d H:i:s') // Or use Time::now() if using CI4's Time class
        ];

        // Dynamic validation for name uniqueness (ignore current ID)
        $rules = $this->groupsModel->getValidationRules();
        
        if (isset($data['name'])) {
            // required|string|max_length[50]|is_unique[groups.name]
            $rules['name'] = 'required|string|max_length[50]|is_unique[groups.name,id,' . intval($id) . ']';
        }

        $this->groupsModel->setValidationRules($rules);

        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        try {
            // Update the record
            if ($this->groupsModel->update($id, $data)) {
                return $this->respond([
                    'status' => true,
                    'message' => 'Updated successfully'
                ], 200);
            } else { 
                return $this->respond([ 
                    'status' => false, 
                    'message' => 'Failed to update', 
                    'errors' => $this->groupsModel->errors() 
                ], 500); // HTTP 500 Internal Server Error 
            }
        } catch (\Exception $e) {
            return $this->respond([
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        }

    }

    public function getAllGroups(){
        $groups = $this->groupsModel->select('id, name, created_at')->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'data' => $groups
        ], 200); // HTTP 200 OK
    }
}
