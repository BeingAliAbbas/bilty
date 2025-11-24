<?php

/**
 * CompanyController
 * Handles company-related operations
 */
class CompanyController extends Controller {
    
    /**
     * Save a new company (AJAX endpoint)
     */
    public function save() {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Invalid request'], 400);
            return;
        }
        
        try {
            $companyModel = $this->model('Company');
            
            $name = trim($this->post('name', ''));
            $address = trim($this->post('address', ''));
            
            if ($name === '') {
                throw new Exception("Company name is required.");
            }
            
            if (mb_strlen($name) > 150) {
                throw new Exception("Company name too long (max 150).");
            }
            
            if ($address !== '' && mb_strlen($address) > 255) {
                throw new Exception("Address too long (max 255).");
            }
            
            $id = $companyModel->create($name, $address);
            
            $this->json([
                'ok' => true,
                'company' => [
                    'id' => $id,
                    'name' => $name,
                    'address' => $address
                ]
            ]);
        } catch (Exception $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
