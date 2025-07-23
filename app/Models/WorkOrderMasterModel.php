<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkOrderMasterModel extends Model
{
    protected $table            = 'work_order_master';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "plant",
        "work_order_db",
        "customer",
        "created_by",
        "updated_by",
        "quality_inspection_required",
        "responsible_person",
        "marketing_person",
        "responsible_person_name",
        "marketing_person_name",
        "segment",
        "reciving_date",
        "delivery_date",
        "wo_add_date",
        "no_of_items",
        "weight"
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'plant'      => 'required',
        'work_order_db'      => 'required|string|max_length[6]',
        'customer'      => 'required'
    ];
    protected $validationMessages   = [
        'work_order_db'      => [
            'required' => 'Required', 
            'string' => 'Must be a string',
            'max_length' => 'Must be 5 characters long'
        ],
        'plant'      => [
            'required' => 'Required'
        ],
        'customer'      => [
            'required' => 'Required'
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = ['setTimestamps'];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    // The callback method to set the timestamps
    protected function setTimestamps(array $data)
    {
        // Add current timestamp to the 'updated_at' field
        $data['data']['updated_at'] = date('Y-m-d H:i:s');  // Current timestamp in 'Y-m-d H:i:s' format

        return $data;
    }
}
