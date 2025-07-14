<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PMFileImportLogModel;
use App\Models\PMTempImportProductModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use CodeIgniter\RESTful\ResourceController;

class PMImportController extends ResourceController
{
    public function upload()
    {
        $file = $this->request->getFile('upload_excel');
        if (!$file->isValid()) {
            return $this->failValidationErrors($file->getErrorString());
        }

        /* 1. store file */
        $newName = $file->getRandomName();
        $path    = WRITEPATH . 'uploads/';
        $file->move($path, $newName);

        /* 2. log row */
        $logModel = new PMFileImportLogModel();
        $fileId   = $logModel->insert([
            'original_name' => $file->getClientName(),
            'stored_name'   => $newName,
            'uploaded_by'   => user_id() ?? null,
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
        ], true);

        (new PMTempImportProductModel())->where('id !=', 'NULL')->delete();

        /* 3. dump → temp table fast */
        $this->dumpToTemp($path . $newName, $fileId);

        /* 4. kick CLI command */
        // exec(PHP_BINARY . ' ' . ROOTPATH . "spark validate:products {$fileId} > /dev/null 2>&1 &");

        return $this->respondCreated(['fileId' => $fileId, 'message' => 'File queued for validation']);
    }

    /**
     * Convert Excel rows to temp rows (2 000‑row chunks)
     */
    protected function dumpToTemp(string $filepath, int $fileId): void
    {
        $spreadsheet = IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        foreach ($sheet->toArray(null, true, true, false) as $idx => $r) {
            if ($idx === 0) continue;               // header

            // skip empty rows
            if (empty(array_filter($r, fn($v) => $v !== null && $v !== ''))) continue;

            $r = array_pad($r, 23, '');             // ensure A–W
            [$order,$mat,$matF,$matDesc,$machine,$module,$uom,
             $seg2,$seg3,$prodSize,$prodGroup,$prodLen,$finish,$segment,
             $finishWt,$cheeseWt,$rmSpec,$rodDia,$drawnDia,$remarks,$bom,
             $rmComp,$condRM] = $r;

            $rows[] = [
                'file_id'                  => $fileId,
                'row_index'                => $idx + 1,
                'order_no'                 => trim($order),
                'material_number'          => trim($mat),
                'material_number_froging'  => trim($matF),
                'material_description'     => trim($matDesc),
                'machine_name'             => trim($machine),
                'module'                   => trim($module),
                'uom'                      => trim($uom),
                'seg2'                     => trim($seg2),
                'seg3'                     => trim($seg3),
                'product_size'             => trim($prodSize),
                'product_group'            => trim($prodGroup),
                'product_length'           => trim($prodLen),
                'finish'                   => trim($finish),
                'segment'                  => trim($segment),
                'finish_wt'                => is_numeric($finishWt) ? number_format($finishWt, 2, '.', '') : null,
                'cheese_wt'                => is_numeric($cheeseWt) ? number_format($cheeseWt, 2, '.', '') : null,
                'rm_spec'                  => trim($rmSpec),
                'rod_dia1'                 => trim($rodDia),
                'drawn_dia1'               => trim($drawnDia),
                'special_remarks'          => trim($remarks),
                'bom'                      => trim($bom),
                'rm_component'             => trim($rmComp),
                'condition_raw_material'   => trim($condRM),
                'created_at'               => date('Y-m-d H:i:s'),
            ];

            if (count($rows) === 2000) {
                (new PMTempImportProductModel())->insertBatch($rows);
                $rows = [];
            }
        }
        if ($rows) {
            (new PMTempImportProductModel())->insertBatch($rows);
        }
    }

    /* error download identical to previous, replace model/table names */
}
