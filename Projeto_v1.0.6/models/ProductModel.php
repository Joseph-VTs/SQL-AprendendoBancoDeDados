<?php
// models/ProductModel.php
class ProductModel {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function getProdutosEmpresa($company_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM film WHERE company_id = ? AND deleted_at IS NULL");
        $stmt->execute([$company_id]);
        return $stmt->fetchAll();
    }

    public function incrementarClique($id) {
        $stmt = $this->pdo->prepare("UPDATE film SET clicks = clicks + 1 WHERE film_id = ?");
        $stmt->execute([$id]);
    }
}