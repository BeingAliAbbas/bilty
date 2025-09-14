<?php

require_once 'Controller.php';

class ReportsController extends Controller
{
    public function index()
    {
        // For now, include the original reports.php logic
        // This can be refactored later to extract data operations to models
        
        // Start output buffering to capture the existing reports.php
        ob_start();
        include 'reports.php';
        $content = ob_get_clean();
        
        // For now, just output the content
        // In a full refactor, this would extract data logic to models
        echo $content;
    }
}