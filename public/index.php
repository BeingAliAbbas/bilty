<?php

/**
 * Application Entry Point
 * All requests are routed through this file
 */

// Start session
session_start();

// Define paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Load core classes
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Model.php';
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Router.php';

// Create router instance
$router = new Router();

// Define routes

// Home
$router->get('/', 'HomeController@index');
$router->post('/', 'HomeController@index');

// Bilty routes
$router->get('/bilty', 'BiltyController@index');
$router->get('/bilty/add', 'BiltyController@add');
$router->post('/bilty/add', 'BiltyController@add');
$router->get('/bilty/details/:id', 'BiltyController@details');
$router->get('/bilty/print/:id', 'BiltyController@print');
$router->get('/bilty/bulk-print', 'BiltyController@bulkPrint');
$router->post('/bilty/update-payment', 'BiltyController@updatePayment');

// Company routes
$router->post('/company/save', 'CompanyController@save');

// Bill routes
$router->get('/bill', 'BillController@index');
$router->post('/bill/update-payment', 'BillController@updatePayment');

// Maintenance routes
$router->get('/maintenance', 'MaintenanceController@index');
$router->post('/maintenance/save', 'MaintenanceController@save');

// Report routes
$router->get('/report', 'ReportController@index');

// 404 handler
$router->setNotFound(function() {
    http_response_code(404);
    echo '<h1>404 - Page Not Found</h1>';
    echo '<p>The page you are looking for does not exist.</p>';
    echo '<a href="/">Go to Home</a>';
});

// Dispatch the request
try {
    $router->dispatch();
} catch (Exception $e) {
    http_response_code(500);
    echo '<h1>500 - Internal Server Error</h1>';
    echo '<p>An error occurred while processing your request.</p>';
    if (ini_get('display_errors')) {
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
}
