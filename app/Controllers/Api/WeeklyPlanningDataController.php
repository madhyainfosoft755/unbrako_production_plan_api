<?php

namespace App\Controllers;

use App\Models\WeeklyPlanningDataModel;
use CodeIgniter\RESTful\ResourceController;

class MachinePlanning extends ResourceController
{
    protected $weeklyPlanningDataModel = 'App\Models\WeeklyPlanningDataModel';
    protected $format    = 'json';

    public function index()
    {
        return $this->respond($this->weeklyPlanningDataModel->findAll());
    }

    public function show($id = null)
    {
        $data = $this->weeklyPlanningDataModel->find($id);
        return $data ? $this->respond($data) : $this->failNotFound("Data not found");
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if ($this->weeklyPlanningDataModel->insert($data)) {
            return $this->respondCreated(['message' => 'Inserted successfully']);
        }
        return $this->failValidationErrors($this->weeklyPlanningDataModel->errors());
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        if ($this->weeklyPlanningDataModel->update($id, $data)) {
            return $this->respond(['message' => 'Updated successfully']);
        }
        return $this->failValidationErrors($this->weeklyPlanningDataModel->errors());
    }

    public function delete($id = null)
    {
        if ($this->weeklyPlanningDataModel->delete($id)) {
            return $this->respondDeleted(['message' => 'Deleted successfully']);
        }
        return $this->failNotFound("Data not found");
    }
}
