<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\WorkOrderMasterModel;
use App\Models\PlantModel;
use App\Models\CustomersModel;
use App\Models\SegmentsModel;

class WorkOrderMasterController extends ResourceController
{

    protected $workOrderMasterModel;
    protected $plantModel;
    protected $customersModel;
    protected $segmentsModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->workOrderMasterModel = new WorkOrderMasterModel();
        $this->plantModel = new PlantModel();
        $this->customersModel = new CustomersModel();
        $this->segmentsModel = new SegmentsModel();
    }

    public function getAllData(){
        $data = $this->workOrderMasterModel->select('work_order_master.id, plant, work_order_db, customer, quality_inspection_required, responsible.name as responsible_person_name, responsible_person, marketing.name as marketing_person_name, marketing_person, segments.name as segment_name, segment, reciving_date, delivery_date, work_order_master.created_at')
            ->join('segments', 'segments.id = work_order_master.segment')
            ->join('users as responsible', 'responsible.id = work_order_master.responsible_person')
            ->join('users as marketing', 'marketing.id = work_order_master.marketing_person')
            ->orderBy('work_order_master.created_at', 'DESC')->findAll();

        return $this->respond([
            'data' => $data
        ], 200); // HTTP 200 OK
    }


    public function addWorkOrderMaster(){
        $plant = $this->request->getVar('plant');
        $customer = $this->request->getVar('customer');
        $qir = $this->request->getVar('quality_inspection_required');
        $responsible_person = $this->request->getVar('responsible_person');
        $marketing_person = $this->request->getVar('marketing_person');
        $segment = $this->request->getVar('segment');
        $reciving_date = $this->request->getVar('reciving_date');
        $delivery_date = $this->request->getVar('delivery_date');
        // $plantDetails = $this->plantModel->find($plant);
        // // print_r(auth()->user()->role); die;
        // if (!$plantDetails) {
        //     return service('response')->setJSON([
        //         'status' => false,
        //         'message' => 'Plant not found'
        //     ])->setStatusCode(404);
        // }
        $segmentDetails = $this->segmentsModel->find($segment);
        // print_r(auth()->user()->role); die;
        if (!$segmentDetails) {
            return service('response')->setJSON([
                'status' => false,
                'message' => 'Segment not found'
            ])->setStatusCode(404);
        }

        // $customerDetails = $this->customersModel->find($customer);
        // // print_r(auth()->user()->role); die;
        // if (!$customerDetails) {
        //     return service('response')->setJSON([
        //         'status' => false,
        //         'message' => 'Customer not found'
        //     ])->setStatusCode(404);
        // }
        // Get input data
        $data = [
            'plant'           => $plant,
            'work_order_db'           => $this->request->getVar('work_order_db'),
            'quality_inspection_required'           => empty($qir)? 0: $qir,
            'customer'           => $customer,
            'responsible_person'           => $responsible_person,
            'marketing_person'           => $marketing_person,
            'segment'           => $segment,
            'reciving_date'           => $reciving_date,
            'delivery_date'           => $delivery_date,
            'created_by'      => auth()->user()->id
        ];

        // Validate the input data
        if (!$this->validate($this->workOrderMasterModel->validationRules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // HTTP 400 Bad Request
        }

        // Save the name
        if ($this->workOrderMasterModel->insert($data)) {
            return $this->respond([
                'status' => true,
                'message' => 'Added successfully'
            ], 201); // HTTP 201 Created
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Failed to add'
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    public function updateWorkOrderMaster($id)
    {
        // Get input data
        $plant = $this->request->getVar('plant');
        $customer = $this->request->getVar('customer');
        $qir = $this->request->getVar('quality_inspection_required');
        
        // Fetch existing work order by ID
        $workOrder = $this->workOrderMasterModel->find($id);
        
        if (!$workOrder) {
            return service('response')->setJSON([
                'status' => false,
                'message' => 'Work Order not found'
            ])->setStatusCode(404); // Work order not found
        }
        
        // Check if plant exists
        $plantDetails = $this->plantModel->find($plant);
        if (!$plantDetails) {
            return service('response')->setJSON([
                'status' => false,
                'message' => 'Plant not found'
            ])->setStatusCode(404); // Plant not found
        }

        // Check if customer exists
        $customerDetails = $this->customersModel->find($customer);
        if (!$customerDetails) {
            return service('response')->setJSON([
                'status' => false,
                'message' => 'Customer not found'
            ])->setStatusCode(404); // Customer not found
        }

        // Prepare the data for update
        $data = [
            'plant' => $plant,
            'work_order_db' => $this->request->getVar('work_order_db'),
            'quality_inspection_required' => (isset($qir) && $qir !== null) ? $qir : $workOrder['quality_inspection_required'],
            'customer' => $customer,
            'updated_by' => auth()->user()->id, // Track who updated the record
        ];
        // print_r($data); die;
        // Validate the input data
        if (!$this->validate($this->workOrderMasterModel->validationRules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ], 400); // Validation failed
        }

        // Update the record
        if ($this->workOrderMasterModel->update($id, $data)) {
            return $this->respond([
                'status' => true,
                'message' => 'Work Order updated successfully'
            ], 200); // HTTP 200 OK
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Failed to update Work Order'
            ], 500); // HTTP 500 Internal Server Error
        }
    }


    public function getCustomerNames($name_contains){
        $data = $this->workOrderMasterModel->select('customer')
            ->like('customer', $name_contains)
            ->orderBy('customer', 'ASC')->findAll();

        return $this->respond([
            'data' => $data
        ], 200); // HTTP 200 OK
    }

    
}
