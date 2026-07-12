<?php
// models/SakilaModel.php
// Funções de suporte para o legado
function obterTabelas($pdo) { return $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN); }

function descreverTabela($pdo, $tabela) { 
    return $pdo->query("DESCRIBE `$tabela`")->fetchAll(PDO::FETCH_ASSOC); 
}

function validarDados($pdo, $tabela, $dados) {
    // Mantém a lógica de validação robusta que construímos
    $colunas = descreverTabela($pdo, $tabela);
    foreach ($colunas as $col) {
        if ($col['Null'] === 'NO' && ($col['Extra'] !== 'auto_increment') && empty($dados[$col['Field']])) {
            throw new Exception("Campo {$col['Field']} é obrigatório.");
        }
    }
}