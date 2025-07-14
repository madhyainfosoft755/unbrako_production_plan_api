<?php
namespace App\Models;
use CodeIgniter\Model;

class SapTempImportDataModel extends Model
{
    protected $table      = 'sap_temp_import_data';
    protected $returnType = 'array';
    protected $allowedFields = [
        'file_id', 'row_index', 'order_number', 'material', 'material_description',
        'plant', 'order_quantity', 'delivered_quantity', 'confirmed_quantity',
        'unit_of_measure', 'batch', 'system_status', 'start_date',
        'scheduled_finish_date', 'sales_order', 'error_json', 'created_at'
    ];
}
