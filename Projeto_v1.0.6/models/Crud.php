<?php
// models/Crud.php
class Crud {
    protected $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    protected function log($acao, $tabela, $id = null) {
        $user = $_SESSION['usuario_logado'] ?? 'Sistema';
        $log = "[" . date('Y-m-d H:i:s') . "] User: $user | Acao: $acao | Tabela: $tabela | ID: $id" . PHP_EOL;
        file_put_contents(STORAGE_PATH . 'logs/acesso.log', $log, FILE_APPEND);
    }

    public function delete($tabela, $id) {
        $stmt = $this->pdo->prepare("UPDATE `$tabela` SET deleted_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $this->log('EXCLUIR', $tabela, $id);
    }
}