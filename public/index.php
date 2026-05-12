<?php
/**
 * Front Controller — 所有請求入口
 */

define('BASE_PATH', dirname(__DIR__));

// 自動載入
spl_autoload_register(function (string $class) {
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require $file;
});

require BASE_PATH . '/app/Core/Router.php';
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/config/app.php';

// 啟動路由
$router = new Core\Router();
require BASE_PATH . '/config/routes.php';
$router->dispatch();
