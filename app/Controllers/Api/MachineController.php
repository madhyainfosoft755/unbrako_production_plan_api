<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\MachineModel;
use App\Models\ModulesModel;
use App\Models\SapDataModel;
use App\Models\DailyModuleShiftOutputModel;
use App\Models\DailyModuleShiftQtyUpdateModel;

class MachineController extends ResourceController
{
    protected $machineModel;
    protected $modulesModel;
    protected $sapDataModel;
    protected $daily_module_shift_output_model;
    protected $daily_module_shift_qty_update_model;
    protected $format = 'json';

    public function __construct()
    {
        // Load models in the constructor
        $this->machineModel = new MachineModel();
        $this->modulesModel = new ModulesModel();
        $this->sapDataModel = new SapDataModel();
        $this->daily_module_shift_output_model = new DailyModuleShiftOutputModel();
        $this->daily_module_shift_qty_update_model = new DailyModuleShiftQtyUpdateModel();
    }

    public function addMachine(){

        $data = $this->request->getJSON(true); // decode JSON as array

        // Optionally add created_by from auth or session
        $data['created_by'] = auth()->user()->id ?? null;

        // Dynamic validation for name uniqueness (ignore current ID)
        $rules = $this->machineModel->getValidationRules();
        
        if (isset($data['name'])) {
            $rules['name'] = 'required|string|max_length[50]|is_unique[machines.name]';
        }

        if (!$this->validateData($data, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (!$this->machineModel->insert($data)) {
            return $this->failValidationErrors($this->machineModel->errors());
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Machine created successfully',
            'data' => $data
        ]);
    
    }

    public function getAllMachines(){
        $machines = $this->machineModel->select('id, name, no_of_mc, process, speed, no_of_shift, capacity, plan_no_of_mc, per_of_efficiency, created_at')
            ->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'data' => $machines
        ], 200); // HTTP 200 OK
    }

    public function allMachines(){
        $machines = $this->machineModel->select('id, name')->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'data' => $machines
        ], 200); // HTTP 200 OK
    }

    public function getMachine($machine_id){
        $machines = $this->machineModel->select('id, name, no_of_mc, process, speed, no_of_shift, capacity, plan_no_of_mc, per_of_efficiency')
            ->first();
        if($machines){
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

    public function GetAllMachinesWithPartNumbers(){
        $request = $this->request;
        $postData = $request->getJSON(true); // Get POST data as array

        $module_id = isset($postData['module_id']) ? trim($postData['module_id']) : '';

        $builder = $this->sapDataModel
            ->select(
                'sap_data.*, 
                pm.material_description, 
                pm.machine as machine_id, 
                pm.machine_module,
                machines.name as machine_name,
                m.id as module_id,'
            )
            ->join('product_master pm', 'pm.material_number_for_process = sap_data.materialNumber', 'left')
            ->join('machines', 'machines.id = pm.machine', 'left')
            ->join('modules m',          'm.id  = pm.machine_module', 'left')

            // ->join('daily_module_shift_qty_update dmsq', 'dmsq.sap_id = sap_data.id', 'left')
            // ->join('daily_module_shift_output dmso',          'dmso.id  = dmsq.module_shift_id', 'left')
            // ->where('dmso.is_permanent','0')
            ->where('sap_data.to_forge_qty > sap_data.forged_so_far ')
            
            ->orderBy('machines.name')                 // ORDER BY mr.name,
            ->orderBy('sap_data.materialNumber'); //          sap_data.materialNumber
            // ->findAll();

        if (!empty($module_id)) {
            $builder->where('m.id', $module_id);
        }
        $data = $builder->findAll();

        // iterate every data object and add kv for production_qty
        foreach ($data as &$item) {
            $item['production_qty'] = 0; // Initialize production_qty to 0
            // Get the production quantity for this materialNumber
            $qtyUpdate = $this->daily_module_shift_qty_update_model
                ->select('SUM(production_qty) as total_qty')
                ->join('daily_module_shift_output dmso', 'dmso.id = daily_module_shift_qty_update.module_shift_id', 'left')
                ->where('sap_id', $item['id'])
                ->where('dmso.is_permanent', '0')
                ->first();
            if ($qtyUpdate && isset($qtyUpdate['total_qty'])) {
                $item['production_qty'] = (int)$qtyUpdate['total_qty'];
            }
        }

        return $this->respond([
            'data' => $data
        ], 200); // HTTP 200 OK
    }

    public function updateMachine($id = null) {

        if (!$id) {
            return $this->fail('Machine ID is required', 400);
        }

        // Get the existing record first
        $existing = $this->machineModel->find($id);

        if (!$existing) {
            return $this->failNotFound('Machine not found');
        }

        // Get incoming data (supports partial update)
        $data = $this->request->getJSON(true); // returns array

        // Add `updated_by` if needed
        $data['updated_by'] = auth()->user()->id ?? null;

        // Dynamic validation for name uniqueness (ignore current ID)
        $rules = $this->machineModel->getValidationRules();
        
        if (isset($data['name'])) {
            $rules['name'] = 'required|string|max_length[50]|is_unique[machines.name,id,' . intval($id) . ']';
        }

        if (!$this->validateData($data, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // Validate only provided fields (partial update)
        if (!$this->machineModel->update($id, $data)) {
            return $this->failValidationErrors($this->machineModel->errors());
        }

        return $this->respond([
            'status' => true,
            'message' => 'Machine updated successfully',
            'data' => $this->machineModel->find($id)
        ]);
    }
    
}
