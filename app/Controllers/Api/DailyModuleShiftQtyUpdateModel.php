<?php

namespace App\Controllers\Api;

use App\Models\DailyModuleShiftQtyUpdateModel;
use CodeIgniter\RESTful\ResourceController;

class DailyModuleShiftQtyUpdateModelController extends ResourceController
{
    protected $daily_module_shift_qty_update_model;
    protected $format    = 'json';

    public function __construct()
    {
        // Load models in the constructor
        $this->daily_module_shift_qty_update_model = new DailyModuleShiftQtyUpdateModel();
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!$this->model->insert($data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respondCreated($data);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        if (!$this->model->update($id, $data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond($data);
    }

    public function delete($id = null)
    {
        if (!$this->model->find($id)) {
            return $this->failNotFound("Record with ID $id not found.");
        }

        $this->model->delete($id);
        return $this->respondDeleted(['id' => $id]);
    }
}
