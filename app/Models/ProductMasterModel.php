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
        'order_number', 'material_number', 'material_description', 'unit_of_measure',
        'machine', 'seg2', 'seg3', 'segment', 'finish', 'prod_group',
        'cheese_wt', 'finish_wt', 'size', 'length', 'spec',
        'rod_dia1', 'drawn_dia1', 'condition_of_rm', 'created_by', 'updated_by'
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
        'order_number'       => 'required|max_length[10]|is_unique[product_master.order_number]',
        'material_number'    => 'required|max_length[10]',
        'material_description' => 'required|max_length[1000]',
        'unit_of_measure'    => 'required|max_length[5]',
        'machine'            => 'required|integer',
    ];
    protected $validationMessages   = [
        'order_number' => [
            'required' => 'Order number is required.',
            'max_length' => 'Order number cannot exceed 10 characters.',
            'is_unique' => 'Order number must be unique.',
        ],
        'material_number' => [
            'required' => 'Material number is required.',
        ],
        'material_description' => [
            'required' => 'Material description is required.',
        ],
        'unit_of_measure' => [
            'required' => 'Unit of measure is required.',
        ],
        'machine' => [
            'required' => 'Machine is required.',
        ],
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

    // The callback method to set the timestamps
    protected function setTimestamps(array $data)
    {
        // Add current timestamp to the 'updated_at' field
        $data['data']['updated_at'] = date('Y-m-d H:i:s');  // Current timestamp in 'Y-m-d H:i:s' format

        return $data;
    }
}
