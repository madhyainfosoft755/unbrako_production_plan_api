<?php

// namespace App\Models;

// use CodeIgniter\Model;

// class SapDataModel extends Model
// {
//     protected $table            = 'sap_data';
//     protected $primaryKey       = 'id';
//     protected $useAutoIncrement = true;
//     protected $returnType       = 'array';
//     protected $useSoftDeletes   = false;
//     protected $protectFields    = true;
//     protected $allowedFields    = [];

//     protected bool $allowEmptyInserts = false;
//     protected bool $updateOnlyChanged = true;

//     protected array $casts = [];
//     protected array $castHandlers = [];

//     // Dates
//     protected $useTimestamps = false;
//     protected $dateFormat    = 'datetime';
//     protected $createdField  = 'created_at';
//     protected $updatedField  = 'updated_at';
//     protected $deletedField  = 'deleted_at';

//     // Validation
//     protected $validationRules      = [];
//     protected $validationMessages   = [];
//     protected $skipValidation       = false;
//     protected $cleanValidationRules = true;

//     // Callbacks
//     protected $allowCallbacks = true;
//     protected $beforeInsert   = [];
//     protected $afterInsert    = [];
//     protected $beforeUpdate   = [];
//     protected $afterUpdate    = [];
//     protected $beforeFind     = [];
//     protected $afterFind      = [];
//     protected $beforeDelete   = [];
//     protected $afterDelete    = [];
// }


namespace App\Models;


use CodeIgniter\Model;

class SapDataModel extends Model
{
    protected $table = 'sap_data';
    protected $primaryKey = 'id';
    protected $protectFields    = false;
    protected $readOnly = false;

    // Add callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert  = [];
    protected $beforeUpdate = ['storeOldData'];
    protected $afterUpdate  = ['afterUpdateTrigger'];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    // protected function beforeInsertTrigger(array $data)
    // {
    //     // Modify data before saving
    //     $data['data']['created_at'] = date('Y-m-d H:i:s');
    //     return $data;
    // }

    protected $oldData = [];
    /**
     * Store old record before update
     */
    protected function storeOldData(array $data)
    {
        // log_message('error', 'Batch insert failed1: '. json_encode($data) );
        if (!empty($data['id'])) {
            $this->oldData = $this->find($data['id'][0]);
        }
        return $data;
    }
  
    protected $allowedFields = ['orderNumber', 'plant', 'materialNumber', 'materialDescription', 'orderQuantity_GMEIN', 'deliveredQuantity_GMEIN', 'confirmedQuantity_GMEIN', 'unitOfMeasure_GMEIN', 'to_forge_qty', 'to_forge_limit_inc', 'forged_so_far', 'batch', 'startDate', 'salesOrder', 'systemStatus', 'scheduledFinishDate', 'insertedTimestamp', 'insertedBy',
    'forge_commite_week', 'this_month_forge_qty', 'special_remarks', 'extra_production_remarks', 'is_rm_ready', 'surface_treatment_process', 'priority_list', 'rm_delivery_date', 'monthly_plan', 'monthly_fix_plan', 'rm_allocation_priority', 'rm_correction', 'plan_allocation', 'updated_at', 'updated_by'   ];  // Define the allowed fields

    public function getSapData($filters = [], $orderBy = [], $limit = null, $offset = null)
    {
        $subQuery = '(SELECT *, 
                            CASE 
                                WHEN UPPER(SUBSTRING(batch, 1, 2)) = "DB" THEN SUBSTRING(batch, 1, 6)
                                ELSE SUBSTRING(batch, 1, 5)
                            END AS work_order
                    FROM sap_data) AS derived';

