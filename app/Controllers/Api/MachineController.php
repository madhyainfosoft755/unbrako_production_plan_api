<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\MachineModel;
use App\Models\ModulesModel;
use App\Models\MachineRevisionModel;
use App\Models\MachineShifts;

class MachineController extends ResourceController
{
    protected $machineModel;
    protected $modulesModel;
    protected $machineRevisionModel;
    protected $machineShifts;
    protected $format = 'json';

    public function __construct()
    {
        // Load models in the constructor
        $this->machineModel = new MachineModel();
        $this->modulesModel = new ModulesModel();
        $this->machineRevisionModel = new MachineRevisionModel();
        $this->machineShifts = new MachineShifts();
    }

    public function addMachine(){
        $db = db_connect();
        $db->transBegin();

        try {
            // Get input data
            // $input = $this->request->getJSON();
            $machineShiftModel = new MachineShifts();

            // Validate input data
            if (!$this->validate($this->machineModel->validationRules)) {
                return $this->respond([
                    'status'  => false,
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors()
                ], 400); // HTTP 400 Bad Request
            }

            // Insert machine
            $machineData = [
                "name"=>$this->request->getVar('name'),
                "no_of_mc"=>$this->request->getVar('no_of_mc'),
                "process"=>$this->request->getVar('process'),
                "speed"=>$this->request->getVar('speed'),
                "created_by"=> auth()->user()->id
            ];
            $machineModel = new MachineModel();
            // Check if the machine name for the process already exists
            $existingMachine = $machineModel->where('name', $this->request->getVar('name'))
                                            ->where('process', $this->request->getVar('process'))
                                            ->first();
            if ($existingMachine) {
                return $this->respond([
                    'status'  => false,
                    'message' => "Machine name '{$machineName}' already exists for the specified process."
                ], 400); // HTTP 400 Bad Request
            }
            // $revisions = json_decode($this->request->getVar('machine_rev'), true);
            $revisions = (array)$this->request->getVar('machine_rev');
            if($this->request->getVar('no_of_mc') != count($revisions)){
                return $this->respond([
                    'status'  => false,
                    'message'  => "Machine number not matched with sub machines number"
                ], 400); // HTTP 400 Bad Request
            }
            
            $machineId = $machineModel->insert($machineData);

            if (!$machineId) {
                throw new \RuntimeException('Failed to insert machine');
            }

            // Validate and insert machine revisions
            $machineRevisionModel = new MachineRevisionModel();
            // print_r($this->request->getVar('machine_rev'));die;
            foreach ($revisions as $revision) {
                $revicionArr = [];
                $revicionArr['name']= $revision->machineName;
                $revicionArr['machine'] = $machineId;
                $revicionArr['created_by'] = auth()->user()->id;

                $revisionId = $machineRevisionModel->insert($revicionArr);
                if (!$revisionId) {
                    throw new \RuntimeException('Failed to insert revision: ' . json_encode($revision));
                }

                // Insert shifts for the revision
                foreach ($revision->shifts as $shift) {
                    $shiftData = [
                        'machine' => $revisionId,
                        'shift'            => $shift,
                        // 'created'          => auth()->user()->id
                    ];

                    if (!$machineShiftModel->insert($shiftData)) {
                        throw new \RuntimeException('Failed to insert shift for revision ID: ' . $revisionId);
                    }
                }
            }

            // Commit transaction
            $db->transCommit();

            return $this->respondCreated(['message' => 'Machine and revisions created successfully']);
        } catch (\Throwable $e) {
            // Rollback transaction
            $db->transRollback();

            return $this->fail(['error' => $e->getMessage()], 500);
        }
    
    }

