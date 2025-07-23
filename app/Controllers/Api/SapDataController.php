<?php
namespace App\Controllers\Api;

use App\Models\SapDataModel;
use App\Models\SapDataHistoryModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use App\Models\CustomUserModel;
use App\Models\WeeklyPeriodsModel;
use CodeIgniter\Controller\AuthController;
use CodeIgniter\Files\File;
use Config\Database;
use Exception;
use DateTime;
use App\Models\SAPFileImportLogModel;
use App\Models\SapTempImportDataModel;
use App\Models\SapCalculatedSummaryModel;
use App\Models\MasterTemplatesPasswordModel;
use App\Models\SurfaceTreatmentProcessModel;
use App\Models\WeeklyPlanningModel;
use App\Models\WeeklyPlanningDataModel;
use App\Models\WorkOrderMasterModel;
use App\Models\ProductMasterModel;
use App\Models\FinishModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Protection;


class SapDataController extends ResourceController
{
    protected $sapDataModel;
    protected $userModel;
    protected $WeeklyPeriodsModel;
    protected $masterTemplatesPasswordModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->sapDataModel = new SapDataModel();
        $this->userModel = new CustomUserModel();
        $this->weeklyPeriodsModel = new WeeklyPeriodsModel();
        $this->masterTemplatesPasswordModel = new MasterTemplatesPasswordModel();
        $this->current_date = date('Y-m-d');
    }
     


public function index()
{
    $excelUploadFile = $this->request->getFile("upload_excel");
    if (!$excelUploadFile->isValid()) {
        return $this->failValidationErrors($excelUploadFile->getErrorString());
    }

    $uploadExcelURL = "";
    if ($excelUploadFile && $excelUploadFile->isValid()) {
        // 1. Persist file
        $newExcelUploadName = $excelUploadFile->getRandomName();
        $path = WRITEPATH . 'uploads/';
        $excelUploadFile->move(WRITEPATH . "uploads", $newExcelUploadName);
        // $excelUploadFile->move(FCPATH . "uploads", $newExcelUploadName);
        // $uploadExcelURL = FCPATH . "uploads/" . $newExcelUploadName;
        $uploadExcelURL = WRITEPATH . "uploads/" . $newExcelUploadName;

        // 2. Add log row
        $logModel  = new SAPFileImportLogModel();
        $fileId = $logModel->insert([
            'original_name' => $excelUploadFile->getClientName(),
            'stored_name'   => $newExcelUploadName,
            'uploaded_by'   => user_id() ?? null, // if you use auth
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
        ], true);
        $this->clearSapData(); 
        $this->dumpToTemp($path . $newExcelUploadName, $fileId);

         // 4. Trigger CLI job asynchronously (Linux)
        // exec("php " . ROOTPATH . "spark validate:sapfiledata {$fileId} > /dev/null 2>&1 &");

        return $this->respondCreated(['fileId' => $fileId, 'message' => 'File queued for validation']);
    } else {
        return $this->fail("Failed to upload the file.", 400);
    }

    // try {
    //     // Load the uploaded Excel file
    //       $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadExcelURL);
    //       $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

    //     // // Remove the header row
    //      unset($sheetData[1]);

    //     // Prepare data for insertion
    //     $insertData = $this->prepareInsertData($sheetData);
    //     // print_r($insertData); die;
    //     // Perform database operations
    //     $db = \Config\Database::connect();
    //     // $db->transStart();

    //     $this->transferSapData(); // Transfer data to history
    //      $this->clearSapData();    // Clear sap_data table
    //     $this->insertSapData($insertData); // Insert new data into sap_data

    //     // $db->transComplete();

    //     return $this->respond([
    //         "status" => true,
    //         "message" => "Data processed and added successfully.",
    //         "data" => $insertData
    //     ], 201);
    // } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
    //     return $this->fail("Error processing the Excel file: " . $e->getMessage(), 500);
    // } catch (\Exception $e) {
    //     return $this->fail("An error occurred: " . $e->getMessage(), 500);
    // }
}


protected function dumpToTemp(string $filepath, int $fileId): void
{
    helper('date_excel');
    $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath)->getActiveSheet();
    $spreadsheet = IOFactory::load($filepath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = [];

    foreach ($sheet->toArray(null, true, true, false) as $idx => $r) {
        if ($idx === 0) continue;
        if (empty(array_filter($r))) continue;

        $r = array_pad($r, 13, '');

        [$order, $mat, $desc, $plant, $oqty, $dqty, $cqty, $uom, $batch, $status, $startDate, $finishDate, $salesOrder] = $r;
        // $orderQuantity     = is_numeric($oqty) ? (int)$oqty : 0;
        // $confirmedQuantity = is_numeric($cqty) ? (int)$cqty : 0;
        $rows[] = [
            'file_id'               => $fileId,
            'row_index'             => $idx + 1,
            'order_number'          => trim($order),
            'material'              => trim($mat),
            'material_description'  => trim($desc),
            'plant'                 => trim($plant),
            'order_quantity'        => $oqty ? (int)str_replace(',', '', $oqty) : 0,
            'delivered_quantity'    => $dqty ? (int)str_replace(',', '', $dqty) : 0,
            'confirmed_quantity'    => $cqty ? (int)str_replace(',', '', $cqty) : 0,
            'unit_of_measure'       => trim($uom),
            'batch'                 => trim($batch),
            'system_status'         => trim($status) ?: null,
            'start_date'            => $this->dmy_to_iso($startDate),
            'scheduled_finish_date' => $this->dmy_to_iso($finishDate),
            'sales_order'           => trim($salesOrder) ?: null,
            // 'to_forge_qty'          => $orderQuantity - $confirmedQuantity,
            'created_at'            => date('Y-m-d H:i:s'),
        ];

        if (count($rows) === 2000) {
            (new \App\Models\SapTempImportDataModel())->insertBatch($rows);
            $rows = [];
        }
    }

    if ($rows) {
        (new \App\Models\SapTempImportDataModel())->insertBatch($rows);
    }
}


