<?php
// config.php
define('BASE_PATH', __DIR__);
define('BASE_URL', 'http://localhost/Funcionais%20em%20PHP/SQL-default_city/Projeto_v1.0.6/');

// Caminhos úteis
define('VIEWS_PATH', BASE_PATH . '/views/');
define('MODELS_PATH', BASE_PATH . '/models/');
define('STORAGE_PATH', BASE_PATH . '/storage/');

// Configurações de exibição (Segurança em produção)
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', STORAGE_PATH . 'logs/php-error.log');