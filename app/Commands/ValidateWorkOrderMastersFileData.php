<?php

namespace App\Commands;

use App\Models\WOMFileImportLogModel;
use App\Models\WOMTempImportWorkOrderModel;
use App\Models\WorkOrderMasterModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ValidateWorkOrderMastersFileData extends BaseCommand
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
    protected $name = 'validate:workordermastersfiledata';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Validate & import work‑order Excel rows. Usage: php spark validate:workorders {fileId}';

    /**
     * The Command's Usage
     *
     * @var string
     */
    // protected $usage = 'command:name [arguments] [options]';
    protected $usage = 'validate:workordermastersfiledata {fileId}';

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
        if (empty($params)) {
            CLI::error('File ID required'); return;
        }
        $fileId = (int)$params[0];
        echo $fileId;

        $fileLog = (new WOMFileImportLogModel())->find($fileId);
        if (!$fileLog) { CLI::error('File not found'); return; }

        (new WOMFileImportLogModel())->update($fileId, ['status' => 'processing']);

        $tempModel   = new WOMTempImportWorkOrderModel();
        $mainModel   = new WorkOrderMasterModel();
        $segmentIds  = $this->prefetchSegments(); // name=>id map

        // Load all valid product material numbers
        $validWorkOrderDb = array_column($mainModel->select('work_order_db')->findAll(), 'work_order_db');

        $batchSize = 1000;
        while (true) {
            $chunk = $tempModel
                ->where('file_id', $fileId)
                ->where('error_json IS NULL')
                ->limit($batchSize)
                ->get()
                ->getResultArray();
            // print_r($chunk);
            if (!$chunk) {
                break;
            }

            $validRows   = [];
            $validRowIds   = [];
            $errorRows   = [];
            $noErrorRows   = [];

            foreach ($chunk as $row) {
                $errors = $this->validateRow($row, $segmentIds, $validWorkOrderDb);
                // print_r($row);
                // echo 'error';
                // print_r($errors);
                // print_r(count($errors) > 0 ? 'has errors' : 'no errors');
                if (count($errors) > 0) {
                    $errorRows[] = ['id' => $row['id'], 'error_json' => json_encode($errors)];
                } else {
                    $dataForMain = [
                        'plant'                    => $row['plant'],
                        'work_order_db'            => $row['work_order_db'],
                        'customer'                 => $row['customer'],
                        'responsible_person_name'  => $row['responsible_person_name'],
                        'segment'                  => $segmentIds[$row['segment_name']], // FK id
                        'marketing_person_name'    => $row['marketing_person_name'],
                        'wo_add_date'              => $row['wo_add_date'],
                        'reciving_date'            => $row['reciving_date'],
                        'delivery_date'            => $row['delivery_date'],
                        'no_of_items'              => $row['no_of_items'],
                        'weight'                   => $row['weight'],
                        'created_by'               => $fileLog['uploaded_by'],
                    ];
                    $noErrorRows[] = ['id' => $row['id'], 'error_json' => '0'];
                    $validRows[] = $dataForMain;
                    array_push($validRowIds, $row['id']);
                    // print_r('dataForMain');
                    // print_r($dataForMain);
                }
                // print_r('validRows');
                // print_r($validRows);
                
            }

            // Bulk update errors
            if ($errorRows) {
                $tempModel->updateBatch($errorRows, 'id');
            }

            // Bulk insert valid
            if ($validRows) {
                echo 'Inserting ' . count($validRows) . ' valid rows…';

                $tempModel->updateBatch($noErrorRows, 'id');
                $result = $mainModel->insertBatch($validRows);
                if (!$result) {
                    echo "insertBatch failed:" . PHP_EOL;
                    print_r($mainModel->errors());     // Validation errors
                    print_r($mainModel->db->error());  // Database errors
                } else {
                    echo "Inserted successfully." . PHP_EOL;

                    $tempModel->whereIn('id', $validRowIds)
                        ->delete();
                }
              
            }

            CLI::write('Processed ' . count($chunk) . ' rows…');
        }

        (new WOMFileImportLogModel())->update($fileId, [
            'status'       => 'completed',
            'processed_at' => date('Y-m-d H:i:s')
        ]);

        CLI::write('Done ');
    }

    /**
     * -----------  helpers  -----------------------------------------------
     */
    protected function prefetchSegments(): array
    {
        // id,name columns required
        $segments = db_connect()->table('segments')
            ->select('id,name')
            ->get()->getResultArray();

        $map = [];
        foreach ($segments as $seg) {
            $map[$seg['name']] = $seg['id'];
        }
        return $map;
    }

    protected function validateRow(array $row, array $segmentIds, $validWorkOrderDb): array
    {
        $e = [];

        if (in_array(trim($row['work_order_db']), $validWorkOrderDb)) {
            $e['work_order_db'] = 'Work order code already found in work order master';
        }

        if ($row['plant'] === '')   $e['plant'] = 'Required';
        if ($row['work_order_db'] === '' || strlen($row['work_order_db']) > 5)
            $e['work_order_db'] = 'Required, <=5 chars';
        if ($row['customer'] === '') $e['customer'] = 'Required';

        if (!isset($segmentIds[$row['segment_name']])) {
            $e['segment'] = 'Segment not found';
        }
        if (!$row['wo_add_date'])   $e['wo_add_date'] = 'Invalid date';
        if (!$row['reciving_date']) $e['reciving_date'] = 'Invalid date';
        if (!$row['delivery_date']) $e['delivery_date'] = 'Invalid date';

        // if (!is_int($row['no_of_items']))      $e['no_of_items'] = 'Must be integer';
        // if (!is_numeric($row['weight']))       $e['weight'] = 'Must be decimal';
        // numeric + whole‑number check
        $items = trim($row['no_of_items'] ?? '');
        if ($items === '' || !ctype_digit($items)) {
            $e['no_of_items'] = 'Must be a whole number';
        }

        // decimal check
        if ($row['weight'] === null || !is_numeric($row['weight'])) {
            $e['weight'] = 'Must be decimal';
        }

        return $e;
    }
}
