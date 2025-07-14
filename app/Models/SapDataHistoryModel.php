<?php
namespace App\Models;

use CodeIgniter\Model;


class SapDataHistoryModel extends Model
{
    protected $table = 'sap_data_history';
    protected $primaryKey = 'id'; // Replace with your actual primary key
   
    protected $allowedFields = ['sapId', 'orderNumber', 'plant', 'materialNumber', 'materialDescription', 'orderQuantity_GMEIN', 'deliveredQuantity_GMEIN', 'confirmedQuantity_GMEIN', 'unitOfMeasure_GMEIN', 'to_forge_qty', 'to_forge_limit_inc', 'forged_so_far', 'batch', 'startDate', 'salesOrder', 'systemStatus', 'scheduledFinishDate', 'insertedTimestamp', 'insertedBy',
    'forge_commite_week', 'this_month_forge_qty', 'special_remarks', 'is_rm_ready', 'rm_delivery_date', 'monthly_plan', 'monthly_fix_plan', 'rm_allocation_priority', 'rm_correction', 'plan_allocation', 'updated_at', 'updated_by' ];  // Define the allowed fields

}
