<?php

// Move the old index.php to preserve it
if (file_exists('index_old.php')) {
    // Don't overwrite if backup already exists
} else {
    if (file_exists('index.php')) {
        rename('index.php', 'index_old.php');
    }
}

// Autoload dependencies
require_once '../app/config/Database.php';
require_once '../app/config/Router.php';

// Initialize router
$router = new Router();

// Define routes
$router->get('/', 'HomeController@index');
$router->get('/home', 'HomeController@index');

// Consignment routes
$router->get('/consignments', 'ConsignmentController@index');
$router->get('/consignments/create', 'ConsignmentController@create');
$router->post('/consignments/create', 'ConsignmentController@create');
$router->get('/consignments/{id}', 'ConsignmentController@show');
$router->get('/consignments/{id}/edit', 'ConsignmentController@edit');
$router->post('/consignments/{id}/edit', 'ConsignmentController@edit');
$router->get('/consignments/bulk', 'ConsignmentController@bulk');
$router->get('/consignments/export', 'ConsignmentController@export');

// Company routes
$router->get('/companies', 'CompanyController@index');
$router->get('/companies/create', 'CompanyController@create');
$router->post('/companies/create', 'CompanyController@create');
$router->post('/companies/store', 'CompanyController@store');
$router->get('/companies/{id}', 'CompanyController@show');
$router->get('/companies/{id}/edit', 'CompanyController@edit');
$router->post('/companies/{id}/edit', 'CompanyController@edit');
$router->post('/companies/{id}/delete', 'CompanyController@delete');

// Bill routes
$router->get('/bills', 'BillController@index');
$router->get('/bills/create', 'BillController@create');
$router->post('/bills/create', 'BillController@create');
$router->get('/bills/{id}', 'BillController@show');
$router->post('/bills/{id}/payment', 'BillController@updatePayment');

// Reports routes
$router->get('/reports', 'ReportsController@index');

// Maintenance routes  
$router->get('/maintenance', 'MaintenanceController@index');
$router->post('/maintenance', 'MaintenanceController@store');

// Legacy compatibility routes for AJAX endpoints
$router->post('/company_save.php', 'CompanyController@store');
$router->post('/update_bill_payment.php', 'BillController@updatePayment');

// Handle the request
try {
    $router->dispatch();
} catch (Exception $e) {
    // Log error in production
    error_log("Router error: " . $e->getMessage());
    
    // Show generic error page
    http_response_code(500);
    echo "<h1>500 - Internal Server Error</h1>";
    echo "<p>Something went wrong. Please try again later.</p>";
    
    // In development, you might want to show the actual error
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
    }
}