<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\MachineMasterModel;
use App\Models\MachineModel;

class MachineRevisionController extends ResourceController
{
    protected $machineMasterModel;
    protected $machineModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->machineMasterModel = new MachineMasterModel();
        $this->machineModel = new MachineModel();
    }

    public function addMachineRevision($machine_id){
        // Get input data
        $data = [
            'machine' => $machine_id,
            'name'           => $this->request->getVar('name'),
            'created_by'      => auth()->user()->id
        ];

        // Validate the input data
        if (!$this->validate($this->machineMasterModel->validationRules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        // Save the name
        try {
            if ($this->machineMasterModel->insert($data)) {
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
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            // Check if the error is due to foreign key constraint violation
            // if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
            //     return $this->respond([
            //         'status' => false,
            //         // 'message' => 'Foreign key constraint violation. Machine does not exist.'
            //         // 'message' => 'Machine does not exist.',
            //         'message' => 'Database error occurred: ' . $e->getMessage()
            //     ], 400); // HTTP 400 Bad Request
            // }

            // Check if the error is due to foreign key constraint violation
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                // Check which foreign key constraint failed by looking for the constraint name
                if (strpos($e->getMessage(), 'revisions-for-machine') !== false) {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Machine does not exist'
                    ], 400); // HTTP 400 Bad Request
                }
                // if (strpos($e->getMessage(), 'field1_constraint_name') !== false) {
                //     return $this->respond([
                //         'status' => false,
                //         'message' => 'Field1 does not exist'
                //     ], 400); // HTTP 400 Bad Request
                // }
                // if (strpos($e->getMessage(), 'field2_constraint_name') !== false) {
                //     return $this->respond([
                //         'status' => false,
                //         'message' => 'Field2 does not exist'
                //     ], 400); // HTTP 400 Bad Request
                // }

                // Default error if no specific constraint is matched
                return $this->respond([
                    'status' => false,
                    'message' => 'Foreign key constraint violation. Unable to add.'
                ], 400); // HTTP 400 Bad Request
            }
    
            // Handle any other database-related errors
            return $this->respond([
                'status' => false,
                'message' => 'Database error occurred: ' . $e->getMessage()
            ], 500); // HTTP 500 Internal Server Error
        }
    }


    public function machinesInfo(){
        $data = $this->machineModel->select('
                id AS machine_id, 
                name AS machine_name')
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->respond([
            'status'  => true,
            'message' => 'Data Found',
            'data'    => $data
        ], 200);
    }

    public function getMachineForModules(){
        $moduleIds = $this->request->getVar('moduleIds');
        $data = $this->machineMasterModel->select('
                machines.id AS machine_id, 
                machines.name AS machine_name
            ')
            ->join('machines', 'machine_module_master.machine_rev = machines.id')
            ->whereIn('machine_module_master.module', $moduleIds)
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->respond([
            'status'  => true,
            'message' => 'Data Found',
            'data'    => $data
        ], 200);
    }
    
}
