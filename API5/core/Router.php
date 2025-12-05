<?php
/**
 * Simple Router for API5
 * Handles route registration and matching
 */

defined('API_ACCESS') or die('Direct access not permitted');

class Router {
    private $routes = [];

    /**
     * Register a GET route
     */
    public function get($route, $callback) {
        $this->addRoute('GET', $route, $callback);
    }

    /**
     * Register a POST route
     */
    public function post($route, $callback) {
        $this->addRoute('POST', $route, $callback);
    }

    /**
     * Register a PUT route
     */
    public function put($route, $callback) {
        $this->addRoute('PUT', $route, $callback);
    }

    /**
     * Register a DELETE route
     */
    public function delete($route, $callback) {
        $this->addRoute('DELETE', $route, $callback);
    }

    /**
     * Add a route to the routing table
     */
    private function addRoute($method, $route, $callback) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'route' => $route,
            'callback' => $callback
        ];
    }

    /**
     * Dispatch the request to the appropriate route
     */
    public function dispatch() {
        $requestedRoute = $_GET['route'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $route['route'] === $requestedRoute) {
                call_user_func($route['callback']);
                return;
            }
        }

        // No route found
        Response::error('Route not found: ' . $requestedRoute, 404, [
            'route' => $requestedRoute,
            'method' => $requestMethod,
            'available_routes' => array_map(function($r) {
                return $r['method'] . ' ' . $r['route'];
            }, $this->routes)
        ]);
    }

    /**
     * Alias for dispatch() - used by index.php
     */
    public function run($route = null) {
        $this->dispatch();
    }
}
