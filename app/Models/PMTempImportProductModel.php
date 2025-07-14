<?php 
namespace App\Models;
use CodeIgniter\Model;

class PMTempImportProductModel extends Model
{
    protected $table      = 'pm_temp_import_products';
    protected $returnType = 'array';
    protected $allowedFields = [
        'file_id','row_index','order_no','material_number','material_number_froging',
        'material_description','machine_name','module','uom','seg2','seg3',
        'product_size','product_group','product_length','finish','segment',
        'finish_wt','cheese_wt','rm_spec','rod_dia1','drawn_dia1','special_remarks',
        'bom','rm_component','condition_raw_material','error_json','created_at'
    ];
}
