<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PMFileImportLogModel;
use App\Models\PMTempImportProductModel;
use App\Models\ProductMasterModel;

class ValidateProductMasterFileData extends BaseCommand
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
    protected $name = 'validate:productmastersfiledata';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Validate & import Product Master Excel rows';

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
        if (!$params) { CLI::error('File ID required'); return; }
        $fileId = (int) $params[0];

        $fileLog = (new PMFileImportLogModel())->find($fileId);
        if (!$fileLog) { CLI::error('file_id not found'); return; }

        (new PMFileImportLogModel())->update($fileId, ['status' => 'processing']);

        /* pre‑fetch reference tables once to avoid 50 k FK queries */
        $refs = $this->prefetchReferenceIds();

        $temp  = new PMTempImportProductModel();
        $final = new ProductMasterModel();

        $batch = 1000;
        while (true) {
            $chunk = $temp->where('file_id', $fileId)
                          ->where('error_json IS NULL')
                          ->limit($batch)
                          ->get()->getResultArray();

            if (!$chunk) break;

            $valid = [];
            $errs  = [];
            $validRowIds   = [];
            $noErrorRows   = [];

            foreach ($chunk as $row) {
                $e = $this->validateRow($row, $refs);
                print_r($row);
                echo 'error';
                print_r($e);
                print_r(count($e) > 0 ? 'has errors' : 'no errors');
                if ($e) {
                    $errs[] = ['id' => $row['id'], 'error_json' => json_encode($e)];
                } else {
                    $dataForMain = [
                        'order_no'          => $row['order_no'],
                        'material_number'   => $row['material_number'],
                        'material_number_froging' => $row['material_number_froging'],
                        'material_description'    => $row['material_description'],
                        'machine_id'        => $refs['machine_name'][$row['machine_name']],
                        'module_id'         => $refs['module'][$row['module']],
                        'uom'               => $row['uom'],
                        'seg2_id'           => $refs['seg2'][$row['seg2']],
                        'seg3_id'           => $refs['seg3'][$row['seg3']],
                        'product_size'      => $row['product_size'],
                        'group_id'          => $refs['product_group'][$row['product_group']],
                        'product_length'    => $row['product_length'],
                        'finish_id'         => $refs['finish'][$row['finish']],
                        'segment_id'        => $refs['segment'][$row['segment']],
                        'finish_wt'         => $row['finish_wt'],
                        'cheese_wt'         => $row['cheese_wt'],
                        'rm_spec'           => $row['rm_spec'],
                        'rod_dia1'          => $row['rod_dia1'],
                        'drawn_dia1'        => $row['drawn_dia1'],
                        'special_remarks'   => $row['special_remarks'],
                        'bom'               => $row['bom'],
                        'rm_component'      => $row['rm_component'],
                        'condition_raw_material' => $row['condition_raw_material'],
                        'created_by'        => $fileLog['uploaded_by'],
                    ];
                    $noErrorRows[] = ['id' => $row['id'], 'error_json' => '0'];
                    $valid[] = $dataForMain;
                    array_push($validRowIds, $row['id']);
                    print_r('dataForMain');
                    print_r($dataForMain);
                }
                print_r('validRows');
                print_r($valid);
            }

            if ($errs)  {
                $temp->updateBatch($errs, 'id');
            }
            if ($valid){ 
                 echo 'Inserting ' . count($valid) . ' valid rows…';
                $temp->updateBatch($noErrorRows, 'id');
                $final->insertBatch($valid);
                print_r($validRowIds);
                $temp->whereIn('id', $validRowIds)
                    ->delete();
            }

            CLI::write('Processed '.count($chunk).' rows…');
        }

        (new PMFileImportLogModel())->update($fileId, [
            'status'       => 'completed',
            'processed_at' => date('Y-m-d H:i:s')
        ]);
        CLI::write('Done');
    }

    /** ------------------------------------------------ helpers ---------- */
    protected function prefetchReferenceIds(): array
    {
        $map = fn(string $table) => array_column(
            db_connect()->table($table)->select('id,name')->get()->getResultArray(),
            'id',
            'name'
        );

        return [
            'machine_name' => $map('machines'),
            'module'  => $map('modules'),
            'seg2'    => $map('seg_2'),
            'seg3'    => $map('seg_3'),
            'product_group'   => $map('groups'),
            'finish'  => $map('finish'),
            'segment' => $map('segments'),
        ];
    }

    protected function validateRow(array $r, array $ref): array
    {
        $e = [];

        $required = [
            'order_no','material_number','material_description','machine_name',
            'module','uom','seg2','seg3','product_size','product_group',
            'product_length','finish','segment','finish_wt','cheese_wt'
        ];
        foreach ($required as $f) {
            if (trim($r[$f] ?? '') === '') $e[$f] = 'Required';
        }

        // FK checks
        foreach (['machine_name','module','seg2','seg3','product_group','finish','segment'] as $fk) {
            $val = $r[$fk];
            if ($val !== '' && !isset($ref[$fk][$val])) {
                $e[$fk] = 'Not found';
            }
        }

        // numeric checks
        foreach (['finish_wt','cheese_wt'] as $num) {
            if ($r[$num] === null || !is_numeric($r[$num])) {
                $e[$num] = 'Must be decimal';
            }
        }

        return $e;
    }
}
