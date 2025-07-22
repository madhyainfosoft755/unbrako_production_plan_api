<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\SapRmUpdateModel;
use App\Models\SapDataModel;
use CodeIgniter\HTTP\ResponseInterface;

class SapRmUpdateController extends ResourceController
{
    protected $format    = 'json';
    // GET: Get records for current month and sap_id from post
    public function index()
    {
        $data = $this->request->getJSON(true); // Get JSON as associative array
        $sapId = $data['sap_id'] ?? null;
        if (!$sapId) {
            return $this->respond([
                'status' => false,
                'message' => 'sap_id is required.'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $model = new SapRmUpdateModel();

        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');

        $records = $model->where('sap_id', $sapId)
                         ->where('date >=', $startOfMonth)
                         ->where('date <=', $endOfMonth)
                         ->findAll();

        return $this->respond([
            'status' => true,
            'data' => $records,
        ]);
    }

    // POST: Insert record only if today is Saturday and no existing record
    public function create()
    {
        $data = $this->request->getJSON(true); // Get JSON as associative array

        if (!isset($data['sap_ids']) || !is_array($data['sap_ids']) || count($data['sap_ids']) === 0) {
            return $this->fail('sap_ids is required and must be a non-empty array.');
        }

        $sapIds = array_filter($data['sap_ids'], fn($id) => is_numeric($id));
        $sapDataModel = new SapDataModel();

        $updateFields = [];

        $insertedBy = user_id();

        if(count($sapIds) == 1 && isset($data['rm_date']) && !empty($data['rm_date'])){
            if(!isset($data['rm_date']) && !is_numeric($data['rm_date'])){
                return $this->fail('sap_ids is required and must be a non-empty array.');
            }
            $sapId = $sapIds[0];
            if (!$sapId || !$insertedBy) {
                return $this->respond([
                    'status' => false,
                    'message' => 'sap_id and inserted_by are required.'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }
    
            $today = date('Y-m-d');
            $dayOfWeek = date('w'); // 6 = Saturday
    
            if ($dayOfWeek != 6) {
                return $this->respond([
                    'status' => false,
                    'message' => 'You can only insert data on Saturday.'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }
    
            $model = new SapRmUpdateModel();
    
            // Check if already exists
            $existing = $model->where('sap_id', $sapId)
                              ->where('date', $today)
                              ->first();
    
            if ($existing) {
                return $this->respond([
                    'status' => false,
                    'message' => 'Data already filled for today.'
                ], ResponseInterface::HTTP_CONFLICT);
            }
    
            // Insert record
            $model->insert([
                'sap_id' => $sapId,
                'date' => $today,
                'allocation'=>(int)$data['rm_date'],
                'inserted_by' => $insertedBy,
            ]);
    
        }


        if (isset($data['rm_correction']) && is_numeric($data['rm_correction'])) {
            $updateFields['rm_correction'] = (int)$data['rm_correction'];
        }
        if (isset($data['rm_delivery_date'])) {
            $date = new \DateTime($data['rm_delivery_date']);
            $date->setTimezone(new \DateTimeZone('Asia/Kolkata')); // Optional
            $mysqlDate = $date->format('Y-m-d');
            $updateFields['rm_delivery_date'] = $mysqlDate;
        }
        if (isset($data['rm_delivery']) && $data['rm_delivery'] === true) {
            $updateFields['is_rm_ready'] = 1;
            // Remove 'rm_delivery_date' if it's set
            if (isset($updateFields['rm_delivery_date'])) {
                unset($updateFields['rm_delivery_date']);
            }

        }
        if (empty($updateFields)) {
            return $this->fail('No valid update fields provided.');
        }
        $batchUpdateData = [];
        foreach ($sapIds as $id) {
            if ($sapDataModel->find($id)) {
                $row = array_merge(['id' => $id], $updateFields);
                $batchUpdateData[] = $row;
            }
        }
        if (!empty($batchUpdateData)) {
            $sapDataModel->updateBatch($batchUpdateData, 'id');
        }
        
        return $this->respondCreated([
            'status' => true,
            'message' => 'Data inserted successfully.'
        ]);

    }
}
