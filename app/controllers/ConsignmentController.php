<?php

require_once 'Controller.php';
require_once '../app/models/Consignment.php';
require_once '../app/models/Company.php';

class ConsignmentController extends Controller
{
    private $consignmentModel;
    private $companyModel;

    public function __construct()
    {
        $this->consignmentModel = new Consignment();
        $this->companyModel = new Company();
    }

    public function index()
    {
        $filters = [
            'search' => trim($this->input('q', '')),
            'company_id' => intval($this->input('company', 0))
        ];

        $consignments = $this->consignmentModel->getWithCompany($filters);
        $companies = $this->companyModel->getAll();

        $data = [
            'title' => 'Bilty Records',
            'consignments' => $consignments,
            'companies' => $companies,
            'filters' => $filters
        ];

        echo $this->view('consignments/index', $data);
    }

    public function create()
    {
        if ($this->isPost()) {
            try {
                $data = [
                    'bilty_no' => $this->input('bilty_no'),
                    'date' => $this->input('date'),
                    'company_id' => $this->input('company'),
                    'vehicle_no' => $this->input('vehicle_no'),
                    'vehicle_owner' => $this->input('vehicle_owner'),
                    'driver_name' => $this->input('driver_name'),
                    'driver_number' => $this->input('driver_number'),
                    'vehicle_type' => $this->input('vehicle_type'),
                    'sender_name' => $this->input('sender_name'),
                    'from_city' => $this->input('from_city'),
                    'to_city' => $this->input('to_city'),
                    'qty' => $this->input('qty'),
                    'details' => $this->input('details'),
                    'km' => $this->input('km'),
                    'rate' => $this->input('rate'),
                    'advance' => $this->input('advance')
                ];

                $id = $this->consignmentModel->createConsignment($data);

                $data = [
                    'title' => 'Add New Bilty',
                    'companies' => $this->companyModel->getAllWithAddressCheck(),
                    'success' => 'Bilty has been saved successfully.',
                    'auto_bilty_no' => $this->consignmentModel->getNextBiltyNo()
                ];

                echo $this->view('consignments/create', $data);
                return;

            } catch (Exception $e) {
                $data = [
                    'title' => 'Add New Bilty',
                    'companies' => $this->companyModel->getAllWithAddressCheck(),
                    'errors' => [$e->getMessage()],
                    'auto_bilty_no' => $this->consignmentModel->getNextBiltyNo(),
                    'old_input' => $_POST
                ];

                echo $this->view('consignments/create', $data);
                return;
            }
        }

        $data = [
            'title' => 'Add New Bilty',
            'companies' => $this->companyModel->getAllWithAddressCheck(),
            'auto_bilty_no' => $this->consignmentModel->getNextBiltyNo()
        ];

        echo $this->view('consignments/create', $data);
    }

    public function show($id)
    {
        $consignment = $this->consignmentModel->getWithCompanyById($id);
        
        if (!$consignment) {
            $this->redirect('/consignments?error=Bilty not found');
            return;
        }

        $data = [
            'title' => 'Bilty Details',
            'consignment' => $consignment
        ];

        echo $this->view('consignments/show', $data);
    }

    public function edit($id)
    {
        $consignment = $this->consignmentModel->find($id);
        
        if (!$consignment) {
            $this->redirect('/consignments?error=Bilty not found');
            return;
        }

        if ($this->isPost()) {
            try {
                $data = [
                    'bilty_no' => $this->input('bilty_no'),
                    'date' => $this->input('date'),
                    'company_id' => $this->input('company'),
                    'vehicle_no' => $this->input('vehicle_no'),
                    'driver_name' => $this->input('driver_name'),
                    'vehicle_type' => $this->input('vehicle_type'),
                    'sender_name' => $this->input('sender_name'),
                    'from_city' => $this->input('from_city'),
                    'to_city' => $this->input('to_city'),
                    'qty' => $this->input('qty'),
                    'details' => $this->input('details'),
                    'km' => $this->input('km'),
                    'rate' => $this->input('rate'),
                    'amount' => $this->input('amount'),
                    'advance' => $this->input('advance'),
                    'balance' => $this->input('balance')
                ];

                $this->consignmentModel->update($id, $data);

                $this->redirect('/consignments?success=Bilty updated successfully');
            } catch (Exception $e) {
                $data = [
                    'title' => 'Edit Bilty',
                    'consignment' => $consignment,
                    'companies' => $this->companyModel->getAll(),
                    'error' => $e->getMessage()
                ];

                echo $this->view('consignments/edit', $data);
                return;
            }
        }

        $data = [
            'title' => 'Edit Bilty',
            'consignment' => $consignment,
            'companies' => $this->companyModel->getAll()
        ];

        echo $this->view('consignments/edit', $data);
    }

    public function bulk()
    {
        $ids = $this->input('ids', '');
        if (empty($ids)) {
            $this->redirect('/consignments?error=No bilties selected');
            return;
        }

        $idArray = array_map('intval', explode(',', $ids));
        $consignments = $this->consignmentModel->getByIds($idArray);

        if (empty($consignments)) {
            $this->redirect('/consignments?error=No valid bilties found');
            return;
        }

        $data = [
            'title' => 'Bulk Print Bilties',
            'consignments' => $consignments,
            'auto_print' => $this->input('auto') === '1'
        ];

        echo $this->view('consignments/bulk', $data);
    }

    public function export()
    {
        $filters = [
            'search' => trim($this->input('q', '')),
            'company_id' => intval($this->input('company', 0))
        ];

        $consignments = $this->consignmentModel->getWithCompany($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=bilties.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'ID', 'Bilty No', 'Date', 'Company', 'From', 'To', 'Vehicle No', 
            'Vehicle Type', 'Vehicle Owner', 'Driver', 'Driver Number', 'Qty', 
            'KM', 'Rate', 'Amount', 'Advance', 'Balance', 'Notes'
        ]);

        foreach ($consignments as $row) {
            // Extract meta if present
            $owner = '';
            $driver_number = '';
            $notes = '';
            
            if (!empty($row['details'])) {
                if (preg_match('/Vehicle:\s*(Own|Rental)/i', $row['details'], $m)) {
                    $owner = ucfirst(strtolower($m[1]));
                }
                if (preg_match('/Driver number:\s*([0-9+\-\s()]+)/i', $row['details'], $m2)) {
                    $driver_number = trim($m2[1]);
                }
                // Clean notes
                $notes = preg_replace('/Additional info:.*$/s', '', $row['details']);
                $notes = str_replace(["\r", "\n"], [' ', ' '], trim($notes));
            }

            fputcsv($output, [
                $row['id'] ?? '',
                $row['bilty_no'] ?? '',
                $row['date'] ?? '',
                $row['company_name'] ?? '',
                $row['from_city'] ?? '',
                $row['to_city'] ?? '',
                $row['vehicle_no'] ?? '',
                $row['vehicle_type'] ?? '',
                $owner,
                $row['driver_name'] ?? '',
                $driver_number,
                $row['qty'] ?? '',
                $row['km'] ?? '',
                $row['rate'] ?? '',
                $row['amount'] ?? '',
                $row['advance'] ?? '',
                $row['balance'] ?? '',
                $notes
            ]);
        }

        fclose($output);
        exit;
    }
}