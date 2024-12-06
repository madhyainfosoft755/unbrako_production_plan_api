<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductMasterModel extends Model
{
    protected $table            = 'product_master';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "order_number",
        "material_info"
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
        'order_number'      => 'required|string|max_length[50]|is_unique[product_master.order_number]',
        'material_info'      => 'required'
    ];
    protected $validationMessages   = [
        'order_number'      => [
            'required' => 'Order number is required',
            'string' => 'Order number must be a string',
            'max_length' => 'Order number must be less than 50 characters',
            'is_unique' => "Order number already exists"
        ],
        'material_info'      => [
            'required' => 'Material info is required'
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
