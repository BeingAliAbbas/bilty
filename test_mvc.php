#!/usr/bin/env php
<?php

// Simple test to verify MVC structure
echo "Testing MVC Structure...\n";

// Test Database connection
try {
    require_once 'app/config/Database.php';
    $db = Database::getInstance();
    echo "✓ Database connection: OK\n";
} catch (Exception $e) {
    echo "✗ Database connection: " . $e->getMessage() . "\n";
}

// Test Router
try {
    require_once 'app/config/Router.php';
    $router = new Router();
    echo "✓ Router class: OK\n";
} catch (Exception $e) {
    echo "✗ Router class: " . $e->getMessage() . "\n";
}

// Test Models
try {
    require_once 'app/models/Model.php';
    require_once 'app/models/Company.php';
    require_once 'app/models/Consignment.php';
    require_once 'app/models/Bill.php';
    echo "✓ Model classes: OK\n";
} catch (Exception $e) {
    echo "✗ Model classes: " . $e->getMessage() . "\n";
}

// Test Controllers
try {
    require_once 'app/controllers/Controller.php';
    require_once 'app/controllers/HomeController.php';
    require_once 'app/controllers/CompanyController.php';
    require_once 'app/controllers/ConsignmentController.php';
    require_once 'app/controllers/BillController.php';
    echo "✓ Controller classes: OK\n";
} catch (Exception $e) {
    echo "✗ Controller classes: " . $e->getMessage() . "\n";
}

// Test View files exist
$viewFiles = [
    'app/views/layout/head.php',
    'app/views/layout/header.php',
    'app/views/home/index.php',
    'app/views/consignments/index.php',
    'app/views/consignments/create.php',
    'app/views/errors/404.php'
];

$missingViews = [];
foreach ($viewFiles as $file) {
    if (!file_exists($file)) {
        $missingViews[] = $file;
    }
}

if (empty($missingViews)) {
    echo "✓ View files: OK\n";
} else {
    echo "✗ Missing view files: " . implode(', ', $missingViews) . "\n";
}

echo "\nMVC Structure Test Complete!\n";