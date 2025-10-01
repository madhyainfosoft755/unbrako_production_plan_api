<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\ModulesModel;
use App\Models\WeeklyPlanningModel;

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
            'responsible'           => $this->request->getVar('responsible'),
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

    public function editModule($id){
        // Find existing record
        $existing = $this->modulesModel->find($id);

        if (!$existing) {
            return $this->respond([
                'status' => false,
                'message' => 'Module record not found'
            ], 404); // HTTP 404 Not Found
        }


        // Get input data
        $data = [
            'name'           => $this->request->getVar('name'),
            'responsible'           => $this->request->getVar('responsible'),
            'updated_by'  => auth()->user()->id,
            'updated_at'  => date('Y-m-d H:i:s') // Or use Time::now() if using CI4's Time class
        ];

        // Dynamic validation for name uniqueness (ignore current ID)
        $rules = $this->modulesModel->getValidationRules();
        
        if (isset($data['name'])) {
            // required|string|max_length[50]|is_unique[modules.name]
            $rules['name'] = 'required|string|max_length[50]|is_unique[modules.name,id,' . intval($id) . ']';
        }

        $this->modulesModel->setValidationRules($rules);

        if (!$this->validateData($data, $rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        try {
            // Update the record
            if ($this->modulesModel->update($id, $data)) {
                return $this->respond([
                    'status' => true,
                    'message' => 'Updated successfully'
                ], 200);
            } else { 
                return $this->respond([ 
                    'status' => false, 
                    'message' => 'Failed to update', 
                    'errors' => $this->modulesModel->errors() 
                ], 500); // HTTP 500 Internal Server Error 
            }
        } catch (\Exception $e) {
            return $this->respond([
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        }

    }

    public function getAllModules(){
        $modules = $this->modulesModel->select('modules.id, modules.name, users.name as responsible, users.id as responsible_id, users.role as role, modules.created_at')
            ->join('users', 'users.id = modules.responsible')
            ->orderBy('name', 'ASC')->findAll();

        $weeklyPlanningModel = new WeeklyPlanningModel();
        helper('week_calc');
        $data = get_current_week_info();
        $savedModules = $weeklyPlanningModel->where([
            'week_number' => $data['week_number'],
            'is_permanent'=> 1
        ])->findAll();

        return $this->respond([
            'data' => $modules,
            'savedModules'=>$savedModules
        ], 200); // HTTP 200 OK
    }
}
