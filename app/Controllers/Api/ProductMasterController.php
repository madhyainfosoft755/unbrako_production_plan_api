<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\MachineMasterModel;
use CodeIgniter\API\ResponseTrait;
use App\Models\MachineShifts;
use App\Models\ProductMasterModel;

class ProductMasterController extends ResourceController
{
    use ResponseTrait;

    protected $machineModel;
    protected $machineShifts;
    protected $productMasterModel;

    public function __construct()
    {
        $this->machineModel = new MachineMasterModel();
        $this->machineShifts = new MachineShifts();
        $this->productMasterModel = new ProductMasterModel();
    }

    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface
     */
    public function index()
    {
        //
    }

    /**
     * Return the properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function show($id = null)
    {
        //
    }

    /**
     * Return a new resource object, with default properties.
     *
     * @return ResponseInterface
     */
    public function new()
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters.
     *
     * @return ResponseInterface
     */
    public function create()
    {
        //
    }

    /**
     * Return the editable properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Add or update a model resource, from "posted" properties.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function update($id = null)
    {
        //
    }

    /**
     * Delete the designated resource object from the model.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function delete($id = null)
    {
        //
    }


    public function getAllProductMaster()
    {

        $productMaster = $this->productMasterModel->select('product_master.*, 
                    machines.name as machine_name, 
                    responsible.name as responsible_name, 
                    machine_revisions.name as machine_1, 
                    seg_2.name as seg2_name, 
                    seg_3.name as seg3_name, 
                    modules.name as module_name,
                    segments.name as segment_name, 
                    finish.name as finish_name, 
                    groups.name as group_name')
            ->join('machine_revisions', 'machine_revisions.id = product_master.machine', 'inner')
            ->join('machines', 'machines.id = machine_revisions.machine', 'inner')
            ->join('machine_module_master', 'machine_module_master.machine_rev = machine_revisions.id', 'inner')
            ->join('modules', 'modules.id = machine_module_master.module', 'inner')
            ->join('users as responsible', 'responsible.id = modules.responsible', 'inner')
            ->join('seg_2', 'seg_2.id = product_master.seg2', 'left')
            ->join('seg_3', 'seg_3.id = product_master.seg3', 'left')
            ->join('segments', 'segments.id = product_master.segment', 'left')
            ->join('finish', 'finish.id = product_master.finish', 'left')
            ->join('groups', 'groups.id = product_master.prod_group', 'left')
            ->get()
            ->getResultArray();


        if ($productMaster) {
            return $this->respond([
                'status'  => true,
                'message' => 'Products found',
                'data'    => $productMaster
            ], 200); // HTTP 200 OK
        } else {
            return $this->respond([
                'status'  => false,
                'message' => 'No products found'
            ], 404); // HTTP 404 Not Found
        }
    }

    public function partNumberInfo($machine_rev_name){
        
        $data = $this->productMasterModel->select('
                product_master.id, 
                product_master.material_number, 
                product_master.material_description, 
                product_master.cheese_wt, 
                product_master.finish_wt, 
                machines.name AS machine_name, 
                machines.speed AS speed
            ')
            ->join('machine_revisions', 'machine_revisions.id = product_master.machine')
            ->join('machines', 'machines.id = machine_revisions.machine')
            ->where('machine_revisions.name', $machine_rev_name)
            ->orderBy('product_master.material_number', 'ASC')->get()
            ->getResultArray();

        return $this->respond([
            'status'  => true,
            'message' => 'Data Found',
            'data'    => $data
        ], 200);
    }
}
