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
use Exception;


class SapDataController extends ResourceController
{
    protected $sapDataModel;
    protected $userModel;
    protected $WeeklyPeriodsModel;

    public function __construct()
    {
        // Load models in the constructor
        $this->sapDataModel = new SapDataModel();
        $this->userModel = new CustomUserModel();
        $this->weeklyPeriodsModel = new WeeklyPeriodsModel();
        $this->current_date = date('Y-m-d');
    }
     


public function index()
{
    $excelUploadFile = $this->request->getFile("upload_excel");
    $uploadExcelURL = "";

    if ($excelUploadFile && $excelUploadFile->isValid()) {
        $newExcelUploadName = $excelUploadFile->getRandomName();
        $excelUploadFile->move(FCPATH . "uploads", $newExcelUploadName);
        $uploadExcelURL = FCPATH . "uploads/" . $newExcelUploadName;
    } else {
        return $this->fail("Failed to upload the file.", 400);
    }

    try {
        // Load the uploaded Excel file
          $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadExcelURL);
          $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        // // Remove the header row
         unset($sheetData[1]);

        // Prepare data for insertion
        $insertData = $this->prepareInsertData($sheetData);
        // print_r($insertData); die;
        // Perform database operations
        $db = \Config\Database::connect();
        // $db->transStart();

        $this->transferSapData(); // Transfer data to history
         $this->clearSapData();    // Clear sap_data table
        $this->insertSapData($insertData); // Insert new data into sap_data

        // $db->transComplete();

        return $this->respond([
            "status" => true,
            "message" => "Data processed and added successfully."
        ], 201);
    } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
        return $this->fail("Error processing the Excel file: " . $e->getMessage(), 500);
    } catch (\Exception $e) {
        return $this->fail("An error occurred: " . $e->getMessage(), 500);
    }
}

/**
 * Prepare data for insertion from the Excel sheet
 */
private function prepareInsertData($sheetData)
{
    $insertData = [];
    // print_r($sheetData); die();
    foreach ($sheetData as $row) {
        // print_r($row);
        $insertData[] = [
            'orderNumber'             => $row['A'] ?? null,
            'plant'                   => $row['B'] ?? null,
            'sequenceNumber'          => $row['C'] ?? null,
            'materialNumber'          => $row['D'] ?? null,
            'materialDescription'     => $row['E'] ?? null,
            'orderQuantity_GMEIN'     => $row['F'] ? str_replace(',', '', $row['F']) : null,
            'deliveredQuantity_GMEIN' => $row['G'] ? str_replace(',', '', $row['G']) : null,
            'confirmedQuantity_GMEIN' => $row['H'] ? str_replace(',', '', $row['H']) : null,
            'unitOfMeasure_GMEIN'     => $row['I'] ?? null,
            'batch'                   => $row['J'] ?? null,
            'createdOn'               => $row['K'] ?? null,
            'orderType'               => $row['L'] ?? null,
            'systemStatus'            => $row['M'] ?? null,
            'enteredBy'               => $row['N'] ?? null,
            'postingDate'             => $row['O'] ?? null,
            'statusProfile'           => $row['P'] ?? null,

            'forge_commite_week'           => $row['Q'] ?? null,
            'this_month_forge_qty'           => $row['R'] ?? null,
            'statusProfile'           => $row['S'] ?? null,
            'plan_no_of_mc'           => $row['T'] ?? null,
            'special_remarks'           => $row['U'] ?? null,
            'advance_final_rm_wt'           => $row['V'] ?? null,
            'rm_allocation_priority'           => $row['W'] ?? null,


            'insertedTimestamp'       => date('Y-m-d H:i:s'),
            'insertedBy'              => 1, // Replace with actual user if available
        ];
        // print_r($insertData); die();
    }
    return $insertData;
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
        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');
        $month_full_year = $this->request->getGet('month_full_year');
        $weekNumber = $this->request->getGet('week_number');
        $weekStart = $this->request->getGet('week_start');
        $weekEnd = $this->request->getGet('week_end');
        // Query parmas end

        // echo $startDate ?? "Not getting startDate";
        // echo $endDate ?? "Not getting endDate";
        // echo $month_full_year ?? "Not getting month_full_year";
        // echo $weekNumber ?? "Not getting weekNumber";
        // echo $weekStart ?? "Not getting weekStart";
        // echo $weekEnd ?? "Not getting weekEnd";
        // die();

        $filters = [
            // 'wom.customer' => 'Unbrako USA LLC', // Exact match
            // 'pm.finish' => '2',
            // 'sap_segment.name' => ['SPM', 'GPM'], // WHERE IN condition
        ];

        if($startDate and $endDate){
            $filters["wom.delivery_date" . " >="] = $startDate;
            $filters["wom.delivery_date" . " <="] = $endDate;
        } else if ($weekStart and $weekEnd){
            $filters["wom.delivery_date" . " >="] = $weekStart;
            $filters["wom.delivery_date" . " <="] = $weekEnd;
        } else if ($month_full_year){
            $month_year = explode('/', $month_full_year);

            $filters["YEAR(wom.delivery_date)"] = $month_year[1];
            $filters["MONTH(wom.delivery_date)"] = $month_year[0];
        }
        // if($user->role != 1){
        //     $filters['modules.responsible'] = auth()->user()->id;
        // }
        $orderBy = [
            'wom.delivery_date' => 'DESC',
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
        //     mr.name as machine_1_name,
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
        //     LEFT JOIN machine_revisions as mr
        //         ON mr.id = pm.machine
        //     LEFT JOIN machines mc
        //         ON mc.id = mr.machine
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
            'forge_commite_week' => 'required|numeric',
            'this_month_forge_qty' => 'required|numeric',
            'plan_no_of_mc' => 'required|numeric',
            'special_remarks' => 'permit_empty|string',
            'rm_delivery_date' => 'required|valid_date',
            'advance_final_rm_wt' => 'required|numeric',
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


}

?>