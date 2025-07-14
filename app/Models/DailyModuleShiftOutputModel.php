<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyModuleShiftOutputModel extends Model
{
    protected $table      = 'daily_module_shift_output';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'supervisor',
        'shift',
        'date',
        'timestamp',
        'is_permanent'
    ];

    protected $useTimestamps = false; // Since you're using a manual timestamp
    protected $returnType    = 'array'; // or 'object' based on your preference
}