        $query = $this->db->table($subQuery)
            ->select('derived.*, 
                    derived.work_order,
                    wom.customer,
                    wom.reciving_date,
                    wom.delivery_date,
                    wom.responsible_person as wom_responsible_person_id,
                    wom.segment as wom_segment_id,
                    pm.finish,
                    pm.finish_wt,
                    pm.size,
                    pm.prod_group,
                    pm.length,
                    pm.spec,
                    pm.rod_dia1,
                    pm.drawn_dia1,
                    pm.machine as machine_id,
                    pm.machine_module as module_id,
                    pm.seg2 as seg2_id,
                    pm.seg3 as seg3_id,
                    mc.name as machine_name,
                    mc.speed as machine_speed,
                    mc.no_of_mc,
                    modules.name as module_name,
                    sap_responsible.name as sap_responsible_person_name,
                    sap_segment.name as sap_segment_name,
                    seg2.name as seg2_name,
                    seg3.name as seg3_name,
                    groups.name as prod_group_name',)
            ->join('work_order_master wom', 'wom.work_order_db = derived.work_order', 'left')
            ->join('product_master pm', 'pm.material_number_for_process = derived.materialNumber', 'left')
            ->join('machines mc', 'mc.id = pm.machine', 'left')
            ->join('modules', 'modules.id = pm.machine_module', 'left')
            ->join('groups', 'groups.id = pm.prod_group', 'left')
            ->join('users sap_responsible', 'sap_responsible.id = modules.responsible', 'left')
            ->join('segments sap_segment', 'sap_segment.id = pm.segment', 'left')
            ->join('segments wom_segment', 'wom_segment.id = wom.segment', 'left')
            ->join('seg_2 seg2', 'seg2.id = pm.seg2', 'left')
            ->join('seg_3 seg3', 'seg3.id = pm.seg3', 'left');
            // ->get()
            // ->getResult();

            // Apply filters dynamically
            foreach ($filters as $key => $value) {
                if (is_array($value)) {
                    // Handle WHERE IN clauses
                    $query->whereIn($key, $value);
                } else {
                    // Handle standard WHERE clauses
                    $query->where($key, $value);
                }
            }

            // Apply ordering dynamically
            foreach ($orderBy as $column => $direction) {
                $query->orderBy($column, $direction);
            }

            // Apply limit and offset if provided
            if (!is_null($limit)) {
                $query->limit($limit, $offset);
            }

            // Print the compiled query
            // echo $query->getCompiledSelect(); 
            // exit; // Stop execution to inspect the query

            // Execute the query and return results
            return $query->get()->getResult();


            // SELECT derived.*, 
            // derived.work_order,
            // wom.customer,
            // wom.reciving_date,
            // wom.delivery_date,
            // wom.responsible_person as wom_responsible_person_id,
            // wom.segment as wom_segment_id,
            // pm.finish,
            // pm.finish_wt,
            // pm.size,
            // pm.prod_group,
            // pm.length,
            // pm.spec,
            // pm.rod_dia1,
            // pm.drawn_dia1,
            // pm.machine as machine_id,
            // pm.machine_module as module_id,
            // pm.seg2 as seg2_id,
            // pm.seg3 as seg3_id,
            // mc.name as machine_name,
            // mc.speed as machine_speed,
            // mc.no_of_mc,
            // modules.name as module_name,
            // sap_responsible.name as sap_responsible_person_name,
            // sap_segment.name as sap_segment_name,
            // seg2.name as seg2_name,
            // seg3.name as seg3_name
            // FROM 
            //     (
            //         SELECT 
            //             *, 
            //             CASE 
            //                 WHEN UPPER(SUBSTRING(batch, 1, 2)) = 'DB' THEN SUBSTRING(batch, 1, 6)
            //                 ELSE SUBSTRING(batch, 1, 5)
            //             END AS work_order
            //         FROM 
            //             sap_data
            //     ) AS derived
            // INNER JOIN work_order_master wom 
            //     ON wom.work_order_db = derived.work_order
            // INNER JOIN product_master as pm
            //     ON pm.material_number_for_process = derived.materialNumber
            // INNER JOIN machines mc
            //     ON mc.id = pm.machine
            // INNER JOIN modules ON
            //     modules.id = pm.machine_module
            // INNER JOIN users sap_responsible
            //     on sap_responsible.id = modules.responsible
            // INNER JOIN segments as sap_segment ON sap_segment.id = pm.segment
            // INNER JOIN segments as wom_segment ON wom_segment.id = wom.segment
            // INNER JOIN seg_2 seg2 on seg2.id = pm.seg2
            // INNER JOIN seg_3 seg3 on seg3.id = pm.seg3;
    }