/**
 * Prepare data for insertion from the Excel sheet
 */
private function prepareInsertData($sheetData)
{
    $insertData = [];
    // print_r($sheetData); die();
    foreach ($sheetData as $rawRow) {

        // 1️⃣  normalise keys: 'A ' → 'A'
        $row = [];
        foreach ($rawRow as $col => $val) {
            $row[trim($col)] = $val;
        }
        // print_r($row);
        $orderQuantity = $row['E'] ? str_replace(',', '', $row['E']) : 0;
        $confirmedQuantity = $row['G'] ? str_replace(',', '', $row['G']) : 0;
        $insertData[] = [
            'orderNumber'             => $row['A'] ?? null,
            'materialNumber'          => $row['B'] ?? null,
            'materialDescription'     => $row['C'] ?? null,
            'plant'                   => $row['D'] ?? null,
            'orderQuantity_GMEIN'     => $row['E'] ? str_replace(',', '', $row['E']) : 0,
            'deliveredQuantity_GMEIN' => $row['F'] ? str_replace(',', '', $row['F']) : 0,
            'confirmedQuantity_GMEIN' => $row['G'] ? str_replace(',', '', $row['G']) : 0,
            'unitOfMeasure_GMEIN'     => $row['H'] ?? null,
            'batch'                   => $row['I'] ?? null,
            'systemStatus'            => $row['J'] ?? null,
            'startDate'               => $this->dmy_to_iso($row['K']),
            'scheduledFinishDate'     => $this->dmy_to_iso($row['L']),
            'salesOrder'              => $row['M'] ?? null, 

            'to_forge_qty'            => (int)$orderQuantity - (int)$confirmedQuantity,
            'forge_commite_week'           => null,
            'this_month_forge_qty'           => null,
            'special_remarks'           => null,
            'monthly_plan'              => null,
            'monthly_fix_plan'           => null,
            'rm_allocation_priority'           => null,


            'insertedTimestamp'       => date('Y-m-d H:i:s'),
            'insertedBy'              => 1, // Replace with actual user if available
        ];
        // print_r($insertData); die();
    }
    return $insertData;
}

