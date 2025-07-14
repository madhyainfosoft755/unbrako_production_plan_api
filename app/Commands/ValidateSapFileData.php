<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\SapFileImportLogModel;
use App\Models\SapTempImportDataModel;
use App\Models\SapDataModel;
use App\Models\ProductMasterModel;
use App\Models\WorkOrderMasterModel;

class ValidateSapFileData extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'custom';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'validate:sapfiledata';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Validate & import SAP Excel rows';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'validate:productmastersfiledata {fileId}';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'fileId' => 'The ID of the uploaded Excel file to process.'
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--dry-run' => 'Only validate, do not insert into DB',
        '--limit'   => 'Limit number of rows to validate'
    ];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        if (!$params) return CLI::error('Missing file_id');

        $fileId = (int)$params[0];

        $logModel   = new SapFileImportLogModel();
        $temp       = new SapTempImportDataModel();
        $final      = new SapDataModel();
        $productM   = new ProductMasterModel();
        $workOrders = new WorkOrderMasterModel();

        $log = $logModel->find($fileId);
        if (!$log) return CLI::error('Invalid file_id');

        $logModel->update($fileId, ['status' => 'processing']);

        // Load all valid product material numbers
        $validMaterials = array_column($productM->select('material_number_for_process')->findAll(), 'material_number_for_process');

        // Load all valid work order db values
        $validWorkOrders = array_column($workOrders->select('work_order_db')->findAll(), 'work_order_db');

        while (true) {
            $rows = $temp->where('file_id', $fileId)
                        ->where('error_json IS NULL')
                        ->limit(1000)
                        ->get()
                        ->getResultArray();

            if (!$rows) break;

            $valid  = [];
            $errors = [];
            $validRowIds   = [];
            $noErrorRows   = [];

            foreach ($rows as $row) {
                $e = [];

                // Basic required checks
                foreach (['order_number', 'material', 'material_description', 'plant', 'unit_of_measure', 'batch'] as $f) {
                    if (trim($row[$f]) === '') $e[$f] = 'Required';
                }

                // Number checks
                foreach (['order_quantity', 'delivered_quantity', 'confirmed_quantity'] as $f) {
                    if (!is_numeric($row[$f])) $e[$f] = 'Must be number';
                }

                // Date format checks
                foreach (['start_date', 'scheduled_finish_date'] as $d) {
                    if ($row[$d] && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $row[$d])) {
                        $e[$d] = 'Invalid date';
                    }
                }

                // Custom check: material must exist in product_master
                if (!in_array(trim($row['material']), $validMaterials)) {
                    $e['material'] = 'Material not found in product master';
                }

                // Custom check: batch is required
                $batch = trim($row['batch']);
                if ($batch === '') {
                    $e['batch'] = 'Batch is required';
                } else {
                    $checkStr = null;

                    if (str_starts_with($batch, 'DB')) {
                        $checkStr = substr($batch, 0, 6); // if starts with DB, take 6 chars
                    } else {
                        $checkStr = substr($batch, 0, 5); // otherwise, 5 chars
                    }

                    if (!in_array($checkStr, $validWorkOrders)) {
                        $e['batch'] = 'Work Order not matched with batch prefix';
                    }
                }

                if (!empty($e)) {
                    $errors[] = [
                        'id' => $row['id'],
                        'error_json' => json_encode($e),
                    ];
                } else {
                    $valid[] = [
                        'orderNumber'             => $row['order_number'],
                        'materialNumber'          => $row['material'],
                        'materialDescription'     => $row['material_description'],
                        'plant'                   => $row['plant'],
                        'orderQuantity_GMEIN'     => $row['order_quantity'],
                        'deliveredQuantity_GMEIN' => $row['delivered_quantity'],
                        'confirmedQuantity_GMEIN' => $row['confirmed_quantity'],
                        'unitOfMeasure_GMEIN'     => $row['unit_of_measure'],
                        'batch'                   => $row['batch'],
                        'systemStatus'            => $row['system_status'],
                        'startDate'               => $row['start_date'],
                        'scheduledFinishDate'     => $row['scheduled_finish_date'],
                        'salesOrder'              => $row['sales_order'],
                        'to_forge_qty'            => (int)$row['order_quantity'] - (int)$row['confirmed_quantity'],
                    ];
                    $noErrorRows[] = ['id' => $row['id'], 'error_json' => '0'];
                    array_push($validRowIds, $row['id']);
                }
            }

            if ($errors) $temp->updateBatch($errors, 'id');
            if ($valid)
            {
                echo 'Inserting ' . count($valid) . ' valid rows…';

                $temp->updateBatch($noErrorRows, 'id');
                $final->insertBatch($valid);
                print_r($validRowIds);
                $temp->whereIn('id', $validRowIds)
                    ->delete(); // remove from temp table
              
            }

            CLI::write("Processed " . count($rows));
        }

        $logModel->update($fileId, [
            'status' => 'completed',
            'processed_at' => date('Y-m-d H:i:s')
        ]);

        CLI::write("SAP Data import complete ✅");
    }
}
