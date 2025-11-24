<?php

/**
 * BiltyController
 * Handles all bilty/consignment related operations
 */
class BiltyController extends Controller {
    
    /**
     * Display all bilties
     */
    public function index() {
        $consignmentModel = $this->model('Consignment');
        $companyModel = $this->model('Company');
        
        $search = trim($this->get('q', ''));
        $companyFilter = (int)$this->get('company', 0);
        
        $filters = [
            'search' => $search,
            'company_id' => $companyFilter
        ];
        
        $rows = $consignmentModel->getAll($filters);
        $companies = $companyModel->getAll();
        
        $this->view('bilty/index', [
            'title' => 'All Bilties',
            'current' => '/bilty',
            'rows' => $rows,
            'companies' => $companies,
            'search' => $search,
            'company_filter' => $companyFilter
        ]);
    }
    
    /**
     * Show add bilty form
     */
    public function add() {
        $consignmentModel = $this->model('Consignment');
        $companyModel = $this->model('Company');
        
        $errors = [];
        $success = '';
        $autoBiltyNo = $consignmentModel->getNextBiltyNo();
        
        if ($this->isPost()) {
            // Process form submission
            $result = $this->processAddBilty($consignmentModel);
            $errors = $result['errors'];
            $success = $result['success'];
            
            if (empty($errors) && $success) {
                $autoBiltyNo = $consignmentModel->getNextBiltyNo();
            }
        }
        
        $companies = $companyModel->getAll();
        $hasRateType = $consignmentModel->hasRateTypeColumn();
        $hasAddress = $companyModel->hasAddressColumn();
        
        $this->view('bilty/add', [
            'title' => 'Add New Bilty',
            'current' => '/bilty/add',
            'errors' => $errors,
            'success' => $success,
            'auto_bilty_no' => $autoBiltyNo,
            'companies' => $companies,
            'has_rate_type' => $hasRateType,
            'has_address' => $hasAddress,
            'post_data' => $this->isPost() ? $_POST : []
        ]);
    }
    
    /**
     * View bilty details
     */
    public function details($params) {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        
        if ($id <= 0) {
            $this->redirect('/bilty');
            return;
        }
        
        $consignmentModel = $this->model('Consignment');
        $bilty = $consignmentModel->getById($id);
        
        if (!$bilty) {
            $this->redirect('/bilty');
            return;
        }
        
        $this->view('bilty/details', [
            'title' => 'Bilty Details',
            'current' => '/bilty',
            'bilty' => $bilty
        ]);
    }
    
    /**
     * Print bilty
     */
    public function print($params) {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        
        if ($id <= 0) {
            $this->redirect('/bilty');
            return;
        }
        
        $consignmentModel = $this->model('Consignment');
        $bilty = $consignmentModel->getById($id);
        
        if (!$bilty) {
            $this->redirect('/bilty');
            return;
        }
        
        $this->view('bilty/print', [
            'title' => 'Print Bilty',
            'bilty' => $bilty
        ], null); // No layout
    }
    
    /**
     * Bulk print bilties
     */
    public function bulkPrint() {
        $consignmentModel = $this->model('Consignment');
        $companyModel = $this->model('Company');
        
        $ids = [];
        $selectedIds = $this->get('ids', '');
        
        if ($selectedIds) {
            $ids = array_map('intval', explode(',', $selectedIds));
            $ids = array_filter($ids);
        }
        
        $bilties = [];
        if (!empty($ids)) {
            $bilties = $consignmentModel->getByIds($ids);
        }
        
        // If auto=1, just render print page
        if ($this->get('auto') === '1') {
            $this->view('bilty/bulk-print', [
                'title' => 'Print Bilties',
                'bilties' => $bilties
            ], null); // No layout
            return;
        }
        
        // Otherwise show selection page
        $companies = $companyModel->getAll();
        $allBilties = $consignmentModel->getAll();
        
        $this->view('bilty/bulk-print-select', [
            'title' => 'Select Bilties to Print',
            'current' => '/bilty',
            'companies' => $companies,
            'bilties' => $allBilties
        ]);
    }
    
    /**
     * Update bilty payment
     */
    public function updatePayment() {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Invalid request'], 400);
            return;
        }
        
