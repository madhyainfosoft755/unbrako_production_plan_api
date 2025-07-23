<?php

use CodeIgniter\Router\RouteCollection;

use App\Controllers\Api\AuthController;
use App\Controllers\Api\CustomersController;
use App\Controllers\Api\FinishController; //
use App\Controllers\Api\GroupsController; //
use App\Controllers\Api\ModulesController; //
use App\Controllers\Api\PlantController;
use App\Controllers\Api\ProcessController;
use App\Controllers\Api\RolesController; 
use App\Controllers\Api\Seg2Controller; //
use App\Controllers\Api\Seg3Controller; //
use App\Controllers\Api\SegmentsController; //
use App\Controllers\Api\UnitOfMeasureController; //
use App\Controllers\Api\WorkOrderMasterController;
use App\Controllers\Api\MachineRevisionController;
use App\Controllers\Api\MachineController;
use App\Controllers\Api\MachineMasterController;
use App\Controllers\Api\SapDataController;
use App\Controllers\Api\WOMImportController;
use App\Controllers\Api\PMImportController;
use App\Controllers\Api\DailyModuleShiftOutputController;
use App\Controllers\Api\DailyModuleShiftQtyUpdateModelController;
use App\Controllers\Api\SurfaceTreatmentProcessController;
use App\Controllers\Api\MasterTemplatePasswordController;
use App\Controllers\Api\WeeklyPlanningController;
use App\Controllers\Api\SapRmUpdateController;
/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// service('auth')->routes($routes);

$routes->post("/api/register", [AuthController::class, "register"]);
$routes->post("/api/login", [AuthController::class, "login"]);
// $routes->get('clear-cache', 'ApiController::clearCache'); // Clear cache

$routes->post("/api/user/forgot-password", [AuthController::class, "forgotPassword"]);
$routes->post("/api/user/reset-password", [AuthController::class, "resetPassword"]);
$routes->post("/api/user/check-reset-token", [AuthController::class, "checkResetPasswordToken"]);

// Protected API Routes
$routes->group("api", ["namespace" => "App\Controllers\Api", "filter" => "shield_auth"], function($routes){

    $routes->get("profile", [AuthController::class, "profile"]);
    $routes->put('user/update', 'AuthController::updateUserDetails');
    $routes->get("users", [AuthController::class, "getAllUsers"]);
    $routes->post("change-password", [AuthController::class, "changePassword2"]);
    $routes->get("logout", [AuthController::class, "logout"]);

    $routes->post('get-sap-data', 'SapDataController::get_sap_data');
    $routes->post('m-w-m-w-m-report', 'SapDataController::get_sap_data2'); // module wise - machine wise - monthly report
    $routes->post('m-w-m-w-m-report2', 'SapDataController::get_sap_data3'); // module wise - machine wise - monthly report2
    $routes->post('sap-data/update/(:num)', 'SapDataController::updateRow/$1');
    $routes->post('update-to-forge/(:num)', 'SapDataController::updateToForge/$1');
    $routes->post('update-weekly-forge/(:num)', 'SapDataController::updateWeeklyForge/$1');
    $routes->post('update-forged-so-far/(:num)', 'SapDataController::updateForgedSoFar/$1');

    // sap data 
    $routes->get('get-sap-file-status', 'SapDataController::getSAPFileStatus');
    $routes->get('get-sap-failed-records', 'SapDataController::downloadSAPFailedRecords');
    $routes->get('validate-sap-file', 'SapDataController::triggerSAPFileValidation');
    $routes->get('download-sap-template', 'SapDataController::downloadSAPTemplate');
    $routes->get('load-sap-filters', 'SapDataController::loadFilterFields');
    $routes->post('get-weekly-planning', 'SapDataController::getWeeklyPlanning');
    $routes->post('update-buld-admin-fields', 'SapDataController::updateBulkAdminFields');

    // Surface Treatment
    $routes->post('surface-treatment-process', 'SurfaceTreatmentProcessController::addSTProcess');
    $routes->get('surface-treatment-process', 'SurfaceTreatmentProcessController::getAllSTProcess');

    // Module
    $routes->post('modules', 'ModulesController::addModule');
    $routes->get('modules', 'ModulesController::getAllModules');

    $routes->get('get-temp-module-shift-data', 'DailyModuleShiftOutputController::getTempSaveData');
    $routes->get('get-temp-module-shift-data2', 'DailyModuleShiftOutputController::getTempModuleShiftData');

    $routes->post('get-all-machines-with-part-numbers', 'MachineController::GetAllMachinesWithPartNumbers');

    $routes->post('save-daily-data', 'DailyModuleShiftOutputController::saveDailyData');
    $routes->get('submit-daily-data', 'DailyModuleShiftOutputController::saveSubmitData');

    $routes->post('get-sap-rm-data', 'SapRmUpdateController::index');
    $routes->post('update-sap-rm-data', 'SapRmUpdateController::create');
    $routes->post('add-part-number-sap/(:num)', 'SapDataController::addPartNumber/$1');
    $routes->get('load-sap-filter-suggestions', 'SapDataController::loadSapFilterSuggestions');

});



