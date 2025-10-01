<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\FinishModel;
use App\Models\WorkOrderMasterModel;

class FinishController extends ResourceController
{
    protected $finishModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->finishModel = new FinishModel();
    }

    public function addFinish(){
        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'created_by'      => auth()->user()->id
        ];

        // Validate the input data
        if (!$this->validate($this->finishModel->validationRules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        
        if ($this->finishModel->insert($data)) {
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

    public function editFinish($id){
        // Find existing record
        $existing = $this->finishModel->find($id);

        if (!$existing) {
            return $this->respond([
                'status' => false,
                'message' => 'Finish record not found'
            ], 404); // HTTP 404 Not Found
        }


        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'updated_by'  => auth()->user()->id,
            'updated_at'  => date('Y-m-d H:i:s') // Or use Time::now() if using CI4's Time class
        ];

        // Dynamic validation for name uniqueness (ignore current ID)
        $rules = $this->finishModel->getValidationRules();
        
        if (isset($data['name'])) {
            // required|string|max_length[50]|is_unique[finish.name]
            $rules['name'] = 'required|string|max_length[50]|is_unique[finish.name,id,' . intval($id) . ']';
        }

        $this->finishModel->setValidationRules($rules);

        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        try {
            // Update the record
            if ($this->finishModel->update($id, $data)) {
                return $this->respond([
                    'status' => true,
                    'message' => 'Updated successfully'
                ], 200);
            } else { 
                return $this->respond([ 
                    'status' => false, 
                    'message' => 'Failed to update', 
                    'errors' => $this->finishModel->errors() 
                ], 500); // HTTP 500 Internal Server Error 
            }
        } catch (\Exception $e) {
            return $this->respond([
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        }

    }

    public function getAllFinish(){
        $finish = $this->finishModel->select('id, name, created_at')->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'data' => $finish
        ], 200); // HTTP 200 OK
    }

    public function getAllWODBandFinish(){
        $finish = $this->finishModel->select('id, name')->orderBy('name', 'ASC')->findAll();
        $workOrderMasterModel = new WorkOrderMasterModel();
        $work_order_db = $workOrderMasterModel->select('id, work_order_db')->orderBy('work_order_db', 'ASC')->findAll();

        return $this->respond([
            'data' => [
                'finish' => $finish,
                'work_order_db' => $work_order_db
            ]
        ], 200); // HTTP 200 OK
    }
}
