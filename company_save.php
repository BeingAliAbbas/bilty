<?php
// Legacy compatibility for company_save.php AJAX endpoint

require_once 'app/config/Database.php';
require_once 'app/models/Model.php';
require_once 'app/models/Company.php';

header('Content-Type: application/json');

try {
    $companyModel = new Company();
    
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        throw new Exception("Company name is required.");
    }

    $id = $companyModel->createCompany($name, $address);

    echo json_encode([
        'ok' => true,
        'company' => [
            'id' => $id,
            'name' => $name,
            'address' => $address
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}