<?php
/**
 * XAMPP subdirectory front controller.
 *
 * This lets the project run from /member-system2 without exposing /public
 * in the URL. public/index.php is kept for servers that point DocumentRoot
 * directly at the public directory.
 */

define('BASE_PATH', __DIR__);

spl_autoload_register(function (string $class) {
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/Router.php';
require BASE_PATH . '/config/env.php';
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/config/app.php';

$router = new Core\Router();
require BASE_PATH . '/config/routes.php';
$router->dispatch();
