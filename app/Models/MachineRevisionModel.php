<?php

namespace App\Models;

use CodeIgniter\Model;

class MachineRevisionModel extends Model
{
    protected $table            = 'machine_revisions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "machine",
        "name",
        "disabled",
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
        "name"=> 'required|string|max_length[50]|is_unique[machine_revisions.name]'
    ];
    protected $validationMessages   = [
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
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