        try {
            $consignmentModel = $this->model('Consignment');
            
            $id = (int)$this->post('bilty_id', 0);
            $advance = (float)$this->post('advance', 0);
            $amount = (float)$this->post('amount', 0);
            $balance = $amount - $advance;
            
            if ($id <= 0) {
                throw new Exception('Invalid bilty ID');
            }
            
            $consignmentModel->updatePayment($id, $advance, $balance);
            
            $this->json([
                'ok' => true,
                'bilty_id' => $id,
                'advance' => $advance,
                'balance' => $balance
            ]);
        } catch (Exception $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Process add bilty form submission
     */
    private function processAddBilty($consignmentModel) {
        $errors = [];
        $success = '';
        
        // Sanitize input
        $biltyNo = trim($this->post('bilty_no', ''));
        $date = $this->post('date', date('Y-m-d'));
        $companyId = (int)$this->post('company', 0);
        $vehicleNo = trim($this->post('vehicle_no', ''));
        $vehicleOwner = ($this->post('vehicle_owner', 'own') === 'rental') ? 'rental' : 'own';
        $driverName = trim($this->post('driver_name', ''));
        $driverNumber = trim($this->post('driver_number', ''));
        $vehicleType = trim($this->post('vehicle_type', ''));
        $senderName = trim($this->post('sender_name', ''));
        $fromCity = trim($this->post('from_city', ''));
        $toCity = trim($this->post('to_city', ''));
        $qty = (int)$this->post('qty', 0);
        $details = trim($this->post('details', ''));
        $km = (int)$this->post('km', 0);
        $rateInput = (float)$this->post('rate', 0);
        $fixed = ($this->post('fixed') === '1');
        
        // Rate calculation
        $rateToSave = $fixed ? 0.0 : $rateInput;
        $rateType = $fixed ? 'Fixed' : 'PerKM';
        
        // Amount calculation
        if ($fixed) {
            $amount = round((float)str_replace(',', '', $this->post('amount', 0)), 2);
        } else {
            $amount = round($km * $rateInput, 2);
        }
        
        $advance = (float)$this->post('advance', 0);
        $balance = round($amount - $advance, 2);
        
        // Validations
        if ($biltyNo === '') {
            $errors[] = "Bilty number is required.";
        } elseif (strlen($biltyNo) > 50) {
            $errors[] = "Bilty number must be 50 characters or less.";
        }
        
        if ($companyId <= 0) {
            $errors[] = "Please select a company.";
        }
        if ($qty < 0) {
            $errors[] = "Quantity cannot be negative.";
        }
        if ($km < 0) {
            $errors[] = "Distance (KM) cannot be negative.";
        }
        if ($rateInput < 0) {
            $errors[] = "Rate cannot be negative.";
        }
        if ($advance < 0) {
            $errors[] = "Advance cannot be negative.";
        }
        if ($amount < 0) {
            $errors[] = "Amount cannot be negative.";
        }
        if ($amount == 0) {
            $errors[] = "Amount must be greater than zero.";
        }
        
        // Add extra info to details
        $extra = [];
        $extra[] = "Vehicle: " . ($vehicleOwner === 'rental' ? "Rental" : "Own");
        if ($driverNumber !== '') {
            $extra[] = "Driver number: " . $driverNumber;
        }
        if (!empty($extra)) {
            $details = trim($details);
            if ($details !== '') {
                $details .= "\n\n";
            }
            $details .= "Additional info:\n" . implode("\n", $extra);
        }
        
        // Save if no errors
        if (empty($errors)) {
            try {
                $data = [
                    'company_id' => $companyId,
                    'bilty_no' => $biltyNo,
                    'date' => $date,
                    'vehicle_no' => $vehicleNo,
                    'driver_name' => $driverName,
                    'vehicle_type' => $vehicleType,
                    'sender_name' => $senderName,
                    'from_city' => $fromCity,
                    'to_city' => $toCity,
                    'qty' => $qty,
                    'details' => $details,
                    'km' => $km,
                    'rate' => $rateToSave,
                    'amount' => $amount,
                    'advance' => $advance,
                    'balance' => $balance,
                    'rate_type' => $rateType
                ];
                
                $consignmentModel->create($data);
                $success = "Bilty has been saved successfully.";
                $_POST = []; // Clear POST data
            } catch (Exception $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
        
        return ['errors' => $errors, 'success' => $success];
    }
}