    /**
     * After update trigger logic (like MySQL trigger)
     */
    protected function afterUpdateTrigger(array $data)
    {
        $sapCalculatedModel = new SapCalculatedSummaryModel();
        $newData = $this->find($data['id'][0]);
        
        log_message('error', 'incomming data: '. json_encode($newData) );
        log_message('error', 'old data: '. json_encode($this->oldData) );
        if (!$newData) {
            return $data;
        }

        $sapId = $newData['id'];

        // Always update summary basic fields
        $sapCalculatedModel->where('sap_id', $sapId)->set([
            'sap_id' => $sapId,
            'sap_orderNumber'               => $newData['orderNumber'],
            'rm_correction'                 => $newData['rm_correction'],
            'plan_allocation'               => $newData['plan_allocation'],
            'materialNumber'                => $newData['materialNumber'],
            'materialDescription'           => $newData['materialDescription'],
            'sap_plant'                     => $newData['plant'],
            'systemStatus'                  => $newData['systemStatus'],
            'orderQuantity_GMEIN'           => $newData['orderQuantity_GMEIN'],
            'deliveredQuantity_GMEIN'       => $newData['deliveredQuantity_GMEIN'],
            'confirmedQuantity_GMEIN'       => $newData['confirmedQuantity_GMEIN'],
            'weekly_plan'                 => $newData['forge_commite_week'],
            'monthly_plan'                => $newData['monthly_plan'],
            'monthly_fix_plan'                => $newData['monthly_fix_plan'],
            'pm_order_number'               => $newData['orderNumber'],
            'unitOfMeasure_GMEIN'           => $newData['unitOfMeasure_GMEIN'],
            'batch'                         => $newData['batch'],
            'main_special_remarks'          => $newData['special_remarks'],
            'extra_production_remarks'      => $newData['extra_production_remarks'],
            'rm_delivery_date'              => $newData['rm_delivery_date'],
            'rm_allocation_priority'        => $newData['rm_allocation_priority'],
            'advance_final_rm_wt'           => $newData['advance_final_rm_wt'],
            'priority_list'                 => $newData['priority_list'],
        ])->update();

        log_message('error', 'new updated data: '. json_encode($sapCalculatedModel->where('sap_id', $sapId)->findAll()) );
        $cond = $this->oldData && (($this->oldData['forged_so_far'] != $newData['forged_so_far']) || 
        ($this->oldData['to_forge_limit_inc'] != $newData['to_forge_limit_inc'])) || 
        ($this->oldData['rm_correction'] != $newData['rm_correction']) || 
        ($this->oldData['plan_allocation'] != $newData['plan_allocation']) || 
        ($this->oldData['month_rm_total'] != $newData['month_rm_total']);
        // log_message('error', 'condition: '. $cond );
        // If forged_so_far changed, do recalculation
        if ($cond) {
            $this->recalculateForgedSummary($sapId, $newData);
        }

        return $data;
    }


