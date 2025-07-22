<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\WeeklyPlanningDataModel;
use App\Models\WeeklyPlanningModel;

class WeeklyPlanningController extends ResourceController
{
    protected $weeklyPlanningModel;
    protected $weeklyPlanningDataModel;
    protected $format    = 'json';

    public function __construct()
    {
        $this->weeklyPlanningModel = new WeeklyPlanningModel();
        $this->weeklyPlanningDataModel = new WeeklyPlanningDataModel();
    }

    public function completeWeeklyReportForMoudle(){
        $data = $this->request->getJSON(true); // Get JSON as associative array
        $moduleId = $data['module_id'] ?? null;

        if($moduleId){
            helper('week_calc');
            $data = get_current_week_info();
            $existing = $this->weeklyPlanningModel->where([
                'module'      => $moduleId,
                'week_number' => $data['week_number'],
                'is_permanent'=> 0
            ])->first();
    
            if ($existing) {
                $this->weeklyPlanningModel->update($existing['id'], ['is_permanent' => 1, 'user' => user_id(), 'timestamp'=> date('Y-m-d H:i:s')]);
                return $this->respond([
                    'status' => 'success',
                    'message' => 'Weekly Planning Added'
                ], 200);
            } else {
                return $this->respond([
                    'status' => 'failed',
                    'message' => 'Module id not found or already completed for week '.$data['week_number']
                ], 400);
            }
        } else {
            return $this->respond([
                'status' => 'failed',
                'message' => 'Module id is required'
            ], 400);
        }
    }

    public function updateWeeklyReportFields(){
        $data = $this->request->getJSON(true); // Get JSON as associative array
        $moduleId = $data['module_id'] ?? null;
        $machineId = $data['machine_id'] ?? null;

        if(empty($machineId) || empty($moduleId)){
            return $this->respond([
                'status' => 'failed',
                'message' => 'Module Id and Machine Id is required'
            ], 400);
        }

        helper('week_calc');
        $weeklyPlanningata = get_current_week_info();

        $existing = $this->weeklyPlanningModel->where([
            'module'      => $moduleId,
            'week_number' => $weeklyPlanningata['week_number'],
            'is_permanent'=> 0
        ])->first();

        if ($existing) {
            $existingId = $existing['id'];
            $existingData = $this->weeklyPlanningDataModel->where([
                'weekly_planning_id'      => $existingId,
                'machine_id' => $machineId,
            ])->first();
            if ($existingData) {
                $updateDataArr = [];
                if(isset($data['rm_tpm_booking']) && !empty($data['rm_tpm_booking'])){
                    $updateDataArr['rm_tpm_booking'] = (int)$data['rm_tpm_booking'];
                }
                if(isset($data['rm_due_to_development']) && !empty($data['rm_due_to_development'])){
                    $updateDataArr['rm_due_to_development'] = (int)$data['rm_due_to_development'];
                }
                if(isset($data['gap']) && !empty($data['gap'])){
                    $updateDataArr['gap'] = (int)$data['gap'];
                }
                if(empty($updateDataArr)){
                    return $this->respond([
                        'status' => 'failed',
                        'message' => 'No update field found'
                    ], 400);
                } else {
                    $updateDataArr['updated_at'] = date('Y-m-d H:i:s');
                    $updateDataArr['updated_by'] = user_id();
                    $this->weeklyPlanningDataModel->update($existingData['id'], $updateDataArr);
                    return $this->respond([
                        'status' => 'success',
                        'message' => 'Updated Successfully.'
                    ], 200);
                }
            } else {
                return $this->respond([
                    'status' => 'failed',
                    'message' => 'Record Not Found'
                ], 400);
            }
        } else {
            return $this->respond([
                'status' => 'failed',
                'message' => 'Module id not found or already completed for week '.$data['week_number']
            ], 400);
        }
    }
}