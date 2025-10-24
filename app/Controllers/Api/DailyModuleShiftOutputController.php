<?php

namespace App\Controllers\Api;

use App\Models\DailyModuleShiftOutputModel;
use CodeIgniter\RESTful\ResourceController;
use App\Models\DailyModuleShiftQtyUpdateModel;
use App\Models\SapDataModel;
use Config\Database;

class DailyModuleShiftOutputController extends ResourceController
{
    protected $daily_module_shift_output_model;
    protected $daily_module_shift_qty_update_model;
    protected $format    = 'json';

    public function __construct()
    {
        // Load models in the constructor
        $this->daily_module_shift_output_model = new DailyModuleShiftOutputModel();
        $this->daily_module_shift_qty_update_model = new DailyModuleShiftQtyUpdateModel();
    }


    // Optional: override if needed
    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!$this->model->insert($data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respondCreated($data);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        if (!$this->model->update($id, $data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond($data);
    }

    public function delete($id = null)
    {
        if (!$this->model->find($id)) {
            return $this->failNotFound("Record with ID $id not found.");
        }

        $this->model->delete($id);
        return $this->respondDeleted(['id' => $id]);
    }

    public function getTempSaveData(){
        $existing = $this->daily_module_shift_output_model->where('is_permanent', 0)
            ->where('user_id', user_id())
            ->first();
        return $this->respondCreated([
            'status' => 'success',
            'data' => $existing
        ], 200);
    }


    public function saveDailyData(){
        $data = $this->request->getJSON(true); // true to convert to array

        if (!$data || !isset($data['machinePartNumberInfo'])) {
            return $this->fail('Invalid input');
        }

        $shift = $data['shift'];
        $supervisor = $data['supervisor'];
        $machineParts = $data['machinePartNumberInfo'];

        $userId = user_id(); // Replace with actual logged-in user ID
        $date = date('Y-m-d');
        $timestamp = date('Y-m-d H:i:s');

        $qtyUpdateModel = new DailyModuleShiftQtyUpdateModel();
        $sapDataModel = new SapDataModel();

        try{
            // Step 1: Check for existing record with is_permanent = 0
            $existing = $this->daily_module_shift_output_model->where('is_permanent', 0)
                                     ->where('user_id', $userId)
                                     ->first();

            try{
                if ($existing) {
                    $moduleShiftId = $existing['id'];
                    $this->daily_module_shift_output_model->update($moduleShiftId, [
                        'user_id'    => $userId,
                        'supervisor' => $supervisor,
                        'shift'      => $shift,
                        'date'       => $date,
                        'timestamp'  => $timestamp
                    ]);
                } else {
                    // Step 2: Insert new record
                    $moduleShiftId = $this->daily_module_shift_output_model->insert([
                        'user_id'    => $userId,
                        'supervisor' => $supervisor,
                        'shift'      => $shift,
                        'date'       => $date,
                        'timestamp'  => $timestamp
                    ]);
                }
            } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
                // Check if the error is due to foreign key constraint violation
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    return $this->respond([
                        'status' => false,
                        // 'message' => 'Foreign key constraint violation. Machine does not exist.'
                        // 'message' => 'Machine does not exist.',
                        // 'message' => 'Database error occurred: ' . $e->getMessage()
                        'message' => 'Data already saved for shift '. $shift . ' on date '. date('d-m-Y')
                    ], 400); // HTTP 400 Bad Request
                } else {
                    return $this->respond([
                        'status' => false,
                        // 'message' => 'Foreign key constraint violation. Machine does not exist.'
                        // 'message' => 'Machine does not exist.',
                        // 'message' => 'Database error occurred: ' . $e->getMessage()
                        'message' => 'Unknown database error occur'
                    ], 400); // HTTP 400 Bad Request
                }
            }

            $db = \Config\Database::connect();
            $db->transStart();

            // Step 3: Get existing qty updates for this module_shift_id
            $existingQtyRecords = $qtyUpdateModel->where('module_shift_id', $moduleShiftId)->findAll();
            $existingBySapId = [];
            foreach ($existingQtyRecords as $record) {
                $existingBySapId[$record['sap_id']] = $record;
            }

            // Step 4: Insert or Update per sap_id
            foreach ($machineParts as $part) {
                if($part['input_qty'] != 0 || $part['input_qty'] != null){
                    $sapId = $part['id'];
                    $existingSAPId = $sapDataModel->find($sapId);
                    if (!$existingSAPId) {
                        continue; // sap_id not found
                    }
                    if((int)$part['input_qty'] > (int)$existingSAPId['to_forge_qty'] - (int)$existingSAPId['forged_so_far']){
                        if(empty(trim($part['remarks']))){
                            // break the loop. if input_qty exeed and remarks not added.
                            return $this->respond([
                                'status' => false,
                                'message' => 'Input qty exceeds limit for material ' . $part['materialNumber'] . '. Remarks required.'
                            ], 400);
                        }
                    }
                    $dataToSave = [
                        'user_id'         => $userId,
                        'module_shift_id' => $moduleShiftId,
                        'module_id'       => $part['module_id'],
                        'sap_id'          => $sapId,
                        'machine_id'      => $part['machine_id'],
                        'material_number' => $part['materialNumber'],
                        'pending_qty'     => $part['pending_qty'],
                        'production_qty'  => $part['input_qty'],
                        'timestamp'       => $timestamp,
                        'remarks'         => $part['remarks']
                    ];
        
                    if (isset($existingBySapId[$sapId])) {
                        $qtyUpdateModel->update($existingBySapId[$sapId]['id'], $dataToSave);
                    } else {
                        $qtyUpdateModel->insert($dataToSave);
                    }
                }
            }

            $db->transComplete();

            // if ($db->transStatus() === false) {
            //     return $this->respond([
            //         'status' => false,
            //         'message' => 'Transaction failed. No data saved.'
            //     ], 400); 
            // }

            return $this->respondCreated([
                'status' => 'success',
                'module_shift_id' => $moduleShiftId
            ]);
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            $db->transRollback();
            // Check if the error is due to foreign key constraint violation
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return $this->respond([
                    'status' => false,
                    // 'message' => 'Foreign key constraint violation. Machine does not exist.'
                    // 'message' => 'Machine does not exist.',
                    // 'message' => 'Database error occurred: ' . $e->getMessage()
                    'message' => 'Data already saved for shift '. $shift . ' on date '. date('d-m-Y')
                ], 400); // HTTP 400 Bad Request
            } else {
                return $this->respond([
                    'status' => false,
                    // 'message' => 'Foreign key constraint violation. Machine does not exist.'
                    // 'message' => 'Machine does not exist.',
                    // 'message' => 'Database error occurred: ' . $e->getMessage()
                    'message' => 'Unknown database error occur'
                ], 400); // HTTP 400 Bad Request
            }
        }
    }

    public function saveSubmitData(){
        $existing = $this->daily_module_shift_output_model->where('is_permanent', 0)
                                     ->where('user_id', user_id())
                                     ->first();

        if ($existing) {
            $moduleShiftId = $existing['id'];
            $this->daily_module_shift_output_model->update($moduleShiftId, [
                'is_permanent'    => 1
            ]);
        }
        return $this->respondCreated([
            'status' => 'success'
        ], 200);
    }


    public function getTempModuleShiftData(){
        $existing = $this->daily_module_shift_output_model->where('is_permanent', 0)
                                     ->where('user_id', user_id())
                                     ->first();

        $success = 'failed';
        $data = null;
        if ($existing) {
            $success = 'success';
            $moduleShiftId = $existing['id'];
            $data =  $this->daily_module_shift_qty_update_model
                ->select('daily_module_shift_qty_update.*, machines.name as machine_name, 
                    modules.name as module_name, sap_data.to_forge_qty, sap_data.forged_so_far')
                ->where('module_shift_id', $moduleShiftId)
                ->join('sap_data', 'sap_data.id = daily_module_shift_qty_update.sap_id', 'left')
                ->join('modules', 'modules.id = daily_module_shift_qty_update.module_id', 'left')
                ->join('machines', 'machines.id = daily_module_shift_qty_update.machine_id', 'left')
                ->findAll();
        }
        return $this->respondCreated([
            'status' => $success,
            'data'   => $data
        ], 200);
    }
}
