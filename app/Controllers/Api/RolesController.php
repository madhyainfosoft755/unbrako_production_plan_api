<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\CustomUserModel;

class RolesController extends ResourceController
{
    protected $userModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->userModel = new CustomUserModel();
    }

    public function getAllRoles(){
        $roles = $this->userModel->select('role, count(*) as employees')
        ->groupBy('role')
        ->orderBy('role', 'ASC')->findAll();

        return $this->respond([
            'data' => $roles
        ], 200); // HTTP 200 OK
    }
}
