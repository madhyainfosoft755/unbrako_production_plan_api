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
use CodeIgniter\Controller\AuthController;
use CodeIgniter\Files\File;
use Exception;

class SapDataController extends ResourceController
{
     


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
    foreach ($sheetData as $row) {
        $insertData[] = [
            'orderNumber'             => $row['A'] ?? null,
            'plant'                   => $row['B'] ?? null,
            'materialNumber'          => $row['C'] ?? null,
            'materialDescription'     => $row['D'] ?? null,
            'orderQuantity_GMEIN'     => $row['E'] ?? null,
            'deliveredQuantity_GMEIN' => $row['F'] ?? null,
            'confirmedQuantity_GMEIN' => $row['G'] ?? null,
            'unitOfMeasure_GMEIN'     => $row['H'] ?? null,
            'batch'                   => $row['I'] ?? null,
            'sequenceNumber'          => $row['J'] ?? null,
            'createdOn'               => $row['K'] ?? null,
            'orderType'               => $row['L'] ?? null,
            'systemStatus'            => $row['M'] ?? null,
            'enteredBy'               => $row['N'] ?? null,
            'postingDate'             => $row['O'] ?? null,
            'statusProfile'           => $row['P'] ?? null,
            'insertedTimestamp'       => date('Y-m-d H:i:s'),
            'insertedBy'              => 'system', // Replace with actual user if available
        ];
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

            $defaultUserId = 1; // Replace with a valid ID from the `users` table
            foreach ($existingData as &$row) {
                $row['insertedBy'] = $row['insertedBy'] ?: $defaultUserId; // Use default user ID if invalid
            }

            // Insert into sap_data_history
            if (!$sapDataHistoryModel->insertBatch($existingData)) {
                log_message('error', 'Error inserting into SAP_DATA_HISTORY: ' . json_encode($db->error()));
                return $this->fail("Error transferring data to history table: " . json_encode($db->error()), 500);
            }
}

/**
 * Clear all data from sap_data table
 */
private function clearSapData()
{
    $sapDataModel = new \App\Models\SapDataModel();
     $sapDataModel->truncate();
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




   
      
    








}

?>