    /**
     * Recalculation logic similar to stored procedure
     */
    protected function recalculateForgedSummary($sapId, $data)
    {
        $db = \Config\Database::connect();

        // Example of fetching extra info from related tables
        $Q = "
            SELECT 
                pm.machine_module AS machine_module,
                IFNULL(pm.finish_wt, 0) AS finish_wt,
                IFNULL(mc.speed, 50) AS speed,
                IFNULL(mc.per_of_efficiency, 60) AS efficiency,
                IFNULL(mc.plan_no_of_mc, 1) AS plan_mc,
                IFNULL(mc.no_of_shift, 1) AS shifts
            FROM product_master pm
            LEFT JOIN machines mc ON mc.id = pm.machine
            LEFT JOIN modules m ON m.id=pm.machine_module   
            WHERE pm.material_number_for_process = 
        ". $data['materialNumber'];
        $query = $db->query($Q);
        // echo $data['materialNumber']; 
        $row = $query->getRowArray();
        // print_r($row);   die;
        // log_message('error', 'query'. $Q );
        // log_message('error', 'row'. json_encode($row) );
        if (!$row) return;
        // log_message('error', 'Calculation starts: ');
        $finish_wt = $row['finish_wt'];
        $mult = floatval($db->query("SELECT getModuleMultiplier(?) AS m", $row['machine_module'])->getRow()->m ?? 1.2);
        $thisMonthForgeWt = ($data['forged_so_far'] * $finish_wt) / 1000;
        $thisMonthForgeRmWt = $thisMonthForgeWt * $mult;
        $actBalanceRmWt = max(0, $data['plan_allocation'] - $thisMonthForgeRmWt);


        $forged = intval($data['forged_so_far'] ?? 0);
        $total_alloc = floatval($data['rm_correction'] ?? 0) + floatval($data['plan_allocation'] ?? 0) + floatval($data['month_rm_total'] ?? 0);

        // Calculations
        $to_forge_qty = intval($data['to_forge_qty'] ?? 0) + intval($data['to_forge_limit_inc'] ?? 0);
        $to_forge_wt = ($to_forge_qty * $finish_wt)/1000;
        $to_forge_rm_wt = $to_forge_wt * $mult;
        $total_alloc2 = min($total_alloc, $to_forge_rm_wt);
        $plan_print_qty = $mult && $finish_wt ? ($total_alloc2 * 1000 / $mult / $finish_wt) : 0;
        $this_month_forge_wt = ($forged * $finish_wt)/1000;
        $this_month_forge_rm_wt = $this_month_forge_wt * $mult;
        $act_balance_rm_wt = max(0, $total_alloc - $this_month_forge_rm_wt);
        $allocated_balance_rm_wt = $act_balance_rm_wt;
        $allocated_product_wt = $allocated_balance_rm_wt/$mult;
        $allocated_product_qty = $finish_wt ? ($allocated_product_wt*1000)/$finish_wt : 0;
        $per_day_booking = (($row['speed'] ?? 50) * 450) * (($row['efficiency'] ?? 60)/100) * ($row['shifts'] ?? 1) * ($row['plan_mc'] ?? 1);
        $final_pending_qty = intval($to_forge_qty - $forged);
        $pending_qty = max(0, $final_pending_qty);
        $pending_wt = ($pending_qty * $finish_wt)/1000;
        $pending_rm_wt = $pending_wt * $mult;
        $pending_from_outside_1 = $to_forge_rm_wt - $total_alloc;
        $pending_from_outside = max(0, $pending_from_outside_1);
        $no_days_booking = $per_day_booking ? $final_pending_qty <=0 ? 0 : $final_pending_qty / $per_day_booking : 0;
        $no_days_booking = number_format((float)$no_days_booking, 1, '.', '');
        $weekly_planning_days = $per_day_booking ? $allocated_product_qty/$per_day_booking : 0;

        $dataForUpdate = [
            'finish_wt'                     => $finish_wt,
            'to_forge_qty'                  => $to_forge_qty,
            'to_forge_wt'                   => $to_forge_wt,
            'forged_so_far'                 => $forged,
            'this_month_forge_wt'          => $this_month_forge_wt,
            'to_forge_rm_wt'                => $to_forge_rm_wt,
            'total_allocation'             => $total_alloc,
            'total_allocation_2'           => $total_alloc2,
            'plan_print_qty'               => $plan_print_qty,
            'this_month_forge_rm_wt'       => $this_month_forge_rm_wt,
            'act_allocated_balance_rm_wt'  => $act_balance_rm_wt,
            'allocated_balance_rm_wt'       => $allocated_balance_rm_wt,
            'allocated_product_wt'         => $allocated_product_wt,
            'allocated_product_qty'        => $allocated_product_qty,
            'per_day_booking'              => $per_day_booking,
            'final_pending_qty'            => $final_pending_qty,
            'pending_qty'                  => $pending_qty,
            'pending_wt'                   => $pending_wt,
            'pending_rm_wt'                => $pending_rm_wt,
            'pending_from_outside_1'       => $pending_from_outside_1,
            'pending_from_outside'         => $pending_from_outside,
            'no_of_days_booking'           => $no_days_booking,
            'no_of_day_weekly_planning'    => $weekly_planning_days,
        ];

        // Update summary table
        $db->table('sap_calculated_summary')
            ->where('sap_id', $sapId)
            ->update($dataForUpdate);

        log_message('error', 'SAP CALCULATE SUMMARY UPDATED DATA: '. json_encode($dataForUpdate) );
    }


