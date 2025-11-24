<?php

/**
 * MaintenanceController
 * Handles vehicle maintenance operations
 */
class MaintenanceController extends Controller {
    
    /**
     * List all maintenance records
     */
    public function index() {
        $maintenanceModel = $this->model('VehicleMaintenance');
        
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        
        $startDate = $this->get('start', $monthStart);
        $endDate = $this->get('end', $today);
        $vehicle = trim($this->get('vehicle', ''));
        $expense = trim($this->get('expense', ''));
        $page = max(1, (int)$this->get('page', 1));
        $perPage = 25;
        
        $filters = [
            'start' => $startDate,
            'end' => $endDate,
            'vehicle' => $vehicle,
            'expense' => $expense,
            'page' => $page,
            'perPage' => $perPage
        ];
        
        $rows = $maintenanceModel->getAll($filters);
        $stats = $maintenanceModel->getStats($filters);
        
        $totalRows = (int)$stats['cnt'];
        $totalPages = max(1, (int)ceil($totalRows / $perPage));
        
        $this->view('maintenance/index', [
            'title' => 'Vehicle Maintenance',
            'current' => '/maintenance',
            'rows' => $rows,
            'stats' => $stats,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'vehicle' => $vehicle,
            'expense' => $expense,
            'page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $perPage
        ]);
    }
    
    /**
     * Save maintenance record (AJAX endpoint)
     */
    public function save() {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Invalid request'], 400);
            return;
        }
        
        try {
            $maintenanceModel = $this->model('VehicleMaintenance');
            
            $entryDate = $this->post('entry_date', date('Y-m-d'));
            $vehicleNo = trim($this->post('vehicle_no', ''));
            $expenseType = trim($this->post('expense_type', ''));
            $amount = (float)$this->post('amount', 0);
            $narration = trim($this->post('narration', ''));
            
            if ($vehicleNo === '') {
                throw new Exception("Vehicle number is required.");
            }
            
            if ($expenseType === '') {
                throw new Exception("Expense type is required.");
            }
            
            if ($amount <= 0) {
                throw new Exception("Amount must be greater than zero.");
            }
            
            $data = [
                'entry_date' => $entryDate,
                'vehicle_no' => $vehicleNo,
                'expense_type' => $expenseType,
                'amount' => $amount,
                'narration' => $narration
            ];
            
            $id = $maintenanceModel->create($data);
            
            $entry = $maintenanceModel->getById($id);
            
            $this->json([
                'ok' => true,
                'entry' => $entry
            ]);
        } catch (Exception $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
