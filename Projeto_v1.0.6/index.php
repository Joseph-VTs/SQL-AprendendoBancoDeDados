<?php
// index.php - Front Controller Unificado
require_once 'config.php';
require_once 'config/Connection.php';
require_once 'controllers/AuthController.php';

// Captura a URL (remove a barra inicial se existir para facilitar)
$url = $_GET['url'] ?? 'catalog/index';
$route_key = '/' . ltrim($url, '/');

$routes = require 'config/routes.php';

// 1. Tenta rotear via Controller
if (array_key_exists($route_key, $routes)) {
    $parts = explode('/', $routes[$route_key]);
    $controllerName = ucfirst($parts[0]) . 'Controller';
    $method = $parts[1];
    
    $controllerFile = "controllers/{$controllerName}.php";
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        $controller = new $controllerName();
        $controller->$method();
    } else {
        die("Controller não encontrado: " . $controllerFile);
    }
} 
// 2. Fallback: Tenta carregar uma View diretamente (ex: catalog/index)
else {
    $viewFile = VIEWS_PATH . $url . '.php';
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        die("Rota ou View não encontrada: " . $url);
    }
}