<?php

class Router
{
    private $routes = [];
    private $basePath = '';

    public function __construct($basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = $this->getRequestPath();

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestPath)) {
                $params = $this->extractParams($route['path'], $requestPath);
                return $this->callHandler($route['handler'], $params);
            }
        }

        // Default route - redirect to home
        if ($requestPath === '' || $requestPath === '/') {
            return $this->callHandler('HomeController@index', []);
        }

        // 404 handler
        http_response_code(404);
        include 'app/views/errors/404.php';
    }

    private function getRequestPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        // Remove base path
        if ($this->basePath && strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath));
        }

        return '/' . trim($path, '/');
    }

    private function matchPath($routePath, $requestPath)
    {
        // Simple pattern matching for now
        $pattern = str_replace(['{id}', '{slug}'], ['([0-9]+)', '([a-zA-Z0-9\-_]+)'], $routePath);
        $pattern = '^' . str_replace('/', '\/', $pattern) . '$';
        
        return preg_match('/' . $pattern . '/', $requestPath);
    }

    private function extractParams($routePath, $requestPath)
    {
        $params = [];
        
        // Extract parameters from URL
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));

        foreach ($routeParts as $index => $part) {
            if (strpos($part, '{') !== false && isset($requestParts[$index])) {
                $paramName = trim($part, '{}');
                $params[$paramName] = $requestParts[$index];
            }
        }

        return $params;
    }

    private function callHandler($handler, $params = [])
    {
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            
            $controllerFile = "app/controllers/{$controller}.php";
            if (!file_exists($controllerFile)) {
                throw new Exception("Controller file not found: {$controllerFile}");
            }

            require_once $controllerFile;
            
            if (!class_exists($controller)) {
                throw new Exception("Controller class not found: {$controller}");
            }

            $controllerInstance = new $controller();
            
            if (!method_exists($controllerInstance, $method)) {
                throw new Exception("Method {$method} not found in {$controller}");
            }

            return call_user_func_array([$controllerInstance, $method], array_values($params));
        }

        if (is_callable($handler)) {
            return call_user_func_array($handler, array_values($params));
        }

        throw new Exception("Invalid handler");
    }
}