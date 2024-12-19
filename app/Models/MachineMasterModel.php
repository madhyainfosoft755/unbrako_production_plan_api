<?php

namespace App\Models;

use CodeIgniter\Model;

class MachineMasterModel extends Model
{
    protected $table            = 'machine_module_master';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "module", //
        "machine_rev",
        "created_by",
        "updated_by"
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
        "module"=> 'required',
        "machine_rev"=> 'required'
    ];
    protected $validationMessages   = [
        'machine_rev'      => [
            'required' => 'Machine is required'
        ],
        'module'      => [
            'required' => 'Name is required'
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
