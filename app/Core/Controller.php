<?php

namespace Core;

class Controller
{
    /** 渲染 View */
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $file = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($file)) {
            http_response_code(500);
            echo "View 不存在：{$view}";
            return;
        }
        require BASE_PATH . '/app/Views/layouts/header.php';
        require $file;
        require BASE_PATH . '/app/Views/layouts/footer.php';
    }

    /** 輸出 JSON（API 回應） */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /** 取得 JSON Request Body */
    protected function input(): array
    {
        $body = file_get_contents('php://input');
        return json_decode($body, true) ?? [];
    }

    /** 重導向 */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
