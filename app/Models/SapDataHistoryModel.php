<?php
namespace App\Models;

use CodeIgniter\Model;


class SapDataHistoryModel extends Model
{
    protected $table = 'sap_data_history';
    protected $primaryKey = 'id'; // Replace with your actual primary key
   
    protected $allowedFields = ['orderNumber', 'plant', 'materialNumber', 'materialDescription', 'orderQuantity_GMEIN', 'deliveredQuantity_GMEIN', 'confirmedQuantity_GMEIN', 'unitOfMeasure_GMEIN', 'batch', 'sequenceNumber', 'createdOn', 'orderType', 'systemStatus', 'enteredBy', 'postingDate', 'statusProfile', 'insertedTimestamp', 'insertedBy'];  // Define the allowed fields

}