    /**
     * After update trigger — calls the same logic as processSapData()
     */
    // protected function afterUpdateTrigger(array $data)
    // {
    //     $db = \Config\Database::connect();
    //     $sapId = $data['id'][0];
    //     $sap   = $this->find($sapId);

    //     if (!$sap) {
    //         return $data;
    //     }

    //     // Run the same logic as processSapData
    //     $this->processSapData($sapId, $db, true);

    //     return $data;
    // }


    /**
     * Full trigger logic (copied from command file)
     */
    // protected function processSapData(int $sapId, BaseConnection $db, bool $update = false)
    // {
    //     $sap = $db->table('sap_data')->where('id', $sapId)->get()->getRowArray();
    //     if (!$sap) return;

    //     $batch = strtoupper(substr($sap['batch'], 0, 2)) === 'DB'
    //         ? substr($sap['batch'], 0, 6)
    //         : substr($sap['batch'], 0, 5);

    //     // Left join fetch
    //     $row = $db->table('product_master pm')
    //         ->select([
    //             'pm.finish_wt', 'pm.machine_module',
    //             'COALESCE(mc.per_of_efficiency,60) AS per_eff',
    //             'mc.id AS machine_id', 'mc.name AS machine_name',
    //             'COALESCE(mc.speed,50) AS speed', 'COALESCE(mc.no_of_mc,1) AS machines',
    //             'COALESCE(mc.no_of_shift,1) AS shifts', 'COALESCE(mc.plan_no_of_mc,1) AS plan_mc',
    //             'wom.id AS work_order_master_id', 'wom.reciving_date', 'wom.delivery_date',
    //             'wom.wo_add_date', 'wom.work_order_db', 'wom.customer', 'wom.responsible_person_name',
    //             'wom.marketing_person_name', 'wom.segment as wom_segment', 'wom.plant as wom_plant',
    //             'segments.name AS wom_seg_name', 'wom.quality_inspection_required',
    //             'modules.name AS module_name', 'modules.responsible AS module_responsible_person_id',
    //             'module_res.name AS module_responsible_person_name', 'pm.id AS product_master_id',
    //             'pm.seg2 as pm_seg2', 'seg2.name AS seg2_name', 'pm.seg3 as pm_seg3', 'seg3.name AS seg3_name',
    //             'pm.finish AS finish_id', 'finish.name AS finish_name', 'pm.prod_group AS grp_id',
    //             'groups.name AS grp_name', 'pm.cheese_wt', 'pm.size', 'pm.length', 'pm.spec',
    //             'pm.rod_dia1', 'pm.drawn_dia1', 'pm.condition_of_rm', 'pm.special_remarks', 'pm.bom',
    //             'pm.rm_component', 'stp.name as surface_treatment_process_name', 'stp.id as surface_treatment_process_id'
    //         ])
    //         ->join('machines mc', 'mc.id=pm.machine', 'left')
    //         ->join('modules', 'modules.id=pm.machine_module', 'left')
    //         ->join('work_order_master wom', "wom.work_order_db = '{$batch}'", 'left')
    //         ->join('segments', 'segments.id=wom.segment', 'left')
    //         ->join('finish', 'finish.id=pm.finish', 'left')
    //         ->join('groups', 'groups.id=pm.prod_group', 'left')
    //         ->join('seg_2 seg2', 'seg2.id=pm.seg2', 'left')
    //         ->join('seg_3 seg3', 'seg3.id=pm.seg3', 'left')
    //         ->join('surface_treatment_process stp', 'stp.id=1', 'left')
    //         ->join('users module_res', 'module_res.id=modules.responsible', 'left')
    //         ->where('pm.material_number_for_process', $sap['materialNumber'])
    //         ->get()
    //         ->getRowArray();

