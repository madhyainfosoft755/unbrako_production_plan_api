<?php

namespace App\Models;

use CodeIgniter\Model;

class SapRmUpdateModel extends Model
{
    protected $table = 'sap_rm_update';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'sap_id',
        'date',
        'allocation',
        'inserted_at',
        'inserted_by',
    ];

    protected $useTimestamps = false;
}
