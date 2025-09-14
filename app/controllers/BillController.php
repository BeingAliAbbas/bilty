<?php

require_once 'Controller.php';
require_once 'app/models/Bill.php';

class BillController extends Controller
{
    private $billModel;

    public function __construct()
    {
        $this->billModel = new Bill();
    }

    public function index()
    {
        $filters = [
            'status' => $this->input('status', 'UNPAID'),
            'search' => trim($this->input('q', '')),
            'sort' => $this->input('sort', 'date_desc'),
            'page' => max(1, intval($this->input('page', 1))),
            'pageSize' => 20
        ];

        $result = $this->billModel->getBillsWithFilters($filters);
        $stats = $this->billModel->getStatistics();

        $data = [
            'title' => 'Manage Bills',
            'bills' => $result['bills'],
            'totalRows' => $result['totalRows'],
            'currentPage' => $result['currentPage'],
            'totalPages' => $result['totalPages'],
            'pageSize' => $result['pageSize'],
            'filters' => $filters,
            'stats' => $stats
        ];

        echo $this->view('bills/index', $data);
    }

    public function create()
    {
        if ($this->isPost()) {
            try {
                $data = [
                    'bill_no' => $this->input('bill_no'),
                    'company_id' => $this->input('company_id'),
                    'gross_amount' => $this->input('gross_amount'),
                    'tax_amount' => $this->input('tax_amount'),
                    'net_amount' => $this->input('net_amount'),
                    'issue_date' => $this->input('issue_date'),
                    'status' => $this->input('status', 'DRAFT')
                ];

                $id = $this->billModel->createBill($data);

                $this->redirect('/bills?success=Bill created successfully');
            } catch (Exception $e) {
                $data = [
                    'title' => 'Create Bill',
                    'error' => $e->getMessage(),
                    'old_input' => $_POST
                ];

                echo $this->view('bills/create', $data);
                return;
            }
        }

        $data = [
            'title' => 'Create Bill'
        ];

        echo $this->view('bills/create', $data);
    }

    public function show($id)
    {
        $bill = $this->billModel->getBillWithCompany($id);
        
        if (!$bill) {
            $this->redirect('/bills?error=Bill not found');
            return;
        }

        $data = [
            'title' => 'Bill Details',
            'bill' => $bill
        ];

        echo $this->view('bills/show', $data);
    }

    public function updatePayment()
    {
        header('Content-Type: application/json');

        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'POST method required'], 405);
            return;
        }

        try {
            $billId = intval($this->input('bill_id'));
            $action = $this->input('action');
            $note = trim($this->input('note', ''));
            $paymentDate = $this->input('payment_date');

            if (!$billId) {
                throw new Exception('Bill ID is required');
            }

            if (!in_array($action, ['PAID', 'UNPAID'])) {
                throw new Exception('Invalid action');
            }

            $updated = $this->billModel->updatePaymentStatus($billId, $action, $paymentDate, $note);

            if ($updated) {
                $bill = $this->billModel->find($billId);
                $this->json([
                    'ok' => true,
                    'payment_status' => $bill['payment_status'],
                    'payment_date' => $bill['payment_date']
                ]);
            } else {
                $this->json(['ok' => false, 'error' => 'Failed to update bill'], 400);
            }
        } catch (Exception $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }
}