    //     $row = $row ?? [
    //         'finish_wt' => 0, 'machine_module' => null, 'per_eff' => 60, 'speed' => 50,
    //         'machines' => 1, 'shifts' => 1, 'plan_mc' => 1, 'machine_id' => null, 'customer' => null,
    //         'quality_inspection_required' => 0, 'wom_plant' => null, 'wom_segment' => null, 'wom_seg_name' => null,
    //         'pm_seg2' => null, 'seg2_name' => null, 'pm_seg3' => null, 'work_order_master_id' => null,
    //         'product_master_id' => null, 'module_name' => null, 'module_responsible_person_id' => null,
    //         'module_responsible_person_name' => null, 'machine_name' => null, 'finish_id' => null,
    //         'finish_name' => null, 'grp_id' => null, 'grp_name' => null, 'cheese_wt' => 0,
    //         'surface_treatment_process_name' => null, 'surface_treatment_process_id' => null,
    //         'size' => null, 'length' => null, 'spec' => null, 'rod_dia1' => null, 'drawn_dia1' => null,
    //         'condition_of_rm' => null, 'special_remarks' => null, 'bom' => null, 'rm_component' => null,
    //         'reciving_date' => null, 'delivery_date' => null, 'wo_add_date' => null,
    //         'work_order_db' => null, 'responsible_person_name' => null, 'marketing_person_name' => null
    //     ];

    //     extract($row);

    //     $forged = intval($sap['forged_so_far'] ?? 0);
    //     $total_alloc = floatval($sap['rm_correction'] ?? 0) + floatval($sap['plan_allocation'] ?? 0);
    //     $mult = floatval($db->query("SELECT getModuleMultiplier(?) AS m", [$machine_module])->getRow()->m ?? 1.2);

    //     $to_forge_qty = intval($sap['to_forge_qty'] ?? 0) + intval($sap['to_forge_limit_inc'] ?? 0);
    //     $to_forge_wt = ($to_forge_qty * $finish_wt) / 1000;
    //     $to_forge_rm_wt = $to_forge_wt * $mult;
    //     $total_alloc2 = min($total_alloc, $to_forge_rm_wt);
    //     $plan_print_qty = $mult && $finish_wt ? ($total_alloc2 * 1000 / $mult / $finish_wt) : 0;
    //     $this_month_forge_wt = ($forged * $finish_wt) / 1000;
    //     $this_month_forge_rm_wt = $this_month_forge_wt * $mult;
    //     $act_balance_rm_wt = max(0, $total_alloc - $this_month_forge_rm_wt);
    //     $allocated_balance_rm_wt = $act_balance_rm_wt;
    //     $allocated_product_wt = $allocated_balance_rm_wt / $mult;
    //     $allocated_product_qty = $finish_wt ? ($allocated_product_wt * 1000) / $finish_wt : 0;
    //     $per_day_booking = ($speed * 450) * ($per_eff / 100) * $shifts * $plan_mc;
    //     $final_pending_qty = $to_forge_qty - $forged;
    //     $pending_qty = max(0, $final_pending_qty);
    //     $pending_wt = ($pending_qty * $finish_wt) / 1000;
    //     $pending_rm_wt = $pending_wt * $mult;
    //     $pending_from_outside_1 = $to_forge_rm_wt - $total_alloc;
    //     $pending_from_outside = max(0, $pending_from_outside_1);
    //     $no_days_booking = $per_day_booking ? $final_pending_qty / $per_day_booking : 0;
    //     $weekly_planning_days = $per_day_booking ? $allocated_product_qty / $per_day_booking : 0;

