<?php

abstract class Controller
{
    protected function view($viewFile, $data = [])
    {
        // Extract data to make variables available in the view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewPath = "app/views/{$viewFile}.php";
        if (!file_exists($viewPath)) {
            throw new Exception("View file not found: {$viewPath}");
        }
        
        include $viewPath;
        
        // Get the contents and clean the buffer
        $content = ob_get_clean();
        
        return $content;
    }

    protected function redirect($url, $statusCode = 302)
    {
        header("Location: {$url}", true, $statusCode);
        exit();
    }

    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    protected function input($key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function validateRequired($fields)
    {
        $errors = [];
        foreach ($fields as $field => $label) {
            $value = trim($this->input($field, ''));
            if (empty($value)) {
                $errors[] = "{$label} is required.";
            }
        }
        return $errors;
    }

    protected function sanitizeInput($input)
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}