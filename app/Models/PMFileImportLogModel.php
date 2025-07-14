<?php
namespace App\Models;
use CodeIgniter\Model;

class PMFileImportLogModel extends Model
{
    protected $table      = 'pm_file_import_logs';
    protected $returnType = 'array';
    protected $allowedFields = [
        'original_name', 'stored_name', 'uploaded_by',
        'status', 'created_at', 'processed_at'
    ];
}
