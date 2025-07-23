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
