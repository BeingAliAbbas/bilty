<?php

/**
 * Router Class
 * Handles URL routing and request dispatching
 */
class Router {
    private $routes = [];
    private $notFound = null;
    
    /**
     * Add a GET route
     */
    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }
    
    /**
     * Add a POST route
     */
    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }
    
    /**
     * Add a route for any method
     */
    public function any($path, $callback) {
        $this->addRoute('ANY', $path, $callback);
    }
    
    /**
     * Set 404 handler
     */
    public function setNotFound($callback) {
        $this->notFound = $callback;
    }
    
    /**
     * Add a route to the routes array
     */
    private function addRoute($method, $path, $callback) {
        $pattern = $this->convertPathToRegex($path);
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'callback' => $callback
        ];
    }
    
    /**
     * Convert route path to regex pattern
     */
    private function convertPathToRegex($path) {
        // Convert :param to regex capture group
        $pattern = preg_replace('/\/:([a-zA-Z0-9_]+)/', '/(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        return $pattern;
    }
    
    /**
     * Dispatch the request
     */
    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if application is in subdirectory
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/') {
            $requestUri = substr($requestUri, strlen($scriptName));
        }
        
        $requestUri = rtrim($requestUri, '/');
        if (empty($requestUri)) {
            $requestUri = '/';
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $requestMethod) {
                continue;
            }
            
            if (preg_match($route['pattern'], $requestUri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                return $this->invokeCallback($route['callback'], $params);
            }
        }
        
        // No route matched, call 404 handler
        if ($this->notFound) {
            return $this->invokeCallback($this->notFound, []);
        }
        
        // Default 404
        http_response_code(404);
        echo "404 - Page Not Found";
    }
    
    /**
     * Invoke the route callback
     */
    private function invokeCallback($callback, $params) {
        if (is_callable($callback)) {
            return call_user_func_array($callback, [$params]);
        }
        
        if (is_string($callback)) {
            // Format: 'ControllerName@methodName'
            list($controller, $method) = explode('@', $callback);
            
            $controllerPath = __DIR__ . '/../app/controllers/' . $controller . '.php';
            
            if (!file_exists($controllerPath)) {
                throw new Exception("Controller not found: " . $controller);
            }
            
            require_once $controllerPath;
            
            if (!class_exists($controller)) {
                throw new Exception("Controller class not found: " . $controller);
            }
            
            $controllerInstance = new $controller();
            
            if (!method_exists($controllerInstance, $method)) {
                throw new Exception("Method not found: " . $controller . '::' . $method);
            }
            
            return call_user_func_array([$controllerInstance, $method], [$params]);
        }
        
        throw new Exception("Invalid callback format");
    }
}
