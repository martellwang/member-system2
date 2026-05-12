<?php

namespace Core;

class Router
{
    private array $routes = [];

    /** 註冊路由 */
    public function get(string $path, string $controller, string $action): void
    {
        $this->routes['GET'][$path] = [$controller, $action];
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->routes['POST'][$path] = [$controller, $action];
    }

    /** 解析並分派請求 */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $url    = trim($_GET['url'] ?? '', '/');

        // 嘗試完整匹配
        if (isset($this->routes[$method][$url])) {
            [$controller, $action] = $this->routes[$method][$url];
            $this->call($controller, $action);
            return;
        }

        // 嘗試動態參數匹配（例如 members/123/approve）
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
            if (preg_match("#^{$regex}$#", $url, $matches)) {
                array_shift($matches);
                [$controller, $action] = $handler;
                $this->call($controller, $action, $matches);
                return;
            }
        }

        // 404
        http_response_code(404);
        echo json_encode(['error' => '路由不存在']);
    }

    private function call(string $controller, string $action, array $params = []): void
    {
        $class = "Controllers\\{$controller}";
        if (!class_exists($class)) {
            http_response_code(500);
            echo json_encode(['error' => "Controller {$controller} 不存在"]);
            return;
        }
        $obj = new $class();
        if (!method_exists($obj, $action)) {
            http_response_code(500);
            echo json_encode(['error' => "Action {$action} 不存在"]);
            return;
        }
        $obj->$action(...$params);
    }
}
