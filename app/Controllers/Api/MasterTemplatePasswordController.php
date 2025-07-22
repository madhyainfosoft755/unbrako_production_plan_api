<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\MasterTemplatesPasswordModel;

class MasterTemplatePasswordController extends ResourceController
{
    protected $masterTemplatesPasswordModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->masterTemplatesPasswordModel = new MasterTemplatesPasswordModel();
    }

    public function getTemplatePasswords(){
        $passwords = $this->masterTemplatesPasswordModel->select('template_name, password')->orderBy('template_name', 'ASC')->findAll();

        return $this->respond([
            'data' => $passwords
        ], 200); // HTTP 200 OK
    }
}
