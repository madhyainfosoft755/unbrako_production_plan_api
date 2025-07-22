<?php

namespace App\Models;

use CodeIgniter\Model;

class WeeklyPlanningDataModel extends Model
{
    protected $table = 'weekly_planning_data';
    protected $primaryKey = 'id';
    protected $returnType       = 'array';
    protected $allowedFields = [
        'weekly_planning_id',
        'machine_id',
        'machine_name',
        'no_of_machines',
        'plan_no_of_machines',
        'machine_speed',
        'no_of_shift',
        'per_of_efficiency',
        'no_of_days_booking',
        'pending_wt',
        'no_of_day_weekly_planning',
        'allocated_product_wt',
        'capacity',
        'single_mc_shift_capacity',
        'rm_tpm_booking',
        'rm_due_to_development',
        'gap',
        'updated_at',
        'updated_by'
    ];
    protected $useTimestamps = true;
}
