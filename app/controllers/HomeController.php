<?php

/**
 * HomeController
 * Handles the home page (index.php functionality)
 */
class HomeController extends Controller {
    
    public function index() {
        $consignmentModel = $this->model('Consignment');
        $companyModel = $this->model('Company');
        
        $biltyNo = '';
        $biltyRow = null;
        $foundBiltyId = null;
        
        if ($this->isPost()) {
            $biltyNo = trim($this->post('bilty_no', ''));
            
            if ($biltyNo !== '') {
                $biltyRow = $consignmentModel->getByBiltyNo($biltyNo);
                if ($biltyRow) {
                    $foundBiltyId = $biltyRow['id'];
                }
            }
        }
        
        $this->view('home/index', [
            'title' => 'Home',
            'current' => '/',
            'bilty_no' => $biltyNo,
            'bilty_row' => $biltyRow,
            'found_bilty_id' => $foundBiltyId
        ]);
    }
}
