<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\WorkOrderMasterModel;
use App\Models\PlantModel;
use App\Models\CustomersModel;
use App\Models\SegmentsModel;
use App\Models\WOMFileImportLogModel;
use App\Models\WOMTempImportWorkOrderModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Protection;

use \DateTime;

class WorkOrderMasterController extends ResourceController
{

    protected $workOrderMasterModel;
    protected $plantModel;
    protected $customersModel;
    protected $segmentsModel;
    protected $wom_file_import_log_model;
    protected $wom_temp_import_work_order_model;

    public function __construct()
    {
        // Load models in the constructor
        $this->workOrderMasterModel = new WorkOrderMasterModel();
        $this->plantModel = new PlantModel();
        $this->customersModel = new CustomersModel();
        $this->segmentsModel = new SegmentsModel();
        $this->wom_file_import_log_model = new WOMFileImportLogModel();
        $this->wom_temp_import_work_order_model = new WOMTempImportWorkOrderModel();
    }

    public function getAllData(){
        $request = $this->request;
        $postData = $request->getJSON(true); // Get POST data as array

        // Get page from query param (e.g., ?page=2), default to 1
        $page = (int) $this->request->getGet('page');
        $page = max($page, 1); // Ensure at least 1
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        // $cacheKey = 'get_wo_master_data'; // Unique cache key

        // Attempt to get the data from cache
        // $cache = \Config\Services::cache();

        // Check if data is available in cache
        // if ($data = $cache->get($cacheKey)) {
            // $cache->delete($cacheKey);
            // return $this->respond(['status' => 'success', 'message' => 'Cache cleared successfully.']);
            // return $this->respond($data);  // Return cached data
        // }

        // $data = $this->workOrderMasterModel->select('work_order_master.id, plant, work_order_master.responsible_person_name as responsible_person_name1 , work_order_master.marketing_person_name as marketing_person_name1, work_order_db, customer, quality_inspection_required, responsible.name as responsible_person_name, responsible_person, marketing.name as marketing_person_name, marketing_person, segments.name as segment_name, segment, reciving_date, delivery_date, work_order_master.created_at')
        //     ->join('segments', 'segments.id = work_order_master.segment', 'left')
        //     ->join('users as responsible', 'responsible.id = work_order_master.responsible_person', 'left')
        //     ->join('users as marketing', 'marketing.id = work_order_master.marketing_person', 'left')
        //     ->orderBy('work_order_master.created_at', 'DESC')->findAll();

        // Fetch paginated records
        $builder = $this->workOrderMasterModel
            ->select('
                work_order_master.id,
                plant,
                work_order_master.responsible_person_name as responsible_person_name1,
                work_order_master.marketing_person_name as marketing_person_name1,
                work_order_master.no_of_items,
                work_order_master.weight,
                work_order_db,
                customer,
                quality_inspection_required,
                responsible.name as responsible_person_name,
                responsible_person,
                marketing.name as marketing_person_name,
                marketing_person,
                segments.name as segment_name,
                segment,
                reciving_date,
                delivery_date,
                work_order_master.wo_add_date
            ')
            ->join('segments', 'segments.id = work_order_master.segment', 'left')
            ->join('users as responsible', 'responsible.id = work_order_master.responsible_person', 'left')
            ->join('users as marketing', 'marketing.id = work_order_master.marketing_person', 'left');
            // ->orderBy('work_order_master.created_at', 'DESC')
            // ->findAll($perPage, $offset); // Apply limit and offset

        // Dynamically apply filters if fields are not empty
        if (!empty(trim($postData['work_order_db'] ?? ''))) {
            $builder->where('work_order_db', trim($postData['work_order_db']));
        }

        if (!empty(trim($postData['customer'] ?? ''))) {
            $builder->like('customer', trim($postData['customer']));
        }

        if (!empty(trim($postData['responsible_person_name'] ?? ''))) {
            $builder->like('work_order_master.responsible_person_name', trim($postData['responsible_person_name']));
        }

        if (!empty(trim($postData['segment_id'] ?? ''))) {
            $builder->where('segment', trim($postData['segment_id']));
        }

        if (!empty(trim($postData['marketing_person_name'] ?? ''))) {
            $builder->like('work_order_master.marketing_person_name', trim($postData['marketing_person_name']));
        }

        if (!empty(trim($postData['date_type'] ?? ''))) {
            if (!empty($postData['range_dates'])) {
                if($postData['range_dates'][1] != null){
                    $builder->where('work_order_master.'.$postData['date_type'].'>=', DateTime::createFromFormat('d-m-Y', $postData['range_dates'][0])->format('Y-m-d'));
                    $builder->where('work_order_master.'.$postData['date_type'].'<=', DateTime::createFromFormat('d-m-Y', $postData['range_dates'][1])->format('Y-m-d'));
                } else {
                    $builder->where('work_order_master.'.$postData['date_type'].'>=', DateTime::createFromFormat('d-m-Y', $postData['range_dates'][0])->format('Y-m-d'));
                }
            }
        }


        // Safely get the values first and then trim
        // $workOrderDb = isset($postData['work_order_db']) ? trim($postData['work_order_db']) : '';
        // $customer = isset($postData['customer']) ? trim($postData['customer']) : '';
        // $responsiblePersonName = isset($postData['responsible_person_name']) ? trim($postData['responsible_person_name']) : '';
        // $segmentId = isset($postData['segment_id']) ? trim($postData['segment_id']) : '';
        // $marketingPersonName = isset($postData['marketing_person_name']) ? trim($postData['marketing_person_name']) : '';

        // // Apply conditions if not empty
        // if (!empty($workOrderDb)) {
        //     $builder->where('work_order_db', $workOrderDb);
        // }

        // if (!empty($customer)) {
        //     $builder->where('customer', $customer);
        // }

        // if (!empty($responsiblePersonName)) {
        //     $builder->like('responsible.name', $responsiblePersonName);
        // }

        // if (!empty($segmentId)) {
        //     $builder->where('segment', $segmentId);
        // }

        // if (!empty($marketingPersonName)) {
        //     $builder->like('marketing.name', $marketingPersonName);
        // }

        $builder->orderBy('work_order_master.delivery_date', 'DESC');
        // Clone for count query
        $countBuilder = clone $builder;
        // echo '<pre>';
        // print_r($countBuilder->countAllResults(true));  // 👈 This shows the COUNT query
        // echo '</pre>';
        // exit();
        $total = $countBuilder->countAllResults(false);

        // Get paginated result
        $data = $builder->findAll($perPage, $offset);

        // Get total number of records for pagination meta
        return $this->respond([
            'data'       => $data,
            'filterData' => $postData,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => ceil($total / $perPage)
            ]
        ], 200);
    }