private function dmy_to_iso($value)
{
    // Empty cell ⇒ NULL
    if ($value === null || trim((string) $value) === '') {
        return null;
    }

    /* ---------- Excel numeric serial date ---------- */
    // if (is_numeric($value)) {
    //     try {
    //         return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
    //     } catch (\Throwable $e) {
    //         return null;                   // invalid serial number
    //     }
    // }

    /* ---------- Try a set of allowed string formats ---------- */
    $formats = ['!m/d/Y', '!d-m-Y', '!d/m/Y'];   // extend if necessary
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, trim($value));
        if ($dt) {
            $errors = DateTime::getLastErrors();
            if ($errors === false               // ← parse was perfect
                || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)
            ) {
                return $dt->format('Y-m-d');
            }
        }
    }

    /* ---------- Everything failed ---------- */
    return null;
}


    public function downloadSAPTemplate(array $data = [], $fileId=null)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sapTempImportDataModel  = new SapTempImportDataModel();
        $sheet->setTitle('Data');

        // Add headers
        $sheet->setCellValue('A1', 'Order');
        $sheet->setCellValue('B1', 'Material');
        $sheet->setCellValue('C1', 'Material description');
        $sheet->setCellValue('D1', 'Plant');
        $sheet->setCellValue('E1', 'Order quantity');
        $sheet->setCellValue('F1', 'Delivered quantity');
        $sheet->setCellValue('G1', 'Confirmed quantity');
        $sheet->setCellValue('H1', 'Unit of measure');
        $sheet->setCellValue('I1', 'Batch');
        $sheet->setCellValue('J1', 'System Status');
        $sheet->setCellValue('K1', 'Start date (sched)');
        $sheet->setCellValue('L1', 'Scheduled finish date');
        $sheet->setCellValue('M1', 'Sales Order');

        // Set minimum column widths
        $sheet->getColumnDimension('A')->setWidth(20); // Name
        $sheet->getColumnDimension('B')->setWidth(20); // Name
        $sheet->getColumnDimension('C')->setWidth(30); // Department
        $sheet->getColumnDimension('D')->setWidth(10); // Status
        $sheet->getColumnDimension('E')->setWidth(30); // Start Date
        $sheet->getColumnDimension('F')->setWidth(30); // End Date
        $sheet->getColumnDimension('G')->setWidth(30); // End Date
        $sheet->getColumnDimension('H')->setWidth(30); // End Date
        $sheet->getColumnDimension('I')->setWidth(30); // End Date
        $sheet->getColumnDimension('J')->setWidth(40); // End Date
        $sheet->getColumnDimension('K')->setWidth(30); // End Date
        $sheet->getColumnDimension('L')->setWidth(30); // End Date
        $sheet->getColumnDimension('M')->setWidth(30); // End Date
        if (!empty($data)) {
            $sheet->setCellValue('N1', 'Error Information');
            $sheet->getColumnDimension('N')->setWidth(200); // End Date
        }
        // Freeze the first row
        $sheet->freezePane('A2');


        // Lock all cells by default (optional)
        $spreadsheet->getDefaultStyle()->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

        // Lock header row (A1 to E1)
        $sheet->getStyle('A1:M1')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

        // 3. Now protect the sheet
        $sheet->getProtection()->setSheet(true);
        helper('string');

        $passwordForTemplate = generateRandomString(10);
        $sheet->getProtection()->setPassword($passwordForTemplate);

        // Add validation for rows 2 to 100
        for ($row = 2; $row <= 1000; $row++) {

            $sheet->getStyle("K{$row}")
                ->getNumberFormat()
                ->setFormatCode('dd-mm-yyyy');
            $sheet->getStyle("L{$row}")
                ->getNumberFormat()
                ->setFormatCode('dd-mm-yyyy');

        
        }
        
        $filename = 'SAPDataTemplate.xlsx';

        /* -----------------------------------------------------------------
        |  Write data rows if provided
        |------------------------------------------------------------------
        |  Mapping:
        |  A -> Order
        |  B -> Material
        |  C -> Material description
        |  D -> Plant
        |  E -> Order quantity
        |  F -> Delivered quantity
        |  G -> Confirmed quantity
        |  H -> Unit of measure
        |  I -> Batch
        |  J -> System Status
        |  K -> Start date (sched)
        |  L -> Scheduled finish date
        |  M -> Sales Order
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

                $sheet->setCellValue("A{$rowNumber}", $v('order_number'));
                $sheet->setCellValue("B{$rowNumber}", $v('material'));
                $sheet->setCellValue("C{$rowNumber}", $v('material_description'));
                $sheet->setCellValue("D{$rowNumber}", $v('plant'));
                $sheet->setCellValue("E{$rowNumber}", $v('order_quantity'));
                $sheet->setCellValue("F{$rowNumber}", $v('delivered_quantity'));
                $sheet->setCellValue("G{$rowNumber}", $v('confirmed_quantity'));
                $sheet->setCellValue("H{$rowNumber}", $v('unit_of_measure'));
                $sheet->setCellValue("I{$rowNumber}", $v('batch'));
                $sheet->setCellValue("J{$rowNumber}", $v('system_status'));
                $sheet->setCellValue("K{$rowNumber}", $ymdToExcel($record['start_date'] ?? null));
                $sheet->setCellValue("L{$rowNumber}", $ymdToExcel($record['scheduled_finish_date'] ?? null));
                $sheet->setCellValue("M{$rowNumber}", $v('sales_order'));
                $sheet->setCellValue("N{$rowNumber}", $v('error_json'));

                $rowNumber++;
            }

            $filename = 'FailedSAPData.xlsx';
            $sapTempImportDataModel
                    ->where('file_id', $fileId)
                    ->where('error_json !=', '0')
                    ->delete();
        } else {
            $templateName = 'SAP Data Template';
            // Check if record exists
            $existing = $this->masterTemplatesPasswordModel->where('template_name', $templateName)->first();

            if ($existing) {
                // Update password
                $this->masterTemplatesPasswordModel->update($existing['id'], ['password' => $passwordForTemplate]);
            } else {
                // Insert new
                $newId = $this->masterTemplatesPasswordModel->insert([
                    'template_name' => $templateName,
                    'password'      => $passwordForTemplate
                ]);
            }
        }

        // Output
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        $writer->save("php://output");
    }

    public function downloadSAPFailedRecords(){
        
        $sapFileImportLogModel  = new SAPFileImportLogModel();
        $sapTempImportDataModel  = new SapTempImportDataModel();
        $records = $sapFileImportLogModel->select('*')->orderBy('id', 'DESC')->findAll();
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
            
            $rows = $sapTempImportDataModel
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
                
                $this->downloadSAPTemplate($rows, $fileId);
                // return $this->respond([
                //     'data' => $rows,
                //     'status' => 'success',
                //     'message' => 'OK'
                // ], 200); 
            }
        }
    }

    public function triggerSAPFileValidation()
    {
        $sapFileImportLogModel  = new SAPFileImportLogModel();
        $sapTempImportDataModel  = new SapTempImportDataModel();
        $records = $sapFileImportLogModel->select('*')
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
            $command = 'php ' . ROOTPATH . 'spark validate:sapfiledata ' . escapeshellarg($fileId);
            $logfile = WRITEPATH . 'logs/cli_job_' . date('Ymd_His') . '.log';
            // echo $command;
            // Run the command in background and log output
            exec("$command > $logfile 2>&1 &");

            // if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //     // Windows
            //     // pclose(popen("start /B \"$command\" > \"$logfile\" 2>&1", "r"));
            //     pclose(popen("start \"\" /B $command > \"$logfile\" 2>&1", "r"));
            // } else {
            //     // Linux / Mac
            //     exec("$command > \"$logfile\" 2>&1 &");
            // }

    
            return $this->respond([
                'status' => 'success',
                'message' => 'Validation job started for file ID ' . $fileId,
                'command' => $command,
                'OS' => strtoupper(substr(PHP_OS, 0, 3))
            ], 200);
        }

    }


    public function getSAPFileStatus(){
        $sapFileImportLogModel  = new SAPFileImportLogModel();
        $sapTempImportDataModel  = new SapTempImportDataModel();

        $records = $sapFileImportLogModel->select('*')->orderBy('id', 'DESC')->findAll();
        $failedRecords = $sapTempImportDataModel
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

/**
 * Transfer data from sap_data to sap_data_history
 */
private function transferSapData()
{
    $sapDataModel = new \App\Models\SapDataModel();
    $sapDataHistoryModel = new \App\Models\SapDataHistoryModel();

    // Fetch current data from sap_data table
    $existingData = $sapDataModel->findAll();

    // Insert into sap_data_history if data exists
    if($existingData){
        $defaultUserId = 1; // Replace with a valid ID from the `users` table
        foreach ($existingData as &$row) {
            // $row['insertedBy'] = $row['insertedBy'] ?: $defaultUserId; // Use default user ID if invalid
            $row['sapId'] = $row['id']; // Use default user ID if invalid
            unset($row['id']);
        }
        // print_r($existingData); die;

        // Insert into sap_data_history
        if (!$sapDataHistoryModel->insertBatch($existingData)) {
            log_message('error', 'Error inserting into SAP_DATA_HISTORY: ' . json_encode($db->error()));
            return $this->fail("Error transferring data to history table: " . json_encode($db->error()), 500);
        }
    }
}

/**
 * Clear all data from sap_data table
 */
private function clearSapData()
{
    $sapDataModel = new \App\Models\SapDataModel();
    //  $sapDataModel->truncate();
     $sapDataModel->where('id is not null')->delete();
}

/**
 * Insert new data into sap_data table
 */
