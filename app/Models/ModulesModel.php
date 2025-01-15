<?php

namespace App\Models;

use CodeIgniter\Model;

class ModulesModel extends Model
{
    protected $table            = 'modules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "name",
        "responsible",
        "description",
        "created_by",
        "updated_at",
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
        'name'      => 'required|string|max_length[50]|is_unique[modules.name]',
        'responsible' => 'required'
    ];
    protected $validationMessages   = [
        'name'      => [
            'required' => 'Module name is required', 
            'string' => 'Module name must be a string',
            'max_length' => 'Module name must be less than 50 characters',
            'is_unique' => "Module name already exists"
        ],
        'responsible' =>[
            'required' => 'Module name is required',
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
