<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterTemplatesPasswordModel extends Model
{
    protected $table            = 'master_templates_password';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['template_name', 'password'];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = ''; // no updated_at in schema
}