// Admin Routes
$routes->group("api", ["namespace" => "App\Controllers\Api", "filter" => ["shield_auth", "admin_access"]], function($routes) {
    $routes->post('transfer-and-upload', 'SapDataController::index');
    
    // roles
    $routes->get('roles', 'RolesController::getAllRoles');

    // segments
    $routes->post('segments', 'SegmentsController::addSegment');
    $routes->get('segments', 'SegmentsController::getAllSegments');

    // finish
    $routes->post('finish', 'FinishController::addFinish');
    $routes->get('finish', 'FinishController::getAllFinish');
    $routes->get('get-all-wo-db-and-finish', 'FinishController::getAllWODBandFinish');

    // Master Template Passwords
    $routes->get('get-master-template-passwords', 'MasterTemplatePasswordController::getTemplatePasswords');
    

    // groups
    $routes->post('groups', 'GroupsController::addGroup');
    $routes->get('groups', 'GroupsController::getAllGroups');

    // seg2
    $routes->post('seg2', 'Seg2Controller::addSeg2');
    $routes->get('seg2', 'Seg2Controller::getAllSeg2');

    // seg3
    $routes->post('seg3', 'Seg3Controller::addSeg3');
    $routes->get('seg3', 'Seg3Controller::getAllSeg3');

    // Unit of measure
    $routes->post('unit-of-measure', 'UnitOfMeasureController::addUnitOfMeasure');
    $routes->get('unit-of-measure', 'UnitOfMeasureController::getAllUnitOfMeasure');

    // Plant
    $routes->post('plant', 'PlantController::addPlant');
    $routes->get('plant', 'PlantController::getAllPlant');

    // Customers
    // $routes->post('customers', 'CustomersController::addCustomer');
    // $routes->get('customers', 'CustomersController::getAllCustomers');
    
    // Machine Revisions
    $routes->post('machine-revisions/(:num)', 'MachineRevisionController::addMachineRevision/$1');
    $routes->post('get-machine-for-modules', 'MachineRevisionController::getMachineForModules'); 
    
    // Surface Treatment
    $routes->post('surface-treatment-process', 'SurfaceTreatmentProcessController::addSTProcess');
    // Module
    $routes->post('modules', 'ModulesController::addModule');
    
    $routes->post('complete-weekly-report-for-module', 'WeeklyPlanningController::completeWeeklyReportForMoudle');
    $routes->post('update-weekly-report-fields', 'WeeklyPlanningController::updateWeeklyReportFields');


    // Machine
    $routes->post('machines', 'MachineController::addMachine');
    $routes->get('machines', 'MachineController::getAllMachines');
    $routes->get('all-machines', 'MachineController::allMachines');
    $routes->get('machines/(:num)', 'MachineController::getMachine/$1');
    $routes->put('machines/(:num)', 'MachineController::updateMachine/$1');
    

    // Machine Master CRUD routes
    $routes->post('add-machine-master', 'MachineMasterController::addMachineMaster');
    $routes->post('machine-master', 'MachineMasterController::getAllMachineMaster');
    $routes->put('machine-master/(:num)', 'MachineMasterController::updateMachineMaster/$1');
    $routes->get('get-machine-modules/(:num)', 'MachineMasterController::getMachineModules/$1'); 
    //  $routes->delete('machine-master/(:num)', 'MachineMasterController::deleteMachineMaster/$1');
    
    // Work Order Master
    $routes->post('add-work-order-master', 'WorkOrderMasterController::addWorkOrderMaster');
    $routes->post('work-order-master', 'WorkOrderMasterController::getAllData');
    $routes->get('customer-names/(:any)', 'WorkOrderMasterController::getCustomerNames/$1');
    $routes->patch('work-order-master/(:num)', 'WorkOrderMasterController::updateWorkOrderMaster/$1');
    $routes->get('download-wom-template', 'WorkOrderMasterController::downloadWOMTemplate');
    $routes->get('get-work-order-file-upload-status', 'WorkOrderMasterController::getWorkOrderFileUploadStatus');
    $routes->get('get-work-order-failed-records', 'WorkOrderMasterController::downloadWOMFailedRecords');
    $routes->get('validate-wom-file', 'WorkOrderMasterController::triggerWOMFileValidation');

    // Product Master CRUD routes
    $routes->post('add-product-master', 'ProductMasterController::create');
    $routes->post('product-master', 'ProductMasterController::getAllProductMaster');
    $routes->get('product-master/(:num)', 'ProductMasterController::getMachineMaster/$1');
    $routes->put('product-master/(:num)', 'ProductMasterController::update/$1');
    $routes->get('download-pm-template', 'ProductMasterController::downloadPMTemplate');
    $routes->get('get-product-master-file-upload-status', 'ProductMasterController::getProductMasterFileUploadStatus');
    $routes->get('get-product-master-failed-records', 'ProductMasterController::downloadPMFailedRecords');
    $routes->get('validate-pm-file', 'ProductMasterController::triggerPMFileValidation');
    $routes->get('check-part-number/(:any)', 'ProductMasterController::checkPartNumber/$1');

    $routes->post('import/product-master-file', 'PMImportController::upload');

    $routes->get('shifts', 'ShiftController::getAllShift');
    $routes->post('import/work-order-master-file', 'WOMImportController::upload');
    

});

// Forging Routes
$routes->group("api", ["namespace" => "App\Controllers\Api", "filter" => ["shield_auth", "forging_access"]], function($routes) {
});

// Open APIs
$routes->group("masters", ["namespace" => "App\Controllers\Api"], function($routes){
    $routes->get('part-number-info/(:any)', 'ProductMasterController::partNumberInfo/$1'); //material_no_info for machine name
    $routes->get('material-number-info/(:any)', 'ProductMasterController::material_no_info/$1'); //material_no_info
    $routes->get('machines-info', 'MachineRevisionController::machinesInfo');

});



// Create JSON Doc
$routes->get("swagger-json-doc", "DocController::convertAnnotationToJson");

