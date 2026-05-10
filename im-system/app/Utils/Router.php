<?php

namespace App\Utils;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    
    public function __construct()
    {
        $this->loadRoutes();
    }
    
    private function loadRoutes()
    {
        $routes = require BASE_PATH . '/routes/api.php';
        
        foreach ($routes as $route) {
            $this->addRoute(
                $route['method'],
                $route['path'],
                $route['handler'],
                $route['middleware'] ?? []
            );
        }
    }
    
    private function addRoute($method, $path, $handler, $middleware = [])
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }
    
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        
        if (empty($uri)) {
            $uri = '/';
        }
        
        $matchedRoute = null;
        $params = [];
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->convertToRegex($route['path']);
            
            if (preg_match($pattern, $uri, $matches)) {
                $matchedRoute = $route;
                
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                break;
            }
        }
        
        if (!$matchedRoute) {
            Response::error('Route not found', 404, 'NOT_FOUND', 404);
            return;
        }
        
        foreach ($matchedRoute['middleware'] as $middleware) {
            if ($middleware === 'auth') {
                $currentUser = \App\Middleware\AuthMiddleware::handle();
                $_GET['current_user'] = $currentUser;
            } elseif ($middleware === 'admin') {
                $admin = \App\Middleware\AdminMiddleware::handle();
                $_GET['admin_user'] = $admin;
            }
        }
        
        $_GET = array_merge($_GET, $params);
        
        $handler = $matchedRoute['handler'];
        $parts = explode('@', $handler);
        $controllerClass = $parts[0];
        $method = $parts[1];
        
        $controller = new $controllerClass();
        
        if (method_exists($controller, $method)) {
            if (!empty($params)) {
                $controller->$method(...array_values($params));
            } else {
                $controller->$method();
            }
        } else {
            Response::error('Method not found', 404, 'NOT_FOUND', 404);
        }
    }
    
    private function convertToRegex($path)
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $pattern . '$#';
    }
}