private function insertSapData($insertData)
{
    $sapDataModel = new \App\Models\SapDataModel();

    if (!empty($insertData)) {
        $sapDataModel->insertBatch($insertData);
    }
}



    public function get_sap_data()
    {
        $user = $this->userModel->find(auth()->user()->id);
        // Query params
        // $startDate = $this->request->getGet('start_date');
        // $endDate = $this->request->getGet('end_date');
        // $month_full_year = $this->request->getGet('month_full_year');
        // $weekNumber = $this->request->getGet('week_number');
        // $weekStart = $this->request->getGet('week_start');
        // $weekEnd = $this->request->getGet('week_end');
        // // Query parmas end

        // // echo $startDate ?? "Not getting startDate";
        // // echo $endDate ?? "Not getting endDate";
        // // echo $month_full_year ?? "Not getting month_full_year";
        // // echo $weekNumber ?? "Not getting weekNumber";
        // // echo $weekStart ?? "Not getting weekStart";
        // // echo $weekEnd ?? "Not getting weekEnd";
        // // die();

        // $filters = [
        //     // 'wom.customer' => 'Unbrako USA LLC', // Exact match
        //     // 'pm.finish' => '2',
        //     // 'sap_segment.name' => ['SPM', 'GPM'], // WHERE IN condition
        // ];

        // if($startDate and $endDate){
        //     $filters["wom.delivery_date" . " >="] = $startDate;
        //     $filters["wom.delivery_date" . " <="] = $endDate;
        // } else if ($weekStart and $weekEnd){
        //     $filters["wom.delivery_date" . " >="] = $weekStart;
        //     $filters["wom.delivery_date" . " <="] = $weekEnd;
        // } else if ($month_full_year){
        //     $month_year = explode('/', $month_full_year);

        //     $filters["YEAR(wom.delivery_date)"] = $month_year[1];
        //     $filters["MONTH(wom.delivery_date)"] = $month_year[0];
        // }
        // // if($user->role != 1){
        // //     $filters['modules.responsible'] = auth()->user()->id;
        // // }
        // $orderBy = [
        //     'wom.delivery_date' => 'DESC',
        // ];
        // $limit = 2000;
        // $offset = 0;
        // $sapData = $this->sapDataModel->getSapData($filters, $orderBy, $limit, $offset);

        $request = $this->request;
        $postData = $request->getJSON(true); // Get POST data as array

        // Get page from query param (e.g., ?page=2), default to 1
        $page = (int) $this->request->getGet('page');
        $page = max($page, 1); // Ensure at least 1
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $sapCalculatedSummaryModel = new SapCalculatedSummaryModel();
        $builder = $sapCalculatedSummaryModel->select('*');

        // Handle filters
        if (!empty($postData['filterBy']) && is_array($postData['filterBy'])) {
            foreach ($postData['filterBy'] as $field => $filter) {
                // print_r($field);
                // print_r($filter);
                // die();
                if (!isset($filter['value']) || !is_array($filter['value'])) {
                    continue; // skip invalid filter
                }

                $values = $filter['value'];
                $builder->whereIn($field, $values);
                // if (count($values) === 2 && $values[0] !== null && $values[1] !== null) {
                //     // Apply BETWEEN condition
                //     $builder->where("{$field} >=", $values[0]);
                //     $builder->where("{$field} <=", $values[1]);
                // }
            }
        }

        // Handle order by
        if (!empty($postData['orderBy']) && isset($postData['orderBy']['field']) && isset($postData['orderBy']['value'])) {
            $field = $postData['orderBy']['field'];
            $direction = $postData['orderBy']['value'];

            if ($direction == 1) {
                $builder->orderBy($field, 'ASC');
            } elseif ($direction == -1) {
                $builder->orderBy($field, 'DESC');
            }
            // If value is not 1 or -1, skip ordering
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        // Get paginated result
        $data = $builder->findAll($perPage, $offset);


        if (!empty($data)) {
            return $this->respond([
                'data' => $data,
                'filterData' => $postData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page'     => $perPage,
                    'total'        => $total,
                    'last_page'    => ceil($total / $perPage)
                ]
            ], 200); // HTTP 200 OK
        } else {
            return $this->respond([
                'message' => 'No data found.',
                'data' => [],
                'filterData' => $postData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page'     => $perPage,
                    'total'        => $total,
                    'last_page'    => ceil($total / $perPage)
                ]
            ], 200); // HTTP 404 Not Found
        }
    }



    public function get_sap_data2()
    {
        $user = $this->userModel->find(auth()->user()->id);
        $moduleIds = $this->request->getVar('moduleIds');
        $machineRevIds = $this->request->getVar('machineRevIds');
        
        $filters = [
            'pm.machine_module' =>  $moduleIds,
            'pm.machine' => $machineRevIds
            // 'wom.customer' => 'Unbrako USA LLC', // Exact match
            // 'pm.finish' => '2',
            // 'sap_segment.name' => ['SPM', 'GPM'], // WHERE IN condition
        ];
        // if($user->role != 1){
        //     $filters['modules.responsible'] = auth()->user()->id;
        // }
        $orderBy = [
            'pm.prod_group' => 'ASC',
            'pm.size' => 'ASC',
            'pm.spec' => 'ASC',
            'pm.drawn_dia1' => 'ASC',
            'derived.materialNumber' => 'ASC',
            'derived.materialDescription' => 'ASC',
            'derived.work_order' => 'ASC',
            'sap_segment.name' => 'ASC',
            'wom.customer' => 'ASC',
        ];
        $limit = 2000;
        $offset = 0;
        $sapData = $this->sapDataModel->getSapData($filters, $orderBy, $limit, $offset);

        if (!empty($sapData)) {
            return $this->respond([
                'data' => $sapData
            ], 200); // HTTP 200 OK
        } else {
            return $this->respond([
                'message' => 'No data found.'
            ], 404); // HTTP 404 Not Found
        }

        // SELECT derived.*, 
        //     derived.work_order,
        //     wom.customer,
        //     wom.reciving_date,
        //     wom.delivery_date,
        //     wom.responsible_person as wom_responsible_person_id,
        //     wom.segment as wom_segment_id,
        //     pm.finish,
        //     pm.finish_wt,
        //     pm.size,
        //     pm.prod_group,
        //     pm.length,
        //     pm.spec,
        //     pm.rod_dia1,
        //     pm.drawn_dia1,
        //     pm.machine as machine_id,
        //     pm.machine_module as module_id,
        //     pm.seg2 as seg2_id,
        //     pm.seg3 as seg3_id,
        //     mc.name as machine_name,
        //     mc.speed as machine_speed,
        //     mc.no_of_mc,
        //     modules.name as module_name,
        //     sap_responsible.name as sap_responsible_person_name,
        //     sap_segment.name as sap_segment_name,
        //     seg2.name as seg2_name,
        //     seg3.name as seg3_name
        //     FROM 
        //         (
        //             SELECT 
        //                 *, 
        //                 CASE 
        //                     WHEN UPPER(SUBSTRING(batch, 1, 2)) = 'DB' THEN SUBSTRING(batch, 1, 6)
        //                     ELSE SUBSTRING(batch, 1, 5)
        //                 END AS work_order
        //             FROM 
        //                 sap_data
        //         ) AS derived
        //     LEFT JOIN work_order_master wom 
        //         ON wom.work_order_db = derived.work_order
        //     LEFT JOIN product_master as pm
        //         ON pm.material_number_for_process = derived.materialNumber
        //     LEFT JOIN machines mc
        //         ON mc.id = pm.machine
        //     LEFT JOIN modules ON
        //         modules.id = pm.machine_module
        //     LEFT JOIN users sap_responsible
        //         on sap_responsible.id = modules.responsible
        //     LEFT JOIN segments as sap_segment ON sap_segment.id = pm.segment
        //     LEFT JOIN segments as wom_segment ON wom_segment.id = wom.segment
        //     LEFT JOIN seg_2 seg2 on seg2.id = pm.seg2
        //     LEFT JOIN seg_3 seg3 on seg3.id = pm.seg3
        //     WHERE pm.machine_module IN (6) AND
        //     pm.machine IN (54, 55)
        //     ORDER BY pm.prod_group,
        //     pm.size,
        //     pm.spec,
        //     pm.drawn_dia1,
        //     derived.materialNumber,
        //     derived.materialDescription,
        //     derived.work_order,
        //     sap_segment.name,
        //     wom.customer ASC;
    }



    public function updateRow($id = null)
    {
        // Check if the row with the given ID exists
        $data = $this->sapDataModel->find($id);

        if (!$data) {
            return $this->respond([
                'status' => 404,
                'message' => "Row with ID $id not found."
            ], 404);
        }

        // Get input data
        $input = $this->request->getJSON(true);

        // Validate input
        $validation = \Config\Services::validation();

        $validation->setRules([
            'forge_commite_week' => 'required|string',
            'this_month_forge_qty' => 'required|numeric',
            'special_remarks' => 'permit_empty|string',
            'rm_delivery_date' => 'required|valid_date',
            'rm_allocation_priority' => 'required|string'
        ]);

        if (!$validation->run($input)) {
            return $this->respond([
                'status' => 400,
                'message' => 'Validation failed',
                'errors' => $validation->getErrors()
            ], 400);
        }

        // Update row
        $input['updated_at'] = date('Y-m-d H:i:s'); // Add updated_at timestamp

        if ($this->sapDataModel->update($id, $input)) {
            return $this->respond([
                'status' => 200,
                'message' => "Row with ID $id successfully updated.",
                'data' => $this->sapDataModel->find($id)
            ], 200);
        }

        return $this->respond([
            'status' => 500,
            'message' => 'Failed to update the row.'
        ], 500);
    }
      
    
    public function updateToForge($id)
    {
        $record = $this->sapDataModel->find($id);

        if (!$record) {
            return $this->respond(['status' => 'error', 'message' => 'Record not found'], 404);
        }

        // Get JSON request body
        $json = $this->request->getJSON();
        $new_to_forge = $json->to_forge ?? null;

        if ($new_to_forge === null) {
            return $this->respond(['status' => 'error', 'message' => 'to_forge value is required'], 400);
        }

        // Calculate the current to_forge value
        $orderQuantity_GMEIN = $record['orderQuantity_GMEIN'];
        $confirmedQuantity_GMEIN = $record['confirmedQuantity_GMEIN'];
        // $current_to_forge = $orderQuantity_GMEIN - $confirmedQuantity_GMEIN;
        
        // Fetch required fields
        $to_forge = (int) $record['to_forge'];
        $forged_so_far = (int) $record['forged_so_far'];

        // Calculate max allowed quantity
        $current_to_forge = $to_forge - $forged_so_far;

        // Calculate 10% upper limit
        $max_limit = $current_to_forge * 1.1;

        // Validate new value
        if ($new_to_forge < 0) {
            return $this->respond(['status' => 'error', 'message' => 'to_forge value cannot be negative'], 400);
        }
        if ($new_to_forge > $max_limit) {
            return $this->respond(['status' => 'error', 'message' => 'to_forge value exceeds 10% limit'], 400);
        }

        // Update the record
        $updateData = ['to_forge' => $new_to_forge, 'updated_at' => date('Y-m-d H:i:s')];
        $this->sapDataModel->update($id, $updateData);

        return $this->respond(['status' => 'success', 'message' => 'to_forge updated successfully', 'new_to_forge' => $new_to_forge], 200);
    }



    public function updateWeeklyForge($id)
    {
        $record = $this->sapDataModel->find($id);

        if (!$record) {
            return $this->respond(['status' => 'error', 'message' => 'Record not found'], 404);
        }

        // Get JSON request body
        $json = $this->request->getJSON();
        $quantity = $json->forge_qty ?? null;

        if ($quantity === null || !is_numeric($quantity) || $quantity <= 0) {
            return $this->respond(['status' => 'error', 'message' => 'Quantity must be a positive number'], 400);
        }

        // Fetch required fields
        $to_forge = (int) $record['to_forge'];
        $forged_so_far = (int) $record['forged_so_far'];

        // Calculate max allowed quantity
        $max_allowed = $to_forge - $forged_so_far;

        // Validation: Quantity should not exceed remaining forging capacity
        if ($quantity > $max_allowed) {
            return $this->respond(['status' => 'error', 'message' => 'Quantity exceeds the allowable forging limit'], 400);
        }

        // Determine the first available week to update
        $weekFields = ['week1', 'week2', 'week3', 'week4'];
        $updateField = null;

        foreach ($weekFields as $week) {
            if (empty($record[$week])) {
                $updateField = $week;
                break;
            }
        }

        if (!$updateField) {
            return $this->respond(['status' => 'error', 'message' => 'No available week slot to update'], 400);
        }

        // Update the record
        $updateData = [
            $updateField => $quantity,
            // 'forged_so_far' => $forged_so_far + $quantity, // Increment forged_so_far
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->sapDataModel->update($id, $updateData);

        return $this->respond([
            'status' => 'success',
            'message' => "Updated $updateField with quantity $quantity",
            'updated_field' => $updateField,
            'new_forged_so_far' => $forged_so_far + $quantity
        ], 200);
    }

    private function _getWeek(){
        $query = $this->weeklyPeriodsModel->select('*')
        ->where('start_date <=', $this->current_date)
        ->where('end_date >=', $this->current_date)
        ->get();

        if ($query->getNumRows() > 0) {
            return $query->getRowArray(); // Return the record as an array
        } else {
            return null; // Return null if no matching record is found
        }
    }


    public function updateForgedSoFar($id)
    {
        $record = $this->sapDataModel->find($id);

        if (!$record) {
            return $this->respond(['status' => 'error', 'message' => 'Record not found'], 404);
        }

        // Get JSON request body
        $json = $this->request->getJSON();
        $new_forged_so_far = $json->forged_so_far ?? null;

        // Validation: Ensure forged_so_far is not negative
        if ($new_forged_so_far === null || !is_numeric($new_forged_so_far) || $new_forged_so_far < 0) {
            return $this->respond(['status' => 'error', 'message' => 'Forged so far must be zero or more'], 400);
        }

        // Fetch required fields
        $to_forge = (int) $record['to_forge'];
        $forged_so_far = (int) $record['forged_so_far'];

        // Calculate max allowed quantity
        $max_allowed = $to_forge - $forged_so_far;

        // Validation: Quantity should not exceed remaining forging capacity
        if ($new_forged_so_far > $max_allowed) {
            return $this->respond(['status' => 'error', 'message' => 'Quantity exceeds the allowable forging limit'], 400);
        }

        // Update the forged_so_far field
        $updateData = [
            'forged_so_far' => $forged_so_far + $new_forged_so_far,
            // 'to_forge' => $to_forge - $forged_so_far,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->sapDataModel->update($id, $updateData);

        return $this->respond([
            'status' => 'success',
            'message' => "Updated forged_so_far to $new_forged_so_far",
            'new_forged_so_far' => $new_forged_so_far
        ], 200);
    }


    public function get_sap_data3()
    {
        $user = $this->userModel->find(auth()->user()->id);
        $moduleIds = $this->request->getVar('moduleIds');
        $machineRevIds = $this->request->getVar('machineRevIds');
        
        $filters = [
            // 'pm.machine_module' =>  $moduleIds,
            // 'pm.machine' => $machineRevIds
            // 'wom.customer' => 'Unbrako USA LLC', // Exact match
            // 'pm.finish' => '2',
            // 'sap_segment.name' => ['SPM', 'GPM'], // WHERE IN condition
        ];
        if ($moduleIds){
            $filters['pm.machine_module'] = $moduleIds;
        }
        if ($machineRevIds){
            $filters['pm.machine'] = $machineRevIds;
        }
        // if($user->role != 1){
        //     $filters['modules.responsible'] = auth()->user()->id;
        // }
        $orderBy = [
            'pm.machine_module' => 'ASC',
            'pm.machine' => 'ASC',
        ];
        $limit = 2000;
        $offset = 0;
        $sapData = $this->sapDataModel->getSapData($filters, $orderBy, $limit, $offset);

        if (!empty($sapData)) {
            return $this->respond([
                'data' => $sapData
            ], 200); // HTTP 200 OK
        } else {
            return $this->respond([
                'message' => 'No data found.'
            ], 404); // HTTP 404 Not Found
        }
    }


    public function loadFilterFields()
    {
        $db = Database::connect();
        $builder = $db->table('sap_calculated_summary');

        // Full list of fields from the table (excluding `id` and `created_at`)
        $fields = [
            'sap_id', 'sap_orderNumber', 'pm_order_number', 'systemStatus',
            'orderQuantity_GMEIN', 'deliveredQuantity_GMEIN', 'confirmedQuantity_GMEIN',
            'monthly_plan', 'monthly_fix_plan', 'weekly_plan', 'materialNumber', 'materialDescription',
            'sap_plant', 'wom_plant', 'unitOfMeasure_GMEIN', 'batch', 'work_order',
            'reciving_date', 'delivery_date', 'wo_add_date', 'work_order_db', 'customer',
            'responsible_person_name', 'marketing_person_name', 'wom_segment_id', 'wom_segment_name',
            'quality_inspection_required', 'finish_wt', 'to_forge_qty', 'to_forge_wt',
            'forged_so_far', 'this_month_forge_wt', 'module_id', 'module_name',
            'seg2_id', 'seg2_name', 'seg3_id', 'seg3_name', 'finish_id', 'finish_name',
            'group_id', 'group_name', 'machine_name', 'no_of_machines',
            'cheese_wt', 'size', 'length', 'spec', 'rod_dia1', 'drawn_dia1',
            'condition_of_rm', 'pm_special_remarks', 'main_special_remarks', 'pm_bom',
            'rm_component', 'rm_allocation_priority', 'rm_delivery_date', 'module_multiplier',
            'to_forge_rm_wt', 'total_allocation', 'total_allocation_2', 'plan_print_qty',
            'this_month_forge_rm_wt', 'act_allocated_balance_rm_wt', 'allocated_balance_rm_wt',
            'rm_correction', 'plan_allocation', 'allocated_product_wt', 'allocated_product_qty',
            'per_of_efficiency', 'machine_speed', 'no_of_shift', 'plan_no_of_machine',
            'per_day_booking', 'final_pending_qty', 'pending_qty', 'pending_wt', 'pending_rm_wt',
            'pending_from_outside_1', 'pending_from_outside', 'no_of_days_booking', 'no_of_day_weekly_planning'
        ];

        $result = [];

        foreach ($fields as $field) {
            try {
                $values = $builder->select($field)
                    ->distinct()
                    ->where("$field IS NOT", null)
                    ->orderBy($field, 'asc')
                    ->get()
                    ->getResultArray();

                $result[$field] = array_values(array_filter(array_column($values, $field), fn ($val) => $val !== ''));
            } catch (\Exception $e) {
                $result[$field] = ['Error' => $e->getMessage()];
            }
        }

        return $this->respond($result);
    }


    public function getWeeklyPlanning()
    {
        $data = $this->request->getJSON(true); // Get JSON as associative array
        $moduleId = $data['module_id'] ?? null;

        $model = new SapCalculatedSummaryModel();

        // $builder = $model->builder();

        // $builder->select('machine_name, no_of_machines, plan_no_of_machine, machine_speed, no_of_shift, per_of_efficiency, no_of_days_booking, pending_wt, no_of_day_weekly_planning, allocated_product_wt');

        // if (!empty($moduleId)) {
        //     $builder->where('module_id', $moduleId);
        // }

        // $builder->groupBy('machine_name');
        // $builder->orderBy('machine_name', 'ASC');

        // $result = $builder->get()->getResult();

        $filterArray = [];
        if (!empty($moduleId)) {
            $filterArray = ['module_id' => $moduleId ];
        }

        $result = $model->getSummaryRows($filterArray, 'machine_name', 'machine_name');

        $weeklyPlanningModel = new WeeklyPlanningModel();

        if($moduleId){
            helper('week_calc');
            $data = get_current_week_info();
            // Check if record already exists
            $existing = $weeklyPlanningModel->where([
                'module'      => $moduleId,
                'week_number' => $data['week_number'],
            ])->first();
            $weeklyPlanId = null;
            $existingUnSavedData = [];
            $weeklyPlanningDataModel = new WeeklyPlanningDataModel();
            if ($existing) {
                $weeklyPlanId = $existing['id'];
                if($existing['is_permanent'] == 1){
                    $data = $weeklyPlanningDataModel->where([
                        'weekly_planning_id'      => $existing['id']
                    ])->orderBy('machine_name', 'ASC')->findAll();
                    return $this->respond([
                        'status' => 'exists',
                        'data'   => $data
                    ]);
                } else {
                    $existingUnSavedData = $weeklyPlanningDataModel->where('weekly_planning_id',$weeklyPlanId)->findAll();
                    $weeklyPlanningDataModel->where('weekly_planning_id',$weeklyPlanId)->delete();
                }
            } else {
                // Prepare insert data (make sure required fields are provided)
                $insertData = [
                    'module'      => $moduleId,
                    'week_number' => $data['week_number'],
                    'start_date'  => $data['start_date'] ?? null,
                    'end_date'    => $data['end_date'] ?? null,
                    'timestamp'   => date('Y-m-d H:i:s'),
                    'user'        => null,
                ];
    
                if (!$weeklyPlanningModel->insert($insertData)) {
                    return $this->failServerError('Failed to insert record');
                }
    
                $weeklyPlanId = $weeklyPlanningModel->getInsertID();
            }

            $insertWeeklyPlanningData = [];
                
            foreach ($result as $row) {
                $data = $this->_getExtraDetails($weeklyPlanningDataModel, $existingUnSavedData, $row['machine_id'], $weeklyPlanId);
                $rowData = [
                    'weekly_planning_id'=> $weeklyPlanId,
                    'machine_id'  => $row['machine_id'],
                    'machine_name'  => $row['machine_name'],
                    'no_of_machines'  => $row['no_of_machines'],
                    'plan_no_of_machines'  => $row['plan_no_of_machine'],
                    'machine_speed'  => $row['machine_speed'],
                    'no_of_shift'  => $row['no_of_shift'],
                    'per_of_efficiency'  => $row['per_of_efficiency'],
                    'no_of_days_booking'  => $row['no_of_days_booking'],
                    'pending_wt'  => $row['pending_wt'],
                    'no_of_day_weekly_planning'  => $row['no_of_day_weekly_planning'],
                    'allocated_product_wt'  => $row['allocated_product_wt'],
                    'capacity'  => $row['capacity'],
                    'single_mc_shift_capacity'  => $row['single_mc_shift_capacity'],
                    'rm_tpm_booking'  => empty($data) ? '' : $data['rm_tpm_booking'],
                    'rm_due_to_development'  => empty($data) ? '' : $data['rm_due_to_development'],
                    'gap'  => empty($data) ? '' : $data['gap'],
                ];

                array_push($insertWeeklyPlanningData, $rowData);
            }
            // print_r($insertWeeklyPlanningData); die();
            if(!empty($insertWeeklyPlanningData)){
                if (!$weeklyPlanningDataModel->insertBatch($insertWeeklyPlanningData)) {
                    return $this->failServerError('Failed to insert record');
                }
            }
        }

        return $this->respond([
            'status' => 'success',
            'data' => $insertWeeklyPlanningData
        ]);
    }

    private function _getExtraDetails($weeklyPlanningDataModel, $existingUnSavedData, $machineId, $weeklyPlanId){
        foreach ($existingUnSavedData as $row) {
            if($row['machine_id'] == $machineId && $row['weekly_planning_id'] == $weeklyPlanId){
                return $row;
            }
        }
        return '';
    }


    public function loadSapFilterSuggestions(){
        $fields = [
            'monthly_plan',
            'monthly_fix_plan',
            'forge_commite_week',
            'priority_list',
            'special_remarks',
        ];

        $result = [];

        foreach ($fields as $field) {
            try {
                $values = $this->sapDataModel->select($field)
                    ->distinct()
                    ->where("$field IS NOT", null)
                    ->orderBy($field, 'asc')
                    ->get()
                    ->getResultArray();

                $result[$field] = array_values(array_filter(array_column($values, $field), fn ($val) => $val !== ''));
            } catch (\Exception $e) {
                $result[$field] = ['Error' => $e->getMessage()];
            }
        }

        return $this->respond($result);
    }



    public function updateBulkAdminFields()
    {
        $data = $this->request->getJSON(true);

        if (!isset($data['sap_ids']) || !is_array($data['sap_ids']) || count($data['sap_ids']) === 0) {
            return $this->fail('sap_ids is required and must be a non-empty array.');
        }

        $sapIds = array_filter($data['sap_ids'], fn($id) => is_numeric($id));
        $sapDataModel = new SapDataModel();

        // Validate foreign key (if provided)
        if (isset($data['surface_treatment_process']) && $data['surface_treatment_process'] !== '') {
            $surfaceId = (int) $data['surface_treatment_process'];
            if ($surfaceId <= 0) {
                return $this->fail('Invalid surface_treatment_process ID.');
            }

            $surfaceModel = new SurfaceTreatmentProcessModel();
            if (!$surfaceModel->find($surfaceId)) {
                return $this->fail('surface_treatment_process ID not found.');
            }
        }

        // Validate string fields
        $stringFields = ['monthly_plan', 'monthly_fix_plan', 'forge_commite_week', 'priority_list', 'special_remarks'];
        $updateFields = [];
        foreach ($stringFields as $field) {
            if (array_key_exists($field, $data)) {
                if (is_null($data[$field])) {
                    return $this->fail("$field cannot be null.");
                }
                if (is_string($data[$field]) && trim($data[$field]) !== '') {
                    // unset($data[$field]); // skip empty strings
                    $updateFields[$field] = trim($data[$field]);
                }
            }
        }

        if (isset($surfaceId)) {
            $updateFields['surface_treatment_process'] = $surfaceId;
        }

        // Final update data to apply to each row
        // $updateFields = array_intersect_key($data, array_flip(array_merge($stringFields, ['surface_treatment_process'])));
        if (empty($updateFields)) {
            return $this->fail('No valid update fields provided.');
        }

        // Build array for batch update
        $batchUpdateData = [];
        foreach ($sapIds as $id) {
            if ($sapDataModel->find($id)) {
                $row = array_merge(['id' => $id], $updateFields);
                $batchUpdateData[] = $row;
            }
        }

        // print_r($batchUpdateData);
        // die();

        if (!empty($batchUpdateData)) {
            $sapDataModel->updateBatch($batchUpdateData, 'id');
        }

        return $this->respond([
            'message' => 'Batch update completed.',
            'updated_count' => count($batchUpdateData),
            'updated_ids' => array_column($batchUpdateData, 'id'),
            'fields_updated' => array_keys($updateFields)
        ]);
    }


    public function addPartNumber($id = null)
    {
        $requestData = $this->request->getJSON(true); // Get JSON body as array

        if (!$id || !is_numeric($id)) {
            return $this->failValidationErrors('Invalid or missing ID in URL.');
        }

        // Check if Work Order exists
        $workOrderModel = new WorkOrderMasterModel();
        $workOrder = $workOrderModel->find($id);

        if (!$workOrder) {
            return $this->failNotFound("Work order with ID $id not found.");
        }

        $data = $requestData['parts'] ?? [];

        if (!is_array($data) || empty($data)) {
            return $this->failValidationErrors('Invalid or missing data array.');
        }

        $productModel = new ProductMasterModel();
        $finishModel = new FinishModel();

        $success = [];
        $failed = [];

        foreach ($data as $row) {
            $errors = [];

            $partId = $row['part_number'] ?? null;
            // $finishId = $row['finish'] ?? null;
            $quantity = $row['quantity'] ?? null;

            // Check product exists
            if (!$partId || !$productModel->where('material_number', $partId)->first()) {
                $errors[] = 'Invalid part_number';
            }

            $partInfo = $productModel->where('material_number', $partId)->first();
            // print_r($partInfo); die();
            // Check finish exists (optional check: only if finish is not null)
            // if ($finishId !== null && !$finishModel->find($finishId)) {
            //     $errors[] = 'Invalid finish';
            // }

            // Validate quantity
            if (!is_numeric($quantity)) {
                $errors[] = 'Quantity must be numeric';
            }

            if (!empty($errors)) {
                $failed[] = [
                    'row' => $row,
                    'errors' => $errors
                ];
            } else {
                $success[] = [
                    'orderNumber' => $partInfo['order_number'],
                    'plant' => $workOrder['plant'],
                    'batch' => $workOrder['work_order_db'],
                    'materialNumber'  => $partInfo['material_number'],
                    'materialDescription'  => $partInfo['material_description'],
                    'to_forge_qty'  => (int)$quantity,
                    // 'finish_id' => $finishId,
                    'orderQuantity_GMEIN' => $quantity,
                    'insertedBy' => user_id()
                ];
            }
        }

        // print_r($success); 
        // print_r($failed);die();

        if (!empty($failed)) {
            return $this->fail([
                'message' => 'Some rows failed validation.',
                'errors' => $failed
            ]);
        }

        // Insert into SapDataModel
        $sapModel = new SapDataModel();
        // $sapModel->insertBatch($success);

        return $this->respondCreated([
            'message' => 'Data inserted successfully.',
            'inserted_count' => count($success)
        ]);
    }

}

?>