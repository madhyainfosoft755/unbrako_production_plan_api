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
        "no_of_shift",
        "capacity",
        "plan_no_of_mc",
        "per_of_efficiency",
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
        "no_of_mc"            => 'required|numeric|greater_than_equal_to[1]',
        "process"             => 'required',
        "speed"               => 'required|numeric|greater_than_equal_to[1]',
        "no_of_shift"         => 'required|numeric|greater_than_equal_to[1]',
        "capacity"            => 'required|numeric|greater_than_equal_to[1]',
        "plan_no_of_mc"       => 'required|numeric|greater_than_equal_to[1]',
        "per_of_efficiency"   => 'required|numeric|greater_than_equal_to[1]',
    ];
    protected $validationMessages = [
        'name' => [
            'required'    => 'Name is required',
            'string'      => 'Name must be a string',
            'max_length'  => 'Name must be less than 50 characters',
            'is_unique'   => 'Name already exists',
        ],
        'no_of_mc' => [
            'required'                 => 'Count is required',
            'numeric'                  => 'Count must be a number',
            'greater_than_equal_to'   => 'Count must be at least 1',
        ],
        'process' => [
            'required' => 'Process is required',
        ],
        'speed' => [
            'required'                 => 'Speed is required',
            'numeric'                  => 'Speed must be a number',
            'greater_than_equal_to'   => 'Speed must be at least 1',
        ],
        'no_of_shift' => [
            'required'                 => 'Number of shifts is required',
            'numeric'                  => 'Number of shifts must be a number',
            'greater_than_equal_to'   => 'Number of shifts must be at least 1',
        ],
        'capacity' => [
            'required'                 => 'Capacity is required',
            'numeric'                  => 'Capacity must be a number',
            'greater_than_equal_to'   => 'Capacity must be at least 1',
        ],
        'plan_no_of_mc' => [
            'required'                 => 'Planned machine count is required',
            'numeric'                  => 'Planned machine count must be a number',
            'greater_than_equal_to'   => 'Planned machine count must be at least 1',
        ],
        'per_of_efficiency' => [
            'required'                 => 'Efficiency is required',
            'numeric'                  => 'Efficiency must be a number',
            'greater_than_equal_to'   => 'Efficiency must be at least 1',
        ],
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
