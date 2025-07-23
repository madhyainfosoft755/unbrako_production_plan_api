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
  
    protected $allowedFields = ['orderNumber', 'plant', 'materialNumber', 'materialDescription', 'orderQuantity_GMEIN', 'deliveredQuantity_GMEIN', 'confirmedQuantity_GMEIN', 'unitOfMeasure_GMEIN', 'to_forge_qty', 'to_forge_limit_inc', 'forged_so_far', 'batch', 'startDate', 'salesOrder', 'systemStatus', 'scheduledFinishDate', 'insertedTimestamp', 'insertedBy',
    'forge_commite_week', 'this_month_forge_qty', 'special_remarks', 'is_rm_ready', 'surface_treatment_process', 'priority_list', 'rm_delivery_date', 'monthly_plan', 'monthly_fix_plan', 'rm_allocation_priority', 'rm_correction', 'plan_allocation', 'updated_at', 'updated_by'   ];  // Define the allowed fields

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
}
