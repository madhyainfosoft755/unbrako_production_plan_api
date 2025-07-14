<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\MachineMasterModel;
use CodeIgniter\API\ResponseTrait;
use App\Models\MachineShifts;
use App\Models\ProductMasterModel;


use App\Models\SegmentsModel;
use App\Models\Seg2Model;
use App\Models\Seg3Model;
use App\Models\FinishModel;
use App\Models\GroupsModel;
use App\Models\ModulesModel;
use App\Models\MachineRevisionModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Protection;

use App\Models\PMFileImportLogModel;
use App\Models\PMTempImportProductModel;

class ProductMasterController extends ResourceController
{
    use ResponseTrait;

    protected $machineModel;
    protected $machineShifts;
    protected $productMasterModel;
    protected $segmentsModel;
    protected $seg2Model;
    protected $seg3Model;
    protected $finishModel;
    protected $groupsModel;
    protected $modulesModel;
    protected $machineRevisionModel;
    protected $format    = 'json';
    protected $pm_file_import_log_model;
    protected $pm_temp_import_products_model;

    public function __construct()
    {
        $this->machineModel = new MachineMasterModel();
        $this->machineShifts = new MachineShifts();
        $this->productMasterModel = new ProductMasterModel();
        $this->segmentsModel = new SegmentsModel();
        $this->seg2Model = new Seg2Model();
        $this->seg3Model = new Seg3Model();
        $this->finishModel = new FinishModel();
        $this->groupsModel = new GroupsModel();
        $this->modulesModel = new ModulesModel();
        $this->machineRevisionModel = new MachineRevisionModel();
        $this->pm_file_import_log_model = new PMFileImportLogModel();
        $this->pm_temp_import_products_model = new PMTempImportProductModel();
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
         // Retrieve data of Form-Data
        // $data = $this->request->getPost();
         // Retrieve JSON data
        $data = $this->request->getJSON(true); // Convert JSON to associative array
        // print_r($data); die;
        $machine_module_master = $this->machineModel->select('*')->where(['module'=> $data['machine_module'], 'machine_rev'=>$data['machine']])->get()->getResultArray();
    
        if(count($machine_module_master)>0){
            $data['machine_module_master_id'] = $machine_module_master[0]['id'];
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Machine Master details not found'
            ], 400); // HTTP 400 Bad Request
        }
        // print_r($data); die;
        $data['created_by'] = auth()->user()->id; // Use Shield login

        if (!$this->validate($this->productMasterModel->getValidationRules())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try{
            if ($this->productMasterModel->insert($data)) {
                return $this->respondCreated([
                    'status'  => 'success',
                    'message' => 'Product added successfully.',
                    'data'    => $data,
                ]);
            }
        }
        catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            // Check if the error is due to foreign key constraint violation
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                if (strpos($e->getMessage(), 'machine-pm') !== false) {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Machine does not exist'
                    ], 400); // HTTP 400 Bad Request
                }
                if (strpos($e->getMessage(), 'segment-pm') !== false) {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Segment does not exist'
                    ], 400); // HTTP 400 Bad Request
                }
                if (strpos($e->getMessage(), 'finish-pm') !== false) {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Finish does not exist'
                    ], 400); // HTTP 400 Bad Request
                }
                if (strpos($e->getMessage(), 'group-pm') !== false) {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Group does not exist'
                    ], 400); // HTTP 400 Bad Request
                }
                if (strpos($e->getMessage(), 'seg2-pm') !== false) {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Seg2 does not exist'
                    ], 400); // HTTP 400 Bad Request
                }
                if (strpos($e->getMessage(), 'seg3-pm') !== false) {
                    return $this->respond([
                        'status' => false,
                        'message' => 'Seg3 does not exist'
                    ], 400); // HTTP 400 Bad Request
                }
                return $this->respond([
                    'status' => false,
                    // 'message' => 'Foreign key constraint violation. Machine does not exist.'
                    // 'message' => 'Machine does not exist.',
                    // 'message' => 'Database error occurred: ' . $e->getMessage()
                    'message' => 'Foreign key constraint violation'
                ], 400); // HTTP 400 Bad Request
            }
        }

        return $this->fail('Failed to create product.');
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
        if (!$id || !$this->model->find($id)) {
            return $this->failNotFound('Product not found.');
        }

        $data = $this->request->getPost();

        if (!$this->validate($this->model->getValidationRules())) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if ($this->model->update($id, $data)) {
            return $this->respond([
                'status'  => 'success',
                'message' => 'Product updated successfully.',
                'data'    => $data,
            ]);
        }

        return $this->fail('Failed to update product.');
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
        $request = $this->request;
        $postData = $request->getJSON(true); // Get POST data as array

        // Get page from query param (e.g., ?page=2), default to 1
        $page = (int) $this->request->getGet('page');
        $page = max($page, 1); // Ensure at least 1
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $builder = $this->productMasterModel->select('product_master.*, 
                    machines.name as machine_name, 
                    responsible.name as responsible_name, 
                    product_master.special_remarks, product_master.bom, product_master.rm_component,
                    machine_revisions.name as machine_1, 
                    seg_2.name as seg2_name, 
                    seg_3.name as seg3_name, 
                    modules.name as module_name,
                    segments.name as segment_name, 
                    finish.name as finish_name, 
                    groups.name as group_name')
            ->join('machine_revisions', 'machine_revisions.id = product_master.machine', 'inner')
            ->join('machines', 'machines.id = machine_revisions.machine', 'inner')
            // ->join('machine_module_master', 'machine_module_master.machine_rev = machine_revisions.id', 'left')
            ->join('modules', 'modules.id = product_master.machine_module', 'left')
            ->join('users as responsible', 'responsible.id = modules.responsible', 'left')
            ->join('seg_2', 'seg_2.id = product_master.seg2', 'left')
            ->join('seg_3', 'seg_3.id = product_master.seg3', 'left')
            ->join('segments', 'segments.id = product_master.segment', 'left')
            ->join('finish', 'finish.id = product_master.finish', 'left')
            ->join('groups', 'groups.id = product_master.prod_group', 'left');
            // ->get()
            // ->getResultArray();

        // Safely get the values first and then trim
        $order_number = isset($postData['order_number']) ? trim($postData['order_number']) : '';
        $material_number = isset($postData['material_number']) ? trim($postData['material_number']) : '';
        $machine = isset($postData['machine']) ? trim($postData['machine']) : '';
        $machine_module = isset($postData['machine_module']) ? trim($postData['machine_module']) : '';
        $segment = isset($postData['segment']) ? trim($postData['segment']) : '';

        $cheese_wt = isset($postData['cheese_wt']) ? trim($postData['cheese_wt']) : '';
        $finish = isset($postData['finish']) ? trim($postData['finish']) : '';
        $finish_wt = isset($postData['finish_wt']) ? trim($postData['finish_wt']) : '';
        $size = isset($postData['size']) ? trim($postData['size']) : '';
        $length = isset($postData['length']) ? trim($postData['length']) : '';

        $spec = isset($postData['spec']) ? trim($postData['spec']) : '';
        $rod_dia1 = isset($postData['rod_dia1']) ? trim($postData['rod_dia1']) : '';
        $drawn_dia1 = isset($postData['drawn_dia1']) ? trim($postData['drawn_dia1']) : '';
        $prod_group = isset($postData['prod_group']) ? trim($postData['prod_group']) : '';
        $seg2 = isset($postData['seg2']) ? trim($postData['seg2']) : '';

        $seg3 = isset($postData['seg3']) ? trim($postData['seg3']) : '';
        $special_remarks = isset($postData['special_remarks']) ? trim($postData['special_remarks']) : '';
        $bom = isset($postData['bom']) ? trim($postData['bom']) : '';
        $rm_component = isset($postData['rm_component']) ? trim($postData['rm_component']) : '';

        

        // // Apply conditions if not empty
        if (!empty($order_number)) {
            $builder->like('product_master.order_number', $order_number);
        }

        if (!empty($material_number)) {
            $builder->like('product_master.material_number', $material_number);
        }

        if (!empty($machine)) {
            $builder->where('machine_revisions.id', $machine);
        }

        if (!empty($machine_module)) {
            $builder->where('modules.id', $machine_module);
        }

        if (!empty($segment)) {
            $builder->where('segments.id', $segment);
        }

        if (!empty($cheese_wt)) {
            $builder->like('product_master.cheese_wt', $cheese_wt);
        }

        if (!empty($finish)) {
            $builder->where('finish.id', $finish);
        }

        if (!empty($finish_wt)) {
            $builder->like('product_master.finish_wt', $finish_wt);
        }

        if (!empty($size)) {
            $builder->like('product_master.size', $size);
        }

        if (!empty($length)) {
            $builder->like('product_master.length', $length);
        }

        if (!empty($spec)) {
            $builder->like('product_master.spec', $spec);
        }

        if (!empty($rod_dia1)) {
            $builder->like('product_master.rod_dia1', $rod_dia1);
        }

        if (!empty($drawn_dia1)) {
            $builder->like('product_master.drawn_dia1', $drawn_dia1);
        }

        if (!empty($prod_group)) {
            $builder->where('groups.id', $prod_group);
        }

        if (!empty($seg2)) {
            $builder->where('seg_2.id', $seg2);
        }

        if (!empty($seg3)) {
            $builder->where('seg_3.id', $seg3);
        }

        if (!empty($special_remarks)) {
            $builder->like('product_master.special_remarks', $special_remarks);
        }

        if (!empty($bom)) {
            $builder->like('product_master.bom', $bom);
        }

        if (!empty($rm_component)) {
            $builder->like('product_master.rm_component', $rm_component);
        }

        // Clone for count query
        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        // Get paginated result
        $data = $builder->findAll($perPage, $offset);

        return $this->respond([
            'status'  => true,
            'message' => 'Products found',
            'data'    => $data,
            'filterData' => $postData,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => ceil($total / $perPage)
            ]
        ], 200); // HTTP 200 OK
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

    public function material_no_info($mat_num){
        $productMaster = $this->productMasterModel->select('product_master.*, 
                    machines.name as machine_name, 
                    responsible.name as responsible_name, 
                    product_master.special_remarks, product_master.bom, product_master.rm_component,
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
            ->where('product_master.material_number', $mat_num)
            ->get()
            ->getResultArray();


        if ($productMaster) {
            return $this->respond([
                'status'  => true,
                'message' => 'Products found',
                'data'    => $productMaster[0]
            ], 200); // HTTP 200 OK
        } else {
            return $this->respond([
                'status'  => false,
                'message' => 'No products found'
            ], 404); // HTTP 404 Not Found
        }
    }

    public function getProductMasterFileUploadStatus(){
        $records = $this->pm_file_import_log_model->select('*')->orderBy('id', 'DESC')->findAll();
        $failedRecords = $this->pm_temp_import_products_model
                    ->where('error_json !=', '0')
                    ->findAll();
        $total_failed_records = count($failedRecords);
        $data = null;
        if (count($records) > 0) {
            $data = array_merge($records[0], array('total_failed_records'=> $total_failed_records));
        }

        return $this->respond([
            'data' => $data
        ], 200); // HTTP 200 OK
    }
    

    public function downloadPMTemplate(array $data = [], $fileId=null)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ProductMaster');

        $listSheet = $spreadsheet->createSheet();
        $listSheet->setTitle('Dropdowns');

        // Add headers
        $sheet->setCellValue('A1', 'Order');
        $sheet->setCellValue('B1', 'Material Number');
        $sheet->setCellValue('C1', 'Material Number Froging');
        $sheet->setCellValue('D1', 'Material Description');
        $sheet->setCellValue('E1', 'Machine Name');
        $sheet->setCellValue('F1', 'Module');
        $sheet->setCellValue('G1', 'Unit of Measure');
        $sheet->setCellValue('H1', 'Seg-2');
        $sheet->setCellValue('I1', 'Seg-3');
        $sheet->setCellValue('J1', 'Product Size');
        $sheet->setCellValue('K1', 'Product Group');
        $sheet->setCellValue('L1', 'Product Length');
        $sheet->setCellValue('M1', 'Finish');
        $sheet->setCellValue('N1', 'Segment');
        $sheet->setCellValue('O1', 'Finish Wt');
        $sheet->setCellValue('P1', 'Cheese Wt');
        $sheet->setCellValue('Q1', 'RM SPEC');
        $sheet->setCellValue('R1', 'ROD DIA1');
        $sheet->setCellValue('S1', 'DRAWN DIA1');
        $sheet->setCellValue('T1', 'Special Remarks');
        $sheet->setCellValue('U1', 'BOM');
        $sheet->setCellValue('V1', 'RM Component');
        $sheet->setCellValue('W1', 'Condition of Raw material');

        // Set minimum column widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(20);
        $sheet->getColumnDimension('L')->setWidth(20);
        $sheet->getColumnDimension('M')->setWidth(20);
        $sheet->getColumnDimension('N')->setWidth(20);
        $sheet->getColumnDimension('O')->setWidth(20);
        $sheet->getColumnDimension('P')->setWidth(20);
        $sheet->getColumnDimension('Q')->setWidth(25);
        $sheet->getColumnDimension('R')->setWidth(25);
        $sheet->getColumnDimension('S')->setWidth(30);
        $sheet->getColumnDimension('T')->setWidth(40);
        $sheet->getColumnDimension('U')->setWidth(30);
        $sheet->getColumnDimension('V')->setWidth(30);
        $sheet->getColumnDimension('W')->setWidth(30);
        if (!empty($data)) {
            $sheet->setCellValue('X1', 'Error Information');
            $sheet->getColumnDimension('X')->setWidth(200); // End Date
        }
        // Freeze the first row
        $sheet->freezePane('A2');


        // Lock all cells by default (optional)
        $spreadsheet->getDefaultStyle()->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

        // Lock header row (A1 to E1)
        $sheet->getStyle('A1:W1')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Protect the sheet with optional password
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('your-secret-password');

        // Dropdown options
        $machines = $this->machineRevisionModel->select('name')->orderBy('name', 'asc')->findAll();
        $machineArr = array_column($machines, 'name');
        $machines_row = 1;
        foreach (array_merge(['Machines'], $machineArr) as $item) {
            $listSheet->setCellValue("A{$machines_row}", $item);
            $machines_row++;
        }

        $modules = $this->modulesModel->select('name')->orderBy('name', 'asc')->findAll();
        $moduleArr = array_column($modules, 'name');
        $modules_row = 1;
        foreach (array_merge(['Modules'], $moduleArr) as $item) {
            $listSheet->setCellValue("B{$modules_row}", $item);
            $modules_row++;
        }

        $seg2 = $this->seg2Model->select('name')->orderBy('name', 'asc')->findAll();
        $seg2Arr = array_column($seg2, 'name');
        $seg2_row = 1;
        foreach (array_merge(['Seg2'], $seg2Arr) as $item) {
            $listSheet->setCellValue("C{$seg2_row}", $item);
            $seg2_row++;
        }

        $seg3 = $this->seg3Model->select('name')->orderBy('name', 'asc')->findAll();
        $seg3Arr = array_column($seg3, 'name');
        $seg3_row = 1;
        foreach (array_merge(['Seg3'], $seg3Arr) as $item) {
            $listSheet->setCellValue("D{$seg3_row}", $item);
            $seg3_row++;
        }

        $group = $this->groupsModel->select('name')->orderBy('name', 'asc')->findAll();
        $groupArr = array_column($group, 'name');
        $group_row = 1;
        foreach (array_merge(['Groups'], $groupArr) as $item) {
            $listSheet->setCellValue("E{$group_row}", $item);
            $group_row++;
        }

        $finish = $this->finishModel->select('name')->orderBy('name', 'asc')->findAll();
        $finishArr = array_column($finish, 'name');
        $finish_row = 1;
        foreach (array_merge(['Finish'], $finishArr) as $item) {
            $listSheet->setCellValue("F{$finish_row}", $item);
            $finish_row++;
        }

        // Dropdown options
        $segments = $this->segmentsModel->select('name')->orderBy('name', 'asc')->findAll();
        $segmentArr = array_column($segments, 'name');
        $segments_row = 1;
        foreach (array_merge(['Segments'], $segmentArr) as $item) {
            $listSheet->setCellValue("G{$segments_row}", $item);
            $segments_row++;
        }

        // Lock header row (A1 to E1)
        $listSheet->getStyle('A1:G1000')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // Protect the sheet with optional password
        $listSheet->getProtection()->setSheet(true);
        $listSheet->getProtection()->setPassword('your-secret-password');
        // print_r(
        //     array(
        //         '$machineArr' => $machineArr,
        //         '$moduleArr' => $moduleArr,
        //         '$seg2Arr' => $seg2Arr,
        //         '$seg3Arr' => $seg3Arr,
        //         '$groupArr' => $groupArr,
        //         '$finishArr' => $finishArr,
        //         '$segmentArr' => $segmentArr,
        //     )
        // );
        // die();

        // Add validation for rows 2 to 100
        for ($rowIndex = 2; $rowIndex <= 1000; $rowIndex++) {
            $sheet->getCell("E{$rowIndex}")->getDataValidation()
                ->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(true)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setFormula1("=Dropdowns!A2:A" . ($machines_row));

            $sheet->getCell("F{$rowIndex}")->getDataValidation()
                ->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(true)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setFormula1("=Dropdowns!B2:B" . ($modules_row));

            $sheet->getCell("H{$rowIndex}")->getDataValidation()
                ->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(true)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setFormula1("=Dropdowns!C2:C" . ($seg2_row));

            $sheet->getCell("I{$rowIndex}")->getDataValidation()
                ->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(true)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setFormula1("=Dropdowns!D2:D" . ($seg3_row));

            $sheet->getCell("K{$rowIndex}")->getDataValidation()
                ->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(true)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setFormula1("=Dropdowns!E2:E" . ($group_row));

            $sheet->getCell("M{$rowIndex}")->getDataValidation()
                ->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(true)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setFormula1("=Dropdowns!F2:F" . ($finish_row));

            $sheet->getCell("N{$rowIndex}")->getDataValidation()
                ->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(true)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setFormula1("=Dropdowns!G2:G" . ($segments_row));
        }

        $filename = 'ProductMasterTemplate.xlsx';


        if (!empty($data)) {
            $rowNumber = 2;   // first data row

            foreach ($data as $record) {

                // Helper closure: NULL -> ''
                $v = static fn($key) => empty($record[$key]) ? '' : $record[$key];

                // Convert Y‑m‑d -> Excel serial number (so the date formatting you
                // already set on columns G,H,I shows dd‑mm‑yyyy in Excel)
                $ymdToExcel = static function (?string $ymd): string|int {
                    if (!$ymd) return '';
                    $ts = strtotime($ymd);
                    return $ts ? ExcelDate::PHPToExcel($ts) : '';
                };

                $sheet->setCellValue("A{$rowNumber}", $v('order_no'));
                $sheet->setCellValue("B{$rowNumber}", $v('material_number'));
                $sheet->setCellValue("C{$rowNumber}", $v('material_number_froging'));
                $sheet->setCellValue("D{$rowNumber}", $v('material_description'));
                $sheet->setCellValue("E{$rowNumber}", $v('machine_name'));
                $sheet->setCellValue("F{$rowNumber}", $v('module'));
                $sheet->setCellValue("G{$rowNumber}", $v('uom'));
                $sheet->setCellValue("H{$rowNumber}", $v('seg2'));
                $sheet->setCellValue("I{$rowNumber}", $v('seg3'));
                $sheet->setCellValue("J{$rowNumber}", $v('product_size'));
                $sheet->setCellValue("K{$rowNumber}", $v('product_group'));
                $sheet->setCellValue("L{$rowNumber}", $v('product_length'));
                $sheet->setCellValue("M{$rowNumber}", $v('finish'));
                $sheet->setCellValue("N{$rowNumber}", $v('segment'));
                $sheet->setCellValue("O{$rowNumber}", $v('finish_wt'));
                $sheet->setCellValue("P{$rowNumber}", $v('cheese_wt'));
                $sheet->setCellValue("Q{$rowNumber}", $v('rm_spec'));
                $sheet->setCellValue("R{$rowNumber}", $v('rod_dia1'));
                $sheet->setCellValue("S{$rowNumber}", $v('drawn_dia1'));
                $sheet->setCellValue("T{$rowNumber}", $v('special_remarks'));
                $sheet->setCellValue("U{$rowNumber}", $v('bom'));
                $sheet->setCellValue("V{$rowNumber}", $v('rm_component'));
                $sheet->setCellValue("W{$rowNumber}", $v('condition_raw_material'));
                $sheet->setCellValue("X{$rowNumber}", $v('error_json'));

                $rowNumber++;
            }

            // If you wrote fewer than the 100 rows you pre‑validated, extend
            // the validation loop so any extra rows keep their dropdowns
            // $lastRow = max($rowNumber - 1, 1000);
            // for ($row = $rowNumber; $row <= $lastRow; $row++) {
            //     /* repeat the validation logic you already have
            //     OR just leave them blank if validation isn’t needed */
            // }
            $filename = 'FailedProductMasterRecords.xlsx';
            $this->pm_temp_import_products_model
                    ->where('file_id', $fileId)
                    ->where('error_json !=', '0')
                    ->delete();
        }


        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        $writer->save("php://output");
    }


    public function downloadPMFailedRecords(){
        $records = $this->pm_file_import_log_model->select('*')->orderBy('id', 'DESC')->findAll();
        $fileId = null;
        if (count($records) > 0) {
            $fileId = $records[0]['id'];
        }

        if($fileId === null){
            return $this->respond([
                'data' => '',
                'status' => 'failed',
                'message' => 'No record found'
            ], 200);
        } else {
            
            $rows = $this->pm_temp_import_products_model
                    ->where('file_id', $fileId)
                    ->where('error_json !=', '0')
                    ->findAll();
    
            if (!$rows) {
                 return $this->respond([
                    'data' => '',
                    'status' => 'failed',
                    'message' => 'No record found'
                ], 200); 
            } else {
                // echo $fileId; die();
                $this->downloadPMTemplate($rows, $fileId);
                // return $this->respond([
                //     'data' => $rows,
                //     'status' => 'success',
                //     'message' => 'OK'
                // ], 200); 
            }
        }

        // $csv = fopen('php://temp', 'w+');
        // // header
        // fputcsv($csv, array_keys($rows[0]));
        // foreach ($rows as $r) {
        //     fputcsv($csv, $r);
        // }
        // rewind($csv);
        // return $this->response
        //     ->setHeader('Content-Type', 'text/csv')
        //     ->setHeader('Content-Disposition', 'attachment; filename="failed_rows_'.$fileId.'.csv"')
        //     ->setBody(stream_get_contents($csv));
    }


    public function triggerPMFileValidation()
    {

        $records = $this->pm_file_import_log_model->select('*')
            ->where('status !=', 'completed')
            ->where('status !=', 'failed')
            ->orderBy('id', 'DESC')->findAll();
        $fileId = null;
        if (count($records) > 0) {
            $fileId = $records[0]['id'];
        }

        if($fileId === null){
            return $this->respond([
                'data' => '',
                'status' => 'failed',
                'message' => 'No record found'
            ], 200);
        } else {
            // Kick off spark command in background
            $command = 'php ' . ROOTPATH . 'spark validate:productmastersfiledata ' . escapeshellarg($fileId);
            $logfile = WRITEPATH . 'logs/cli_job_' . date('Ymd_His') . '.log';
            // echo $command;
            // Run the command in background and log output
            exec("$command > $logfile 2>&1 &");

            // Launch in background, OS‑aware
            // if (stripos(PHP_OS, 'WIN') === 0) {
            //     // Windows
            //     $bg = 'start /B "" ' . $command . ' > ' . escapeshellarg($logfile) . ' 2>&1';
            //     pclose(popen('cmd /C ' . $bg, 'r'));
            // } else {
            //     // Linux/Mac
            //     exec($command . ' > ' . escapeshellarg($logfile) . ' 2>&1 &');
            // }
    
            return $this->respond([
                'status' => 'success',
                'message' => 'Validation job started for file ID ' . $fileId,
                'command' => $command,
            ], 200);
        }

    }
}
