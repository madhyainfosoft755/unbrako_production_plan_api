<?php

namespace App\Models;

use CodeIgniter\Model;

class SapCalculatedSummaryModel extends Model
{
    protected $table            = 'sap_calculated_summary';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false; // Since `id` is NOT AUTO_INCREMENT

    protected $returnType       = 'array';
    // protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'id',
    'sap_id',
    'sap_orderNumber',
    'pm_order_number',
    'systemStatus',
    'orderQuantity_GMEIN',
    'deliveredQuantity_GMEIN',
    'confirmedQuantity_GMEIN',
    'monthly_plan',
    'monthly_fix_plan',
    'weekly_plan',
    'materialNumber',
    'materialDescription',
    'sap_plant',
    'wom_plant',
    'unitOfMeasure_GMEIN',
    'batch',
    'work_order',
    'reciving_date',
    'delivery_date',
    'wo_add_date',
    'work_order_db',
    'customer',
    'responsible_person_name',
    'marketing_person_name',
    'module_responsible_person_id',
    'module_responsible_person_name',
    'wom_segment_id',
    'wom_segment_name',
    'quality_inspection_required',
    'finish_wt',
    'to_forge_qty',
    'to_forge_wt',
    'forged_so_far',
    'this_month_forge_wt',
    'module_id',
    'module_name',
    'seg2_id',
    'seg2_name',
    'seg3_id',
    'seg3_name',
    'finish_id',
    'finish_name',
    'group_id',
    'group_name',
    'machine_name',
    'machine_id',
    'no_of_machines',
    'cheese_wt',
    'size',
    'length',
    'spec',
    'rod_dia1',
    'drawn_dia1',
    'condition_of_rm',
    'pm_special_remarks',
    'main_special_remarks',
    'pm_bom',
    'rm_component',
    'rm_allocation_priority',
    'priority_list',
    'advance_final_rm_wt',
    'rm_delivery_date',
    'module_multiplier',
    'to_forge_rm_wt',
    'total_allocation',
    'total_allocation_2',
    'plan_print_qty',
    'this_month_forge_rm_wt',
    'act_allocated_balance_rm_wt',
    'allocated_balance_rm_wt',
    'rm_correction',
    'plan_allocation',
    'allocated_product_wt',
    'allocated_product_qty',
    'per_of_efficiency',
    'machine_speed',
    'no_of_shift',
    'plan_no_of_machine',
    'per_day_booking',
    'final_pending_qty',
    'pending_qty',
    'pending_wt',
    'surface_treatment_process_id',
    'surface_treatment_process_name',
    'pending_rm_wt',
    'pending_from_outside_1',
    'pending_from_outside',
    'no_of_days_booking',
    'product_master_id',
    'work_order_master_id',
    'no_of_day_weekly_planning',
    'deleted_at',
    'created_at'
    ]; // Not needed for read-only

    // Disable insert/update/delete
    // protected $allowEmptyInserts = false;
    protected $skipValidation    = true;

    protected $useTimestamps = false;

    // Make model strictly read-only
    public function insert($data = null, bool $returnID = true)
    {
        throw new \RuntimeException('Read-only model: insert() is not allowed.');
    }

    // public function update($id = null, $data = null): bool
    // {
    //     throw new \RuntimeException('Read-only model: update() is not allowed.');
    // }

    // public function delete($id = null, bool $purge = false): bool
    // {
    //     throw new \RuntimeException('Read-only model: delete() is not allowed.');
    // }


    public function getSummaryRows(array $conditions = [], $groupBy = null, $orderBy = null)
    {
        $builder = $this->builder();
        $builder->select('machines.capacity as single_mc_shift_capacity, machines.speed as capacity, machine_id, machine_name, no_of_machines, plan_no_of_machine, machine_speed, sap_calculated_summary.no_of_shift as no_of_shift, sap_calculated_summary.per_of_efficiency as per_of_efficiency, no_of_days_booking, pending_wt, no_of_day_weekly_planning, allocated_product_wt,
        0 AS rm_tpm_booking,
        0 AS rm_due_to_development,
        0 AS gap')
        ->join('machines', 'machines.id = machine_id', 'left');

        if (!empty($conditions)) {
            $builder->where($conditions);
        }

        if($groupBy){
            $builder->groupBy($groupBy);
        }

        if($orderBy){
            $builder->orderBy($orderBy, 'ASC');
        }

        return $builder->get()->getResultArray();
    }



    public function getSapData($filters = [], $orderBy = [], $limit = null, $offset = null)
    {
        $builder = $this->builder();
        $query = $builder->select('*');

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
            return $query->get()->getResult();
    }
}
