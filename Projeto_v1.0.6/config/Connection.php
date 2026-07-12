<?php
// config/Connection.php

function Conectar_DB() {
    $env_file = BASE_PATH . '/.env';
    if (!file_exists($env_file)) {
        die("Erro: Arquivo .env não encontrado.");
    }

    $env = parse_ini_file($env_file);
    
    try {
        $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], $options);
    } catch (PDOException $e) {
        error_log("Erro de Conexão: " . $e->getMessage());
        die("Erro interno no servidor.");
    }
}