<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyModuleShiftQtyUpdateModel extends Model
{
    protected $table      = 'daily_module_shift_qty_update'; // Replace with your actual table name
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'module_shift_id',
        'module_id',
        'sap_id',
        'machine_id',
        'material_number',
        'pending_qty',
        'production_qty',
        'timestamp'
    ];

    protected $useTimestamps = false;
    protected $returnType    = 'array';
}
