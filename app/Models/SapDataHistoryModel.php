<?php
namespace App\Models;

use CodeIgniter\Model;


class SapDataHistoryModel extends Model
{
    protected $table = 'sap_data_history';
    protected $primaryKey = 'id'; // Replace with your actual primary key
   
    protected $allowedFields = ['sapId', 'orderNumber', 'plant', 'materialNumber', 'materialDescription', 'orderQuantity_GMEIN', 'deliveredQuantity_GMEIN', 'confirmedQuantity_GMEIN', 'unitOfMeasure_GMEIN', 'batch', 'sequenceNumber', 'createdOn', 'orderType', 'systemStatus', 'enteredBy', 'postingDate', 'statusProfile', 'insertedTimestamp', 'insertedBy',
    'forge_commite_week', 'this_month_forge_qty', 'plan_no_of_mc', 'special_remarks', 'rm_delivery_date', 'advance_final_rm_wt', 'rm_allocation_priority', 'rm_allocation_priority',  'updated_at' ];  // Define the allowed fields

}
