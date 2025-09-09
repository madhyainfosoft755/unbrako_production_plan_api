<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\MasterTemplatesPasswordModel;

use App\Models\SAPFileImportLogModel;
use App\Models\WOMFileImportLogModel;
use App\Models\PMFileImportLogModel;

class MasterTemplatePasswordController extends ResourceController
{
    protected $masterTemplatesPasswordModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->masterTemplatesPasswordModel = new MasterTemplatesPasswordModel();
    }

    public function getTemplatePasswords($templateName){
        $record = $this->masterTemplatesPasswordModel
        ->select('password')
        ->where('template_name', $templateName)
        ->first(); // get only one result

        if ($record) {
            return $this->respond([
                'password' => $record['password'],
                'status' => true,
            ], 200); // HTTP 200 OK
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Template password not found'
            ], 200); // HTTP 404 Not Found
        }
    }

    public function releaseTemplate($templateName){
        if($templateName === 'PMT' || $templateName === 'SAPT' || $templateName === 'WOMT'){
            if($templateName === 'PMT'){
                $model = new PMFileImportLogModel();
            } if ($templateName === 'SAPT'){
                $model = new SAPFileImportLogModel();
            } if ($templateName === 'WOMT'){
                $model = new WOMFileImportLogModel();
            }
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Invalid Template Code.'
            ], 400); // HTTP 400 Bad Request
        }
        // Update all records where status is 'pending' or 'processing'
        $builder = $model->builder();
        $builder->whereIn('status', ['pending', 'processing']);
        $update = $builder->update(['status' => 'failed']);

        if ($update) {
            return $this->respond([
                'status' => true,
                'message' => "Records marked as failed successfully for template {$templateName}."
            ], 200); // HTTP 200 OK
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Failed to update records.'
            ], 500); // HTTP 500 Internal Server Error
        }
    }
}
