<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\MachineMasterModel;
use CodeIgniter\API\ResponseTrait;

class MachineMasterController extends ResourceController
{
    use ResponseTrait;

    protected $machineModel;

    public function __construct()
    {
        $this->machineModel = new MachineMasterModel();
    }

    // Create Machine [POST]
    public function addMachineMaster()
    {
        // Get input data
        $data = [
            'machine_rev'      => $this->request->getVar('machine_rev'),
            'module'         => $this->request->getVar('module'),
            'created_by' => auth()->user()->id
        ];

        // Validate input data
        if (!$this->validate($this->machineModel->validationRules)) {
            return $this->respond([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        try{
            // Save the data
            if ($this->machineModel->insert($data)) {
                return $this->respond([
                    'status'  => true,
                    'message' => 'Machine added successfully'
                ], 201); // HTTP 201 Created
            } else {
                return $this->respond([
                    'status'  => false,
                    'message' => 'Failed to add machine'
                ], 500); // HTTP 500 Internal Server Error
            }

        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            // Check if the error is due to foreign key constraint violation
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                if (strpos($e->getMessage(), 'module') !== false) {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Machine already mapped with this module.'
                    ], 400); // HTTP 400 Bad Request
                }
                // Default error if no specific constraint is matched
                return $this->respond([
                    'status' => false,
                    'message' => 'Foreign key constraint violation. Unable to add.'
                ], 400); // HTTP 400 Bad Request
            }
            // Handle any other database-related errors
            return $this->respond([
                'status' => false,
                // 'message' => 'Database error occurred: ' . $e->getMessage()
                'message' => $e->getMessage()
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    // Get all Machines [GET]
    public function getAllMachineMaster()
    {

        $request = $this->request;
        $postData = $request->getJSON(true); // Get POST data as array

        // Get page from query param (e.g., ?page=2), default to 1
        $page = (int) $this->request->getGet('page');
        $page = max($page, 1); // Ensure at least 1
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $builder = $this->machineModel->select('machine_module_master.id, 
                machines.name as machine_name,
                machines.speed as speed, 
                machines.no_of_mc as no_of_mc, 
                machines.id as machine_id, machines.no_of_shift as no_of_shift, machines.capacity as capacity,
                machines.plan_no_of_mc as plan_no_of_mc, machines.per_of_efficiency as per_of_efficiency,
                machines.process as process, 
                modules.name as module, 
                users.name as responsible')
                ->join('machines', 'machines.id = machine_module_master.machine_rev')
                ->join('modules', 'modules.id = machine_module_master.module')
                ->join('users', 'users.id = modules.responsible')
                ->orderBy('machines.name', 'ASC');

        $machines = isset($postData['machines']) ? trim($postData['machines']) : '';
        $modules = isset($postData['modules']) ? trim($postData['modules']) : '';

        if (!empty($machines)) {
            $builder->like('machines.id', $machines);
        }

        if (!empty($modules)) {
            $builder->where('modules.id', $modules);
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $data = $builder->findAll($perPage, $offset);

        return $this->respond([
            'status'  => true,
            'message' => 'Machines found',
            'data'    => $data,
            'filterData' => $postData,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => ceil($total / $perPage)
            ]
        ], 200);
    }

    // Update Machine [PUT]
    public function updateMachineMaster($id)
    {
        // Get input data
        $data = [
            'process'        => $this->request->getVar('process'),
            'product'        => $this->request->getVar('product'),
            'machine'        => $this->request->getVar('machine'),
            'no_of_mc'       => $this->request->getVar('no_of_mc'),
            'machine_1'      => $this->request->getVar('machine_1'),
            'responsible'    => $this->request->getVar('responsible'),
            'module'         => $this->request->getVar('module'),
            'speed'          => $this->request->getVar('speed'),
            'no_of_shift'    => $this->request->getVar('no_of_shift'),
            'updated_by'     => auth()->user()->id,  // Assuming user authentication
        ];

        // Validate input data
        if (!$this->validate($this->machineModel->validationRules)) {
            return $this->respond([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        // Update the data
        if ($this->machineModel->update($id, $data)) {
            return $this->respond([
                'status'  => true,
                'message' => 'Machine updated successfully'
            ], 200); // HTTP 200 OK
        } else {
            return $this->respond([
                'status'  => false,
                'message' => 'Failed to update machine'
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    public function getMachineModules($machine_rev_id){
        $machine_rev_modules = $this->machineModel->select('machine_module_master.module as module_id, modules.name as module_name, users.name as responsible')
        ->join('modules', 'modules.id = machine_module_master.module')
        ->join('users', 'users.id = modules.responsible')
        ->where('machine_module_master.machine_rev', $machine_rev_id)
        ->orderBy('modules.name', 'ASC')
        ->findAll();
        return $this->respond([
            'status'  => true,
            'message' => 'Machine found',
            'data'    => $machine_rev_modules
        ], 200); 
    }
}
