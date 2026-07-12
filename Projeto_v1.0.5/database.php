<?php 
    ini_set('display_errors', 1); // Desative a exibição direta na tela em produção
    ini_set('log_errors', 1);     // Ative o registro de erros em arquivos de log
    
    function Conectar_DB(): PDO {
        // IMPORTANTE: Garanta que o .env esteja fora da pasta pública do servidor
        $env = parse_ini_file(__DIR__.'/.env');
        // Usar utf8mb4 é mais seguro/moderno
        $conexao = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4"; 
        
        try {
            return new PDO($conexao, $env['DB_USER'], $env['DB_PASS'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Evitar duplicação de dados na resposta
                PDO::ATTR_EMULATE_PREPARES => false // Segurança extra contra SQL Injection
            ]);
        } catch (PDOException $e) {
            // Salva o erro real e secreto no log do servidor Apache (error.log).
            error_log("Erro de Conexão DB: " . $e->getMessage());
            
            // Mensagem amigável e segura para o público
            // die("❗ Desculpe, ocorreu um problema interno de conexão. Tente novamente mais tarde.");
            die("❗ Erro real: " . $e->getMessage());
        }
    }
?>