    public function getAllMachines(){
        $machines = $this->machineRevisionModel->select('machine_revisions.id, 
                                machine_revisions.name as machine_1, 
                                machine_revisions.created_at, 
                                machine_revisions.disabled, 
                                machines.name as machine_name, 
                                machines.speed as speed, 
                                machines.no_of_mc as no_of_mc, 
                                process.name as process')
                                ->join('machines', 'machines.id = machine_revisions.machine')
                                ->join('process', 'process.id = machines.process')
            ->orderBy('machines.name', 'ASC')->findAll();

        $mappedMachines = array();
        foreach ($machines as $machine) {
            $machine['shifts'] = $this->machineShifts->select('shifts.number')
            ->join('shifts', 'shifts.id = machine_shifts.shift')
            ->where('machine_shifts.machine', $machine['id'])->findAll();
            array_push($mappedMachines, $machine);
        }

        return $this->respond([
            'data' => $mappedMachines
        ], 200); // HTTP 200 OK
    }

    public function getMachine($machine_id){
        $machines = $this->machineModel->select('machines.id as id, no_of_mc, machines.name, machines.process as process_id, speed, process.name as process')
            ->join('process', 'process.id = machines.process')
            ->where('machines.id', $machine_id)
            ->findAll();
        if(count($machines)>0){
            $machine = $machines[0];
            // print_r($machine);
            $machine_rev= $this->machineRevisionModel->select('id, name, disabled')->where('machine', $machine['id'])->findAll();
            $all_rev = [];
            foreach($machine_rev as $rev){
                $new_rev = [
                    'id' => $rev['id'],
                    'name' => $rev['name'],
                    'disabled' => $rev['disabled']
                ];
                $shifts = $this->machineShifts->select('id, shift')->where('machine', $rev['id'])->findAll();
                $new_rev['shifts']=$shifts;
                array_push($all_rev, $new_rev);
            }
            $machine['rev']=$all_rev;

            return $this->respond([
                'data' => $machine
            ], 200); // HTTP 200 OK
        } else {
            return $this->respond([
                'status'  => false,
                'message' => 'Machine not found'
            ], 404); // HTTP 404 Not Found
        }
    }

    // public function updateMachine($machine_id){
    //     // Check if the machine revision exists
    //     $machine = $this->machineModel->find($machine_id);
    //     if (!$machine) {
    //         return $this->respond([
    //             'status' => false,
    //             'message' => 'Machine not found'
    //         ], 404); // HTTP 404 Not Found
    //     }

    //     // Get input data
    //     $module = $this->request->getVar('module');
    //     $speed = $this->request->getVar('speed');
        
    //     // Optional: If module is provided, check if it exists
    //     if ($module) {
    //         $moduleDetail = $this->modulesModel->find($module);
    //         if (!$moduleDetail) {
    //             return $this->respond([
    //                 'status' => false,
    //                 'message' => 'Module not found'
    //             ], 404); // HTTP 404 Not Found
    //         }
    //     }

    //     // Prepare data for update
    //     $data = [
    //         'name'        => $this->request->getVar('name') ?? $machine['name'],
    //         'no_of_mc'    => $this->request->getVar('no_of_mc') ?? $machine['no_of_mc'],
    //         'module'      => $module ?? $machine['module'], // Use existing module if not provided
    //         'speed'       => isset($speed) ? $speed : $machine['speed'], // If speed is provided, use it
    //         'updated_by'  => auth()->user()->id, // Assuming you track who updates the record
    //     ];

    //     $validationRules = $this->machineModel->validationRules;
        
    //     $validationRules['name'] = "required|string|max_length[50]|is_unique[machines.name,id,{$machine_id}]";
        
    //     // Validate the input data (if needed, we can add rules here)
    //     if (!$this->validate($validationRules)) {
    //         return $this->respond([
    //             'status' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $this->validator->getErrors()
    //         ], 400); // HTTP 400 Bad Request
    //     }

    //     try{
    //         // Update the machine revision
    //         if ($this->machineModel->update($machine_id, $data)) {
    //             return $this->respond([
    //                 'status' => true,
    //                 'message' => 'Updated successfully'
    //             ], 200); // HTTP 200 OK
    //         } else {
    //             return $this->respond([
    //                 'status' => false,
    //                 'message' => 'Failed to update'
    //             ], 500); // HTTP 500 Internal Server Error
    //         }
    //     }
    //     catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
    //         // Check if the error is due to foreign key constraint violation
    //         if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
    //             return $this->respond([
    //                 'status' => false,
    //                 // 'message' => 'Foreign key constraint violation. Machine does not exist.'
    //                 // 'message' => 'Machine does not exist.',
    //                 // 'message' => 'Database error occurred: ' . $e->getMessage()
    //                 'message' => 'Foreign key constraint violation'
    //             ], 400); // HTTP 400 Bad Request
    //         }
    //     }
    // }

    public function updateMachine($id) {
        $db = db_connect();
    
        try {
            $machineModel = new MachineModel();
            $machineRevisionModel = new MachineRevisionModel();
            $machineShiftModel = new MachineShifts();
    
            // Check if the machine exists
            $existingMachine = $machineModel->find($id);
            if (!$existingMachine) {
                return $this->respond([
                    'status'  => false,
                    'message' => 'Machine not found'
                ], 404); // HTTP 404 Not Found
            }

            // Update validation rules dynamically
            $validationRules = [
                'name' => [
                    'label' => 'Machine Name',
                    'rules' => 'required|string|max_length[50]|is_unique[machines.name,id,' . $id . ']',
                    'errors' => [
                        'required' => 'Name is required',
                        'string' => 'Name must be a valid string',
                        'max_length' => 'Name must not exceed 50 characters',
                        'is_unique' => 'Name already exists in another record'
                    ]
                ],
                'no_of_mc' => 'required|numeric',
                'process' => 'required'
            ];

        
    
            // // Validate input data
            // if (!$this->validate($machineModel->validationRules)) {
            //     return $this->respond([
            //         'status'  => false,
            //         'message' => 'Validation failed',
            //         'errors'  => $this->validator->getErrors()
            //     ], 400); // HTTP 400 Bad Request
            // }
    
            // Prepare updated machine data
            $machineName = $this->request->getVar('name');
            $processId = $this->request->getVar('process');
            $machineData = [
                "name"       => $machineName,
                "no_of_mc"   => $this->request->getVar('no_of_mc'),
                "process"    => $processId,
                "speed"      => $this->request->getVar('speed'),
                "updated_by" => auth()->user()->id
            ];

            if (!$this->validate($validationRules)) {
                return $this->response->setJSON(['errors' => $this->validator->getErrors()])->setStatusCode(400);
            }
    
            // Check if the updated machine name already exists for the process
            $duplicateMachine = $machineModel->where('name', $machineName)
                                             ->where('process', $processId)
                                             ->where('id !=', $id)
                                             ->first();
            if ($duplicateMachine) {
                return $this->respond([
                    'status'  => false,
                    'message' => "Machine name '{$machineName}' already exists for the specified process."
                ], 400); // HTTP 400 Bad Request
            }
    
            // Validate revision count
            $revisions = $this->request->getVar('machine_rev');
            if ($this->request->getVar('no_of_mc') != count($revisions)) {
                return $this->respond([
                    'status'  => false,
                    'message' => "Machine number not matched with sub machines number"
                ], 400); // HTTP 400 Bad Request
            }
    
            // Start transaction
            $db->transBegin();
            echo $id;
            print_r($machineData);
            echo $machineModel->update($id, $machineData);
            // ######################################   Update incomming machine rev id's and insert new
            // ######################################   But never delete a mahine rev id.
            // ######################################   
            // die;
            // Update the machine
            if (!$this->machineModel->update(3, $machineData)) {
                // If update fails, check and output errors
                $errors = $machineModel->errors();
                throw new \RuntimeException(['error' => 'Failed to update machine', 'details' => $errors]);
            }
    
            // Delete existing revisions and shifts
            $this->machineRevisionModel->where('machine', $id)->delete();
            foreach ($revisions as $revision) {
                $revisionData = [
                    'name'       => $revision->machineName,
                    'machine'    => $id,
                    'created_by' => auth()->user()->id
                ];
    
                $revisionId = $this->machineRevisionModel->insert($revisionData);
                if (!$revisionId) {
                    throw new \RuntimeException('Failed to insert revision: ' . json_encode($revision));
                }
    
                // Insert shifts for the revision
                foreach ($revision->shifts as $shift) {
                    $shiftData = [
                        'machine' => $revisionId,
                        'shift'            => $shift,
                        'created_by'          => auth()->user()->id
                    ];
    
                    if (!$machineShiftModel->insert($shiftData)) {
                        throw new \RuntimeException('Failed to insert shift for revision ID: ' . $revisionId);
                    }
                }
            }
    
            // Commit transaction
            $db->transCommit();
    
            return $this->respondUpdated(['message' => 'Machine, revisions, and shifts updated successfully']);
        } catch (\Throwable $e) {
            // Rollback transaction
            $db->transRollback();
    
            return $this->fail(['error' => $e->getMessage()], 500);
        }
    }
    
}
