<?php

namespace App\Models;

use CodeIgniter\Model;

class SapCalculatedSummaryModel extends Model
{
    protected $table            = 'sap_calculated_summary';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false; // Since `id` is NOT AUTO_INCREMENT

    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = []; // Not needed for read-only

    // Disable insert/update/delete
    // protected $allowEmptyInserts = false;
    protected $skipValidation    = true;

    protected $useTimestamps = false;

    // Make model strictly read-only
    public function insert($data = null, bool $returnID = true)
    {
        throw new \RuntimeException('Read-only model: insert() is not allowed.');
    }

    public function update($id = null, $data = null): bool
    {
        throw new \RuntimeException('Read-only model: update() is not allowed.');
    }

    public function delete($id = null, bool $purge = false): bool
    {
        throw new \RuntimeException('Read-only model: delete() is not allowed.');
    }
}
