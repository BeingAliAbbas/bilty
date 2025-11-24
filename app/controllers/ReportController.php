<?php

/**
 * ReportController
 * Handles reporting and analytics
 */
class ReportController extends Controller {
    
    /**
     * Show reports page
     */
    public function index() {
        $consignmentModel = $this->model('Consignment');
        $companyModel = $this->model('Company');
        
        // This is a simplified version - the full reports logic from reports.php
        // would need to be implemented here
        
        $this->view('report/index', [
            'title' => 'Reports',
            'current' => '/report'
        ]);
    }
}
