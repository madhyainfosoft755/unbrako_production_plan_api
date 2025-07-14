<?php

namespace App\Models;

use CodeIgniter\Model;

class WOMTempImportWorkOrderModel extends Model
{
    protected $table      = 'wom_temp_import_workorders';
    protected $returnType = 'array';
    protected $allowedFields = [
        'file_id','row_index','plant','work_order_db','customer',
        'responsible_person_name','segment_name','marketing_person_name',
        'wo_add_date','reciving_date','delivery_date',
        'no_of_items','weight','error_json','created_at'
    ];
}
