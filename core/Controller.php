<?php

/**
 * Base Controller Class
 * All controllers extend this class
 */
class Controller {
    
    /**
     * Load a model
     */
    protected function model($model) {
        $modelPath = __DIR__ . '/../app/models/' . $model . '.php';
        
        if (file_exists($modelPath)) {
            require_once $modelPath;
            return new $model();
        }
        
        throw new Exception("Model not found: " . $model);
    }
    
    /**
     * Load a view
     */
    protected function view($view, $data = [], $layout = 'layouts/main') {
        extract($data);
        
        $viewPath = __DIR__ . '/../app/views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new Exception("View not found: " . $view);
        }
        
        // Capture view content
        ob_start();
        require $viewPath;
        $content = ob_get_clean();
        
        // If no layout, just output content
        if ($layout === null || $layout === false) {
            echo $content;
            return;
        }
        
        // Load layout
        $layoutPath = __DIR__ . '/../app/views/' . $layout . '.php';
        
        if (!file_exists($layoutPath)) {
            throw new Exception("Layout not found: " . $layout);
        }
        
        require $layoutPath;
    }
    
    /**
     * Redirect to another URL
     */
    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Get POST data
     */
    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    
    /**
     * Get GET data
     */
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
    
    /**
     * Check if request is POST
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Check if request is GET
     */
    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
}
