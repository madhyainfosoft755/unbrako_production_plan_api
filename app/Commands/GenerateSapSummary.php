<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

use CodeIgniter\Database\BaseConnection;

class GenerateSapSummary extends BaseCommand
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
    protected $name = 'sap:generate-summary';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Generate sap_calculated_summary for missing sap_data records';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'sap:generate-summary';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        helper('date');
        $db = db_connect();
        $builder = $db->table('sap_data');

        $missingRows = $builder
            ->select('id')
            ->where('id NOT IN (SELECT sap_id FROM sap_calculated_summary)', null, false)
            ->get()
            ->getResultArray();

        if (empty($missingRows)) {
            CLI::write('No missing sap_data found for processing.', 'yellow');
            return;
        }

        foreach ($missingRows as $row) {
            $sapId = $row['id'];

            try {
                CLI::write("Processing SAP ID: {$sapId}...", 'blue');

                // Ideally, call a dedicated Service class here, but for now you can:
                $this->processSapData($sapId, $db);

                CLI::write("Processed SAP ID: {$sapId}", 'green');
            } catch (\Throwable $e) {
                CLI::error("Error processing SAP ID: {$sapId} — " . $e->getMessage());
            }
        }

        CLI::write('✅ All missing summaries processed.', 'green');
    }

    /**
     * This function replicates the logic you wrote in the trigger.
     * Ideally, break it into reusable services or models.
     */
    // protected function processSapData(int $sapId, BaseConnection $db)
    // {
    //     // You can implement the JOIN + calculation logic here
    //     // For now, let's simulate by inserting a row with dummy data

    //     $sap = $db->table('sap_data')->where('id', $sapId)->get()->getRowArray();
    //     if (!$sap) throw new \Exception("SAP ID $sapId not found.");

    //     // Fetch all joined data like: product_master, machine, etc.
    //     // Do calculations here (use same logic as trigger)…

    //     // Insert into sap_calculated_summary
    //     $db->table('sap_calculated_summary')->insert([
    //         'sap_id' => $sap['id'],
    //         'sap_orderNumber' => $sap['orderNumber'],
    //         'materialNumber' => $sap['materialNumber'],
    //         'created_at' => date('Y-m-d H:i:s'),
    //         // Add all other calculated fields
    //     ]);
    // }
    protected function processSapData(int $sapId, BaseConnection $db)
    {
        // Fetch the SAP record
        $sap = $db->table('sap_data')->where('id', $sapId)->get()->getRowArray();
        if (!$sap) return;

        // Derive work_order
        $batch = strtoupper(substr($sap['batch'], 0, 2)) === 'DB'
            ? substr($sap['batch'], 0, 6)
            : substr($sap['batch'], 0, 5);

        // Left join fetch
        $row = $db->table('product_master pm')
            ->select([
                'pm.finish_wt',      'pm.machine_module',
                'COALESCE(mc.per_of_efficiency,60) AS per_eff',
                'mr.id AS machine_revision_id', 'mc.id AS machine_id',
                'mr.name AS machine1', 'mc.name AS machine2',
                'COALESCE(mc.speed,50) AS speed',
                'COALESCE(mc.no_of_mc,1) AS machines',
                'COALESCE(mc.no_of_shift,1) AS shifts',
                'COALESCE(mc.plan_no_of_mc,1) AS plan_mc',
                'wom.reciving_date','wom.delivery_date','wom.wo_add_date',
                'wom.work_order_db','wom.customer','wom.responsible_person_name',
                'wom.marketing_person_name','wom.segment as wom_segment','wom.plant as wom_plant',
                'segments.name AS wom_seg_name','wom.quality_inspection_required',
                'modules.name AS module_name', 'modules.responsible AS module_responsible_person_id',
                'module_res.name AS module_responsible_person_name',
                'pm.seg2 as pm_seg2','seg2.name AS seg2_name',
                'pm.seg3 as pm_seg3','seg3.name AS seg3_name',
                'pm.finish AS finish_id','finish.name AS finish_name',
                'pm.prod_group AS grp_id','groups.name AS grp_name',
                'pm.cheese_wt','pm.size','pm.length','pm.spec',
                'pm.rod_dia1','pm.drawn_dia1','pm.condition_of_rm',
                'pm.special_remarks','pm.bom','pm.rm_component'
            ])
            ->join('machine_revisions mr','mr.id=pm.machine','left')  //
            ->join('machines mc','mc.id=mr.machine','left')
            ->join('modules','modules.id=pm.machine_module','left')
            ->join('work_order_master wom',"wom.work_order_db = '{$batch}'",'left')   //
            ->join('segments','segments.id=wom.segment','left')
            ->join('finish','finish.id=pm.finish','left')
            ->join('groups','groups.id=pm.prod_group','left')
            ->join('seg_2 seg2','seg2.id=pm.seg2','left')
            ->join('seg_3 seg3','seg3.id=pm.seg3','left')
            ->join('users module_res','module_res.id=modules.responsible','left')   //
            ->where('pm.material_number_for_process', $sap['materialNumber'])   //
            ->get()
            ->getRowArray();

        // Fallback if not found
        $row = $row ?? [
            'finish_wt' => 0, 'machine_module'=>null, 'per_eff'=>60, 'speed'=>50,
            'machines'=>1,'shifts'=>1,'plan_mc'=>1, 'machine_revision_id'=>null, 'machine_id'=>null,
            'customer'=>null,'quality_inspection_required'=>0, 'wom_plant'=>null, 'wom_segment'=>null, 'wom_seg_name'=>null,
            'pm_seg2'=>null,'seg2_name'=>null,'pm_seg3'=>null,
            'module_name'=>null, 'module_responsible_person_id'=>null, 'module_responsible_person_name'=>null, 'machine1'=>null,'machine2'=>null,
            'finish_id'=>null,'finish_name'=>null,'sep2_name'=>null,'seg3_name'=>null,
            'grp_id'=>null,'grp_name'=>null,'cheese_wt'=>0,
            'size'=>null,'length'=>null,'spec'=>null,'rod_dia1'=>null,'drawn_dia1'=>null,
            'condition_of_rm'=>null,'special_remarks'=>null,'bom'=>null,'rm_component'=>null,
            'reciving_date'=>null,'delivery_date'=>null,'wo_add_date'=>null,
            'work_order_db'=>null,'responsible_person_name'=>null,'marketing_person_name'=>null
        ];

        // Assign variables
        extract($row);

        $forged = intval($sap['forged_so_far'] ?? 0);
        $total_alloc = floatval($sap['rm_correction'] ?? 0) + floatval($sap['plan_allocation'] ?? 0);
        $mult = floatval($db->query("SELECT getModuleMultiplier(?) AS m", [$machine_module])->getRow()->m ?? 1.2);

        // Calculations
        $to_forge_qty = intval($sap['to_forge_qty']) - intval($sap['to_forge_limit_inc']);
        $to_forge_wt = ($to_forge_qty * $finish_wt)/1000;
        $to_forge_rm_wt = $to_forge_wt * $mult;
        $total_alloc2 = min($total_alloc, $to_forge_rm_wt);
        $plan_print_qty = $mult && $finish_wt ? ($total_alloc2 * 1000 / $mult / $finish_wt) : 0;
        $this_month_forge_wt = ($forged * $finish_wt)/1000;
        $this_month_forge_rm_wt = $this_month_forge_wt * $mult;
        $act_balance_rm_wt = max(0, $total_alloc - $this_month_forge_rm_wt);
        $allocated_balance_rm_wt = $act_balance_rm_wt;
        $allocated_product_wt = $allocated_balance_rm_wt/$mult;
        $allocated_product_qty = $finish_wt ? ($allocated_product_wt*1000)/$finish_wt : 0;
        $per_day_booking = ($speed * 450) * ($per_eff/100) * $shifts * $plan_mc;
        $final_pending_qty = $to_forge_qty - $forged;
        $pending_qty = max(0, $final_pending_qty);
        $pending_wt = ($pending_qty * $finish_wt)/1000;
        $pending_rm_wt = $pending_wt * $mult;
        $pending_from_outside_1 = $to_forge_rm_wt - $total_alloc;
        $pending_from_outside = max(0, $pending_from_outside_1);
        $no_days_booking = $per_day_booking ? $final_pending_qty / $per_day_booking : 0;
        $weekly_planning_days = $per_day_booking ? $allocated_product_qty/$per_day_booking : 0;

        // Insert record
        $db->table('sap_calculated_summary')->insert([
            'sap_id'                        => $sapId,
            'sap_orderNumber'               => $sap['orderNumber'],
            'rm_correction'                 => $sap['rm_correction'],
            'plan_allocation'               => $sap['plan_allocation'],
            'materialNumber'                => $sap['materialNumber'],
            'materialDescription'           => $sap['materialDescription'],
            'sap_plant'                     => $sap['plant'],
            'systemStatus'                  => $sap['systemStatus'],
            'orderQuantity_GMEIN'           => $sap['orderQuantity_GMEIN'],
            'deliveredQuantity_GMEIN'       => $sap['deliveredQuantity_GMEIN'],
            'confirmedQuantity_GMEIN'       => $sap['confirmedQuantity_GMEIN'],
            'weekly_plan'                 => $sap['forge_commite_week'],
            'monthly_plan'                => $sap['monthly_plan'],
            'monthly_fix_plan'                => $sap['monthly_fix_plan'],
            'wom_plant'                     => $wom_plant,
            'pm_order_number'               => $sap['orderNumber'],
            'unitOfMeasure_GMEIN'           => $sap['unitOfMeasure_GMEIN'],
            'batch'                         => $sap['batch'],
            'work_order'                    => $batch,
            'reciving_date'                 => $reciving_date,
            'delivery_date'                 => $delivery_date,
            'wo_add_date'                   => $wo_add_date,
            'work_order_db'                 => $work_order_db,
            'customer'                      => $customer,
            'responsible_person_name'       => $responsible_person_name,
            'marketing_person_name'         => $marketing_person_name,
            'wom_segment_id'                => $wom_segment,
            'wom_segment_name'              => $wom_seg_name,
            'seg2_id'                       => $pm_seg2,
            'seg2_name'                     => $seg2_name,
            'seg3_id'                       => $pm_seg3,
            'seg3_name'                     => $seg3_name,
            'finish_id'                     => $finish_id,
            'finish_name'                   => $finish_name,
            'group_id'                      => $grp_id,
            'group_name'                    => $grp_name,
            'machine_id'                    => $machine_id,
            'machine_revision_id'           => $machine_revision_id,
            'machine_name'                  => $machine2,
            'machine_1_name'                => $machine1,
            'no_of_machines'                => $machines,
            'quality_inspection_required'   => $quality_inspection_required ?? 0,
            'cheese_wt'                     => $cheese_wt,
            'size'                          => $size,
            'length'                        => $length,
            'spec'                          => $spec,
            'rod_dia1'                      => $rod_dia1,
            'drawn_dia1'                    => $drawn_dia1,
            'condition_of_rm'               => $condition_of_rm,
            'pm_special_remarks'            => $special_remarks,
            'main_special_remarks'          => $sap['special_remarks'],
            'pm_bom'                        => $bom,
            'rm_component'                  => $rm_component,
            'rm_delivery_date'              => $sap['rm_delivery_date'],
            'rm_allocation_priority'        => $sap['rm_allocation_priority'],
            'finish_wt'                     => $finish_wt,
            'to_forge_qty'                  => $to_forge_qty,
            'to_forge_wt'                   => $to_forge_wt,
            'forged_so_far'                 => $forged,
            'this_month_forge_wt'          => $this_month_forge_wt,
            'module_id'                     => $machine_module,
            'module_name'                   => $module_name,
            'module_responsible_person_id'  => $module_responsible_person_id,
            'module_responsible_person_name'=> $module_responsible_person_name,
            'module_multiplier'            => $mult,
            'to_forge_rm_wt'                => $to_forge_rm_wt,
            'total_allocation'             => $total_alloc,
            'total_allocation_2'           => $total_alloc2,
            'plan_print_qty'               => $plan_print_qty,
            'this_month_forge_rm_wt'       => $this_month_forge_rm_wt,
            'act_allocated_balance_rm_wt'  => $act_balance_rm_wt,
            'allocated_balance_rm_wt'       => $allocated_balance_rm_wt,
            'allocated_product_wt'         => $allocated_product_wt,
            'allocated_product_qty'        => $allocated_product_qty,
            'per_of_efficiency'            => $per_eff,
            'machine_speed'                => $speed,
            'no_of_shift'                  => $shifts,
            'plan_no_of_machine'           => $plan_mc,
            'per_day_booking'              => $per_day_booking,
            'final_pending_qty'            => $final_pending_qty,
            'pending_qty'                  => $pending_qty,
            'pending_wt'                   => $pending_wt,
            'pending_rm_wt'                => $pending_rm_wt,
            'pending_from_outside_1'       => $pending_from_outside_1,
            'pending_from_outside'         => $pending_from_outside,
            'no_of_days_booking'           => $no_days_booking,
            'no_of_day_weekly_planning'    => $weekly_planning_days,
        ]);
    }
}
