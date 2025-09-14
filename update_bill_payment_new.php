<?php
// Legacy compatibility for update_bill_payment.php AJAX endpoint

require_once 'app/config/Database.php';
require_once 'app/models/Model.php';
require_once 'app/models/Bill.php';

header('Content-Type: application/json');

try {
    $billModel = new Bill();
    
    $billId = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
    $action = $_POST['action'] ?? '';
    $note   = trim($_POST['note'] ?? '');
    $date   = trim($_POST['payment_date'] ?? '');

    if ($billId <= 0) throw new Exception("Invalid bill_id.");
    if (!in_array($action, ['PAID','UNPAID'], true)) {
        throw new Exception("Invalid action.");
    }

    // Validate date if provided
    $paymentDate = null;
    if ($date !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d) throw new Exception("Invalid payment_date format; use YYYY-MM-DD.");
        $paymentDate = $d->format('Y-m-d');
    }

    // Ensure bill exists
    $bill = $billModel->find($billId);
    if (!$bill) {
        throw new Exception("Bill not found.");
    }

    // Update payment status
    $updated = $billModel->updatePaymentStatus($billId, $action, $paymentDate, $note);
    
    if (!$updated) {
        throw new Exception("Failed to update payment status.");
    }

    // Get updated bill data
    $updatedBill = $billModel->find($billId);

    echo json_encode([
        'ok' => true,
        'bill_id' => $billId,
        'payment_status' => $updatedBill['payment_status'],
        'payment_date' => $updatedBill['payment_date']
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}