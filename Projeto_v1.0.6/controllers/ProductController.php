<?php
// controllers/ProductController.php
class ProductController
{
    public function dashboard()
    {
        AuthController::check('empresa');
        $pdo = Conectar_DB();
        $company_id = $_SESSION['company_id'];

        // Busca apenas produtos da empresa logada
        $stmt = $pdo->prepare("SELECT * FROM film WHERE company_id = ? AND deleted_at IS NULL");
        $stmt->execute([$company_id]);
        $produtos = $stmt->fetchAll();

        include VIEWS_PATH . 'company/dashboard.php';
    }

    public function delete($id)
    {
        AuthController::check('empresa');
        $pdo = Conectar_DB();

        // Verifica se o produto pertence à empresa antes de deletar
        $stmt = $pdo->prepare("UPDATE film SET deleted_at = NOW() WHERE film_id = ? AND company_id = ?");
        $stmt->execute([$id, $_SESSION['company_id']]);

        // Log de Auditoria
        $log = new Crud($pdo);
        $log->log('EXCLUIR_PRODUTO', 'film', $id);

        header("Location: " . BASE_URL . "company/dashboard");
    }

    public function create()
    {
        AuthController::check('empresa'); // Garante que só empresa logada acesse
        include VIEWS_PATH . 'company/cadastrar-produto.php';
    }

    public function store()
    {
        AuthController::check('empresa');
        $pdo = Conectar_DB();

        // O Sakila original exige language_id, rental_duration, rental_rate e replacement_cost
        $sql = "INSERT INTO film (title, company_id, language_id, rental_duration, rental_rate, replacement_cost) 
            VALUES (?, ?, 1, 3, 4.99, 19.99)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['title'], $_SESSION['company_id']]);

        header("Location: " . BASE_URL . "company/dashboard");
    }
}
