<?php

require_once 'Controller.php';
require_once 'app/models/Company.php';

class CompanyController extends Controller
{
    private $companyModel;

    public function __construct()
    {
        $this->companyModel = new Company();
    }

    public function index()
    {
        $companies = $this->companyModel->getAll();
        
        $data = [
            'title' => 'Companies',
            'companies' => $companies
        ];

        echo $this->view('companies/index', $data);
    }

    public function create()
    {
        if ($this->isPost()) {
            try {
                $name = trim($this->input('name'));
                $address = trim($this->input('address'));

                $id = $this->companyModel->createCompany($name, $address);

                // Return JSON response for AJAX requests
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    $this->json([
                        'ok' => true,
                        'company' => [
                            'id' => $id,
                            'name' => $name,
                            'address' => $address
                        ]
                    ]);
                }

                $this->redirect('/companies?success=Company created successfully');
            } catch (Exception $e) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
                }

                $this->redirect('/companies?error=' . urlencode($e->getMessage()));
            }
        }

        echo $this->view('companies/create');
    }

    public function store()
    {
        header('Content-Type: application/json');

        try {
            $name = trim($this->input('name', ''));
            $address = trim($this->input('address', ''));

            if (empty($name)) {
                throw new Exception("Company name is required.");
            }

            $id = $this->companyModel->createCompany($name, $address);

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

    public function show($id)
    {
        $company = $this->companyModel->find($id);
        
        if (!$company) {
            $this->redirect('/companies?error=Company not found');
            return;
        }

        $data = [
            'title' => 'Company Details',
            'company' => $company
        ];

        echo $this->view('companies/show', $data);
    }

    public function edit($id)
    {
        $company = $this->companyModel->find($id);
        
        if (!$company) {
            $this->redirect('/companies?error=Company not found');
            return;
        }

        if ($this->isPost()) {
            try {
                $name = trim($this->input('name'));
                $address = trim($this->input('address'));

                if (empty($name)) {
                    throw new Exception("Company name is required.");
                }

                $this->companyModel->update($id, [
                    'name' => $name,
                    'address' => $address
                ]);

                $this->redirect('/companies?success=Company updated successfully');
            } catch (Exception $e) {
                $data = [
                    'title' => 'Edit Company',
                    'company' => $company,
                    'error' => $e->getMessage()
                ];

                echo $this->view('companies/edit', $data);
                return;
            }
        }

        $data = [
            'title' => 'Edit Company',
            'company' => $company
        ];

        echo $this->view('companies/edit', $data);
    }

    public function delete($id)
    {
        if ($this->isPost()) {
            try {
                $deleted = $this->companyModel->delete($id);
                
                if ($deleted) {
                    $this->redirect('/companies?success=Company deleted successfully');
                } else {
                    $this->redirect('/companies?error=Failed to delete company');
                }
            } catch (Exception $e) {
                $this->redirect('/companies?error=' . urlencode($e->getMessage()));
            }
        }

        $this->redirect('/companies');
    }
}