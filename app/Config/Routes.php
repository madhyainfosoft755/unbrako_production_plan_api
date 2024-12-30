<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Api\AuthController;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// service('auth')->routes($routes);

$routes->post("/api/register", [AuthController::class, "register"]);
$routes->post("/api/login", [AuthController::class, "login"]);
$routes->post('api/transfer_and_upload', 'Api\SapDataController::index');


// Protected API Routes
$routes->group("api", ["namespace" => "App\Controllers\Api", "filter" => "shield_auth"], function($routes){

    $routes->get("profile", [AuthController::class, "profile"]);
    $routes->get("logout", [AuthController::class, "logout"]);

});




// Create JSON Doc
$routes->get("swagger-json-doc", "DocController::convertAnnotationToJson");

