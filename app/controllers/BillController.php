<?php

/**
 * BillController
 * Handles bill management operations
 */
class BillController extends Controller {
    
    /**
     * List all bills
     */
    public function index() {
        $billModel = $this->model('Bill');
        
        $statusFilter = $this->get('status', 'UNPAID');
        $search = trim($this->get('q', ''));
        $sort = $this->get('sort', 'date_desc');
        $page = max(1, (int)$this->get('page', 1));
        $pageSize = 20;
        
        $filters = [
            'status' => $statusFilter,
            'search' => $search,
            'sort' => $sort,
            'page' => $page,
            'pageSize' => $pageSize
        ];
        
        $bills = $billModel->getAll($filters);
        $totalRows = $billModel->getCount($filters);
        $stats = $billModel->getStatistics();
        
        $totalPages = max(1, (int)ceil($totalRows / $pageSize));
        
        $this->view('bill/index', [
            'title' => 'Manage Bills',
            'current' => '/bill',
            'bills' => $bills,
            'stats' => $stats,
            'status_filter' => $statusFilter,
            'search' => $search,
            'sort' => $sort,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_rows' => $totalRows
        ]);
    }
    
    /**
     * Update bill payment status (AJAX endpoint)
     */
    public function updatePayment() {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Invalid request'], 400);
            return;
        }
        
        try {
            $billModel = $this->model('Bill');
            
            $billId = (int)$this->post('bill_id', 0);
            $action = $this->post('action', '');
            $note = trim($this->post('note', ''));
            $date = trim($this->post('payment_date', ''));
            
            if ($billId <= 0) {
                throw new Exception("Invalid bill_id.");
            }
            
            if (!in_array($action, ['PAID', 'UNPAID'], true)) {
                throw new Exception("Invalid action.");
            }
            
            // Validate date if provided
            $paymentDate = null;
            if ($date !== '') {
                $d = DateTime::createFromFormat('Y-m-d', $date);
                if (!$d) {
                    throw new Exception("Invalid payment_date format; use YYYY-MM-DD.");
                }
                $paymentDate = $d->format('Y-m-d');
            }
            
            // Check if bill exists
            $bill = $billModel->getById($billId);
            if (!$bill) {
                throw new Exception("Bill not found.");
            }
            
            $billModel->updatePaymentStatus($billId, $action, $paymentDate, $note);
            
            $this->json([
                'ok' => true,
                'bill_id' => $billId,
                'payment_status' => $action,
                'payment_date' => $paymentDate
            ]);
        } catch (Exception $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
