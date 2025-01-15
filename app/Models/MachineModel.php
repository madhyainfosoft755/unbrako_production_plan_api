<?php

namespace App\Models;

use CodeIgniter\Model;

class MachineModel extends Model
{
    protected $table            = 'machines';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "name",
        "no_of_mc",
        "process",
        "speed",
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
        "name"=> 'required|string|max_length[50]|is_unique[machines.name]',
        "no_of_mc"=> 'required',
        "process"=> 'required'
    ];
    protected $validationMessages   = [
        'no_of_mc'      => [
            'required' => 'Count is required'
        ],
        'process'      => [
            'required' => 'Process is required'
        ],
        'name'      => [
            'required' => 'Name is required', 
            'string' => 'Name must be a string',
            'max_length' => 'Name must be less than 50 characters',
            'is_unique' => "Name already exists"
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
