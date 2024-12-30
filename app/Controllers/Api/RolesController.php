<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\RolesModel;
use App\Models\CustomUserModel;

class RolesController extends ResourceController
{
    protected $userModel;
    protected $rolesModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->userModel = new CustomUserModel();
        $this->rolesModel = new RolesModel();
    }

    public function addRole(){
        // Get input data
        $data = [
            'role'           => $this->request->getVar('role'),
            'created_by'      => auth()->user()->id
        ];

        // Validate the input data
        if (!$this->validate($this->rolesModel->validationRules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        // Save the roles
        if ($this->rolesModel->insert($data)) {
            return $this->respond([
                'status' => true,
                'message' => 'Role added successfully'
            ], 201); // HTTP 201 Created
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Failed to add Role'
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    public function getAllRoles(){
        $roles = $this->rolesModel->select('roles.role, roles.id, count(users.role) as employees')
        ->join('users', 'users.role = roles.id', 'left')
        ->groupBy('roles.id')
        ->orderBy('roles.id', 'ASC')->findAll();

        return $this->respond([
            'data' => $roles
        ], 200); // HTTP 200 OK
    }
}
