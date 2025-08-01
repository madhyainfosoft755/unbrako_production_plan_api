<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\WOMFileImportLogModel;
use App\Models\WOMTempImportWorkOrderModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use DateTime;

class WOMImportController extends ResourceController
{
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

}