    //     $data = [
    //         'sap_id' => $sapId,
    //         'sap_orderNumber' => $sap['orderNumber'],
    //         'rm_correction' => $sap['rm_correction'],
    //         'plan_allocation' => $sap['plan_allocation'],
    //         'materialNumber' => $sap['materialNumber'],
    //         'materialDescription' => $sap['materialDescription'],
    //         'sap_plant' => $sap['plant'],
    //         'systemStatus' => $sap['systemStatus'],
    //         'orderQuantity_GMEIN' => $sap['orderQuantity_GMEIN'],
    //         'deliveredQuantity_GMEIN' => $sap['deliveredQuantity_GMEIN'],
    //         'confirmedQuantity_GMEIN' => $sap['confirmedQuantity_GMEIN'],
    //         'weekly_plan' => $sap['forge_commite_week'],
    //         'monthly_plan' => $sap['monthly_plan'],
    //         'monthly_fix_plan' => $sap['monthly_fix_plan'],
    //         'wom_plant' => $wom_plant,
    //         'unitOfMeasure_GMEIN' => $sap['unitOfMeasure_GMEIN'],
    //         'batch' => $sap['batch'],
    //         'work_order' => $batch,
    //         'reciving_date' => $reciving_date,
    //         'delivery_date' => $delivery_date,
    //         'wo_add_date' => $wo_add_date,
    //         'customer' => $customer,
    //         'responsible_person_name' => $responsible_person_name,
    //         'marketing_person_name' => $marketing_person_name,
    //         'finish_wt' => $finish_wt,
    //         'to_forge_qty' => $to_forge_qty,
    //         'to_forge_wt' => $to_forge_wt,
    //         'forged_so_far' => $forged,
    //         'this_month_forge_wt' => $this_month_forge_wt,
    //         'to_forge_rm_wt' => $to_forge_rm_wt,
    //         'total_allocation' => $total_alloc,
    //         'plan_print_qty' => $plan_print_qty,
    //         'this_month_forge_rm_wt' => $this_month_forge_rm_wt,
    //         'act_allocated_balance_rm_wt' => $act_balance_rm_wt,
    //         'allocated_product_qty' => $allocated_product_qty,
    //         'no_of_days_booking' => $no_days_booking,
    //         'no_of_day_weekly_planning' => $weekly_planning_days,
    //         'final_pending_qty' => $final_pending_qty,
    //         'pending_qty' => $pending_qty,
    //         'pending_wt' => $pending_wt,
    //         'pending_rm_wt' => $pending_rm_wt,
    //         'pending_from_outside' => $pending_from_outside,
    //         'per_of_efficiency' => $per_eff,
    //         'machine_speed' => $speed,
    //         'no_of_shift' => $shifts,
    //         'plan_no_of_machine' => $plan_mc,
    //         'per_day_booking' => $per_day_booking,
    //         'module_multiplier' => $mult
    //     ];

    //     if ($update) {
    //         $db->table('sap_calculated_summary')
    //             ->where('sap_id', $sapId)
    //             ->update($data);
    //     }
    // }
}
