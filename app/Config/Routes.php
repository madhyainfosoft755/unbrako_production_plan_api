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

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// service('auth')->routes($routes);

$routes->post("/api/register", [AuthController::class, "register"]);
$routes->post("/api/login", [AuthController::class, "login"]);

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

});


// Admin Routes
$routes->group("api", ["namespace" => "App\Controllers\Api", "filter" => ["shield_auth", "admin_access"]], function($routes) {

    // roles
    $routes->post('roles', 'RolesController::addRole');
    $routes->get('roles', 'RolesController::getAllRoles');

    // segments
    $routes->post('segments', 'SegmentsController::addSegment');
    $routes->get('segments', 'SegmentsController::getAllSegments');

    // finish
    $routes->post('finish', 'FinishController::addFinish');
    $routes->get('finish', 'FinishController::getAllFinish');

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

    // Module
    $routes->post('modules', 'ModulesController::addModule');
    $routes->get('modules', 'ModulesController::getAllModules');

    // Customers
    // $routes->post('customers', 'CustomersController::addCustomer');
    // $routes->get('customers', 'CustomersController::getAllCustomers');

    // Machine Revisions
    $routes->post('machine-revisions/(:num)', 'MachineRevisionController::addMachineRevision/$1');
    $routes->get('machine-revisions/(:num)', 'MachineRevisionController::getMachineRevisions/$1');


    // Machine
    $routes->post('machines', 'MachineController::addMachine');
    $routes->get('machines', 'MachineController::getAllMachines');
    $routes->get('machines/(:num)', 'MachineController::getMachine/$1');
    $routes->put('machines/(:num)', 'MachineController::updateMachine/$1');

    // Machine Master CRUD routes
    $routes->post('machine-master', 'MachineMasterController::addMachineMaster');
    $routes->get('machine-master', 'MachineMasterController::getAllMachineMaster');
    $routes->get('machine-master/(:num)', 'MachineMasterController::getMachineMaster/$1');
    $routes->put('machine-master/(:num)', 'MachineMasterController::updateMachineMaster/$1');
    $routes->get('get-machine-modules/(:num)', 'MachineMasterController::getMachineModules/$1');
    //  $routes->delete('machine-master/(:num)', 'MachineMasterController::deleteMachineMaster/$1');

    // Work Order Master
    $routes->post('add-work-order-master', 'WorkOrderMasterController::addWorkOrderMaster');
    $routes->get('work-order-master', 'WorkOrderMasterController::getAllData');
    $routes->get('customer-names/(:any)', 'WorkOrderMasterController::getCustomerNames/$1');
    $routes->patch('work-order-master/(:num)', 'WorkOrderMasterController::updateWorkOrderMaster/$1');

    // Product Master CRUD routes
    $routes->post('product-master', 'ProductMasterController::create');
    $routes->get('product-master', 'ProductMasterController::getAllProductMaster');
    $routes->get('product-master/(:num)', 'ProductMasterController::getMachineMaster/$1');
    $routes->put('product-master/(:num)', 'ProductMasterController::update/$1');

    $routes->get('shifts', 'ShiftController::getAllShift');

});

// Open APIs
$routes->group("masters", ["namespace" => "App\Controllers\Api"], function($routes){
    $routes->get('part-number-info/(:any)', 'ProductMasterController::partNumberInfo/$1'); //material_no_info for machine name
    $routes->get('material-number-info/(:any)', 'ProductMasterController::material_no_info/$1'); //material_no_info
    $routes->get('machines-info', 'MachineRevisionController::machinesInfo');

});


// Create JSON Doc
$routes->get("swagger-json-doc", "DocController::convertAnnotationToJson");
