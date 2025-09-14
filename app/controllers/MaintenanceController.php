<?php

require_once 'Controller.php';

class MaintenanceController extends Controller
{
    public function index()
    {
        // For now, include the original vehicle_maintenance.php logic
        // This can be refactored later to extract data operations to models
        
        // Start output buffering to capture the existing maintenance file
        ob_start();
        include 'vehicle_maintenance.php';
        $content = ob_get_clean();
        
        // For now, just output the content
        echo $content;
    }

    public function store()
    {
        // Handle vehicle maintenance save
        if ($this->isPost()) {
            // Include the original save logic
            include 'vehicle_maintenance_save.php';
        }
    }
}