    // Method to clear cache manually
    public function clearCache()
    {
        // $cacheKey = 'api_data';  // The cache key you want to clear

        // Get the cache service
        // $cache = \Config\Services::cache();

        // Check if cache exists and then delete it
        // if ($cache->get($cacheKey)) {
        //     $cache->delete($cacheKey);  // Delete the cached data
        //     return $this->respond(['status' => 'success', 'message' => 'Cache cleared successfully.']);
        // }

        return $this->respond(['status' => 'error', 'message' => 'Cache not found.']);
    }


    public function addWorkOrderMaster(){
        $plant = $this->request->getVar('plant');
        $customer = $this->request->getVar('customer');
        $qir = $this->request->getVar('quality_inspection_required');
        $responsible_person_name = $this->request->getVar('responsible_person_name');
        $marketing_person_name = $this->request->getVar('marketing_person_name');
        $segment = $this->request->getVar('segment');
        $reciving_date = $this->request->getVar('reciving_date');
        $delivery_date = $this->request->getVar('delivery_date');
        $wo_add_date = $this->request->getVar('wo_add_date');
        $no_of_items = $this->request->getVar('no_of_items');
        $weight = $this->request->getVar('weight');
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
            'responsible_person_name'           => $responsible_person_name,
            'marketing_person_name'           => $marketing_person_name,
            'segment'           => $segment,
            'reciving_date'           => $reciving_date,
            'delivery_date'           => $delivery_date,
            'wo_add_date'         => $wo_add_date,
            'no_of_items'         => $no_of_items,
            'weight'         => $weight,
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


    public function getWorkOrderFileUploadStatus(){
        $records = $this->wom_file_import_log_model->select('*')->orderBy('id', 'DESC')->findAll();
        $failedRecords = $this->wom_temp_import_work_order_model
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

    public function downloadWOMTemplate(array $data = [], $fileId=null)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('WorkOrderMaster');

        // Add headers
        $sheet->setCellValue('A1', 'Plant');
        $sheet->setCellValue('B1', 'Work Order');
        $sheet->setCellValue('C1', 'Customer');
        $sheet->setCellValue('D1', 'Responsible Person Name');
        $sheet->setCellValue('E1', 'Segment Name');
        $sheet->setCellValue('F1', 'Marketing Person Name');
        $sheet->setCellValue('G1', 'WO Adding Date (dd-mm-yyyy)');
        $sheet->setCellValue('H1', 'Receiving Date (dd-mm-yyyy)');
        $sheet->setCellValue('I1', 'Delivery Date (dd-mm-yyyy)');
        $sheet->setCellValue('J1', 'No. of items');
        $sheet->setCellValue('K1', 'Weight');

        // Set minimum column widths
        $sheet->getColumnDimension('A')->setWidth(10); // Name
        $sheet->getColumnDimension('B')->setWidth(20); // Name
        $sheet->getColumnDimension('C')->setWidth(30); // Department
        $sheet->getColumnDimension('D')->setWidth(30); // Status
        $sheet->getColumnDimension('E')->setWidth(20); // Start Date
        $sheet->getColumnDimension('F')->setWidth(30); // End Date
        $sheet->getColumnDimension('G')->setWidth(30); // End Date
        $sheet->getColumnDimension('H')->setWidth(30); // End Date
        $sheet->getColumnDimension('I')->setWidth(30); // End Date
        $sheet->getColumnDimension('J')->setWidth(20); // End Date
        $sheet->getColumnDimension('K')->setWidth(20); // End Date
        if (!empty($data)) {
            $sheet->setCellValue('L1', 'Error Information');
            $sheet->getColumnDimension('L')->setWidth(200); // End Date
        }
        // Freeze the first row
        $sheet->freezePane('A2');


        // Lock all cells by default (optional)
        $spreadsheet->getDefaultStyle()->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

        // Lock header row (A1 to E1)
        $sheet->getStyle('A1:K1')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // 1. Unlock all cells
        // $spreadsheet->getActiveSheet()
        //     ->getStyle('A1:Z1000') // or your desired range
        //     ->getProtection()
        //     ->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);

        // // 2. Lock only the headers
        // $sheet->getStyle('A1:K1')
        //     ->getProtection()
        //     ->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_PROTECTED);

        // 3. Now protect the sheet
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('your-secret-password');

        // Dropdown options
        $segments = $this->segmentsModel->select('name')->orderBy('name', 'asc')->findAll();
        $segmentsArr = array_column($segments, 'name');

        $plants = ['MP01'];

        // Add validation for rows 2 to 100
        for ($row = 2; $row <= 1000; $row++) {

            $validationStatus = $sheet->getCell("A{$row}")->getDataValidation();
            $validationStatus->setType(DataValidation::TYPE_LIST);
            $validationStatus->setErrorStyle(DataValidation::STYLE_STOP);
            $validationStatus->setAllowBlank(true);
            $validationStatus->setShowInputMessage(true);
            $validationStatus->setShowErrorMessage(true);
            $validationStatus->setShowDropDown(true);
            $validationStatus->setFormula1('"' . implode(',', $plants) . '"');

            
            $validationDept = $sheet->getCell("E{$row}")->getDataValidation();
            $validationDept->setType(DataValidation::TYPE_LIST);
            $validationDept->setErrorStyle(DataValidation::STYLE_STOP);
            $validationDept->setAllowBlank(true);
            $validationDept->setShowInputMessage(true);
            $validationDept->setShowErrorMessage(true);
            $validationDept->setShowDropDown(true);
            $validationDept->setFormula1('"' . implode(',', $segmentsArr) . '"');

            // Format D and E as date (dd-mm-yyyy)
            $sheet->getStyle("G{$row}")
                ->getNumberFormat()
                ->setFormatCode('dd-mm-yyyy');
            $sheet->getStyle("H{$row}")
                ->getNumberFormat()
                ->setFormatCode('dd-mm-yyyy');
            $sheet->getStyle("I{$row}")
                ->getNumberFormat()
                ->setFormatCode('dd-mm-yyyy');

        
        }
        
        $filename = 'WorkOrderMasterTemplate.xlsx';

        /* -----------------------------------------------------------------
        |  Write data rows if provided
        |------------------------------------------------------------------
        |  Mapping:
        |  A -> plant
        |  B -> work_order_db
        |  C -> customer
        |  D -> responsible_person_name
        |  E -> segment_name
        |  F -> marketing_person_name
        |  G -> wo_add_date        (dd‑mm‑yyyy)
        |  H -> reciving_date      (dd‑mm‑yyyy)
        |  I -> delivery_date      (dd‑mm‑yyyy)
        |  J -> no_of_items        (int)
        |  K -> weight             (decimal)
        *-----------------------------------------------------------------*/
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

                $sheet->setCellValue("A{$rowNumber}", $v('plant'));
                $sheet->setCellValue("B{$rowNumber}", $v('work_order_db'));
                $sheet->setCellValue("C{$rowNumber}", $v('customer'));
                $sheet->setCellValue("D{$rowNumber}", $v('responsible_person_name'));
                $sheet->setCellValue("E{$rowNumber}", $v('segment_name'));
                $sheet->setCellValue("F{$rowNumber}", $v('marketing_person_name'));
                $sheet->setCellValue("G{$rowNumber}", $ymdToExcel($record['wo_add_date'] ?? null));
                $sheet->setCellValue("H{$rowNumber}", $ymdToExcel($record['reciving_date'] ?? null));
                $sheet->setCellValue("I{$rowNumber}", $ymdToExcel($record['delivery_date'] ?? null));
                $sheet->setCellValue("J{$rowNumber}", $v('no_of_items'));
                $sheet->setCellValue("K{$rowNumber}", $v('weight'));
                $sheet->setCellValue("L{$rowNumber}", $v('error_json'));

                $rowNumber++;
            }

            // If you wrote fewer than the 100 rows you pre‑validated, extend
            // the validation loop so any extra rows keep their dropdowns
            // $lastRow = max($rowNumber - 1, 1000);
            // for ($row = $rowNumber; $row <= $lastRow; $row++) {
            //     /* repeat the validation logic you already have
            //     OR just leave them blank if validation isn’t needed */
            // }
            $filename = 'FailedWorkOrderMasterRecords.xlsx';
            $this->wom_temp_import_work_order_model
                    ->where('file_id', $fileId)
                    ->where('error_json !=', '0')
                    ->delete();
        }

        // Output
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        $writer->save("php://output");
    }


    public function downloadWOMFailedRecords(){
        $records = $this->wom_file_import_log_model->select('*')->orderBy('id', 'DESC')->findAll();
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
            
            $rows = $this->wom_temp_import_work_order_model
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
                
                $this->downloadWOMTemplate($rows, $fileId);
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


    public function triggerWOMFileValidation()
    {

        $records = $this->wom_file_import_log_model->select('*')
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
            // $php      = PHP_BINARY;  
            // $cmdParts = [
            //     'cd', escapeshellarg(ROOTPATH), '&&',
            //     escapeshellarg($php),
            //     'spark',
            //     'validate:workordermastersfiledata',
            //     escapeshellarg($fileId)
            // ];
            // $command  = implode(' ', $cmdParts);
            // Kick off spark command in background
            $command = 'php ' . ROOTPATH . 'spark validate:workordermastersfiledata ' . escapeshellarg($fileId);
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
