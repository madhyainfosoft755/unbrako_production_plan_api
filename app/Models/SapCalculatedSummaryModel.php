<?php

namespace App\Models;

use CodeIgniter\Model;

class SapCalculatedSummaryModel extends Model
{
    protected $table            = 'sap_calculated_summary';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false; // Since `id` is NOT AUTO_INCREMENT

    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = []; // Not needed for read-only

    // Disable insert/update/delete
    // protected $allowEmptyInserts = false;
    protected $skipValidation    = true;

    protected $useTimestamps = false;

    // Make model strictly read-only
    public function insert($data = null, bool $returnID = true)
    {
        throw new \RuntimeException('Read-only model: insert() is not allowed.');
    }

    public function update($id = null, $data = null): bool
    {
        throw new \RuntimeException('Read-only model: update() is not allowed.');
    }

    public function delete($id = null, bool $purge = false): bool
    {
        throw new \RuntimeException('Read-only model: delete() is not allowed.');
    }


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
