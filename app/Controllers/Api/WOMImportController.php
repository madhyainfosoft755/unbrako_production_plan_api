<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\WOMFileImportLogModel;
use App\Models\WOMTempImportWorkOrderModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\WorkOrderMasterModel;
use App\Models\ProductMasterModel;
use App\Models\SapDataModel;
use DateTime;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class WOMImportController extends ResourceController
{
    protected $workOrderMasterModel;
    public function __construct()
    {
        // Load models in the constructor
        $this->workOrderMasterModel = new WorkOrderMasterModel();
    }

    public function upload()
    {
        $file = $this->request->getFile('upload_excel');
        if (!$file->isValid()) {
            return $this->failValidationErrors($file->getErrorString());
        }

        // 1. Persist file
        $newName = $file->getRandomName();
        $path = WRITEPATH . 'uploads/';
        $file->move($path, $newName);

        // 2. Add log row
        $logModel  = new WOMFileImportLogModel();
        $fileId = $logModel->insert([
            'original_name' => $file->getClientName(),
            'stored_name'   => $newName,
            'uploaded_by'   => user_id() ?? null, // if you use auth
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
        ], true);

        (new WOMTempImportWorkOrderModel())->where('id !=', 'NULL')->delete();

        // 3. Rapidly parse & dump to dummy table (blocking HTTP once, still fast)
        $this->dumpToTemp($path . $newName, $fileId);

        // 4. Trigger CLI job asynchronously (Linux)
        // exec("php " . ROOTPATH . "spark validate:workorders {$fileId} > /dev/null 2>&1 &");

        return $this->respondCreated(['fileId' => $fileId, 'message' => 'File queued for validation']);
    }

    /**
     * Reads the sheet and batch‑inserts into temp table.
     */
    protected function dumpToTemp(string $filepath, int $fileId)
    {
        helper('date_excel');
        $spreadsheet = IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows  = [];
        foreach ($sheet->toArray(null, true, true, false) as $index => $row) {
            if ($index === 0) {
                // header, skip
                continue;
            }
            // (2) Skip completely blank rows
            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                continue;
            }
            // if(empty($row['A'])){
            //     // empty row, skip
            //     continue;
            // }
            // echo $index;
            // print_r($row); continue; die();
            [$plant, $wo, $customer, $respName,
             $segmentName, $mktName, $woDate,
             $recvDate, $delDate, $items, $weight] = $row;

            $rows[] = [
                'file_id'                 => $fileId,
                'row_index'               => $index,
                'plant'                   => trim($plant),
                'work_order_db'           => trim($wo),
                'customer'                => trim($customer),
                'responsible_person_name' => trim($respName),
                'segment_name'            => trim($segmentName),
                'marketing_person_name'   => trim($mktName),
                'wo_add_date'             => $this->dmy_to_iso($woDate),
                'reciving_date'           => $this->dmy_to_iso($recvDate),
                'delivery_date'           => $this->dmy_to_iso($delDate),
                'no_of_items'             => is_numeric($items) ? (int)$items : null,
                'weight'                  => is_numeric($weight) ? number_format($weight, 5, '.', '') : null,
                'created_at'              => date('Y-m-d H:i:s'),
            ];

            // Insert in 2 000‑row chunks
            if (count($rows) === 2000) {
                (new WOMTempImportWorkOrderModel())->insertBatch($rows);
                $rows = [];
            }
        }
        if ($rows) {
            (new WOMTempImportWorkOrderModel())->insertBatch($rows);
        }
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




    public function uploadPartsNumberBulk()
    {
        $file = $this->request->getFile('upload_excel');
        $work_order_id = $this->request->getVar('work_order_id');
        
        if (!$file->isValid()) {
            return $this->failValidationErrors($file->getErrorString());
        }

        $workOrder = $this->workOrderMasterModel->find($work_order_id);
        
        if (!$workOrder) {
            return service('response')->setJSON([
                'status' => false,
                'message' => 'Work Order not found'
            ])->setStatusCode(404); // Work order not found
        }

        // 1. Persist file
        $newName = $file->getRandomName();
        $path = WRITEPATH . 'uploads/';
        $file->move($path, $newName);

        $spreadsheet = IOFactory::load($path . $newName);
        $sheet = $spreadsheet->getActiveSheet();
        $results  = [];
        $productModel = new ProductMasterModel();
        $success = [];
        $failed = [];
        foreach ($sheet->toArray(null, true, true, false) as $index => $row) {
            if ($index === 0) {
                // header, skip
                continue;
            }
            // (2) Skip completely blank rows
            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                continue;
            }
            $excelRowNumber = $index + 1;
            [$material_number, $order_quantity] = $row;
            
            $errors = [];
            // Check product exists
            if (!$material_number || !$productModel->where('material_number_for_process', $material_number)->first()) {
                $errors[] = 'Invalid part_number';
            }

            $partInfo = $productModel->where('material_number_for_process', $material_number)->first();

            // Validate quantity
            if (!is_numeric($order_quantity)) {
                $errors[] = 'Quantity must be numeric';
            }

            if (!empty($errors)) {
                $failed[] = [
                    'row' => $row,
                    'errors' => $errors
                ];
                // $failed[$excelRowNumber] = implode(', ', $errors);
            } else {
                $success[] = [
                    'plant' => $workOrder['plant'],
                    'batch' => $workOrder['work_order_db'],
                    'materialNumber'  => $partInfo['material_number'],
                    'materialDescription'  => $partInfo['material_description'],
                    'to_forge_qty'  => (int)$order_quantity,
                    // 'finish_id' => $finishId,
                    'orderQuantity_GMEIN' => $order_quantity,
                    'insertedBy' => user_id()
                ];
            }

            $results[] = [
                'material_number' => $material_number,
                'order_quantity'  => $order_quantity,
                'error'           => !empty($errors) ? implode(', ', $errors) : ''
            ];
        }

        if (!empty($failed)) {
            $newSpreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $newSheet = $newSpreadsheet->getActiveSheet();

            // Header
            $newSheet->fromArray(
                ['Material Number', 'Order Quantity', 'error'],
                null,
                'A1'
            );

            // Data
            $newSheet->fromArray(
                $results,
                null,
                'A2'
            );

            $date = new DateTime();
            $timestamp = $date->format('d_m_Y_H_i_s_v');
            $newFilename = $workOrder['work_order_db'] . '_' . $timestamp . '.xlsx';
            // Clear previous buffers
            if (ob_get_length()) {
                ob_end_clean();
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($newSpreadsheet);

            // Disable compression (important on Windows/XAMPP)
            ini_set('zlib.output_compression', '0');

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"$newFilename\"");
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: 0");
            header("Pragma: public");
            header("Access-Control-Expose-Headers: Content-Disposition");

            $writer = new Xlsx($newSpreadsheet);
            $writer->save("php://output");
            exit();
        }

        // Insert into SapDataModel
        $sapModel = new SapDataModel();
        $sapModel->insertBatch($success);


        $phpBinary = PHP_BINARY; // current php path
        $spark = ROOTPATH . 'spark';

        // Build command
        $command = escapeshellcmd($phpBinary . ' ' . $spark . ' sap:generate-summary');
        // print_r($spark . ' sap:gs
        // $command = 'php ' . ROOTPATH . 'spark validate:sapfiledata ' . escapeshellarg($fileId);
        $logfile = WRITEPATH . 'logs/cli_job_' . date('Ymd_His') . '.log';
        exec("$command > $logfile 2>&1 &");

        return $this->respondCreated([
            'message' => 'Data inserted successfully.',
            'inserted_count' => count($success)
        ]);

    }

}
