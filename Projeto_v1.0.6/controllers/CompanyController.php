<?php
// controllers/CompanyController.php
class CompanyController
{
    public function cadastrarEmpresa()
    {
        AuthController::check('admin'); // Apenas ADM pode cadastrar empresas
        include VIEWS_PATH . 'admin/cadastrar-empresa.php';
    }

    public function store()
    {
        AuthController::check('admin');
        $pdo = Conectar_DB();
        $slug = strtolower(str_replace(' ', '-', $_POST['nome']));

        $stmt = $pdo->prepare("INSERT INTO companies (nome, slug, whatsapp) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['nome'], $slug, $_POST['whatsapp']]);

        header("Location: " . BASE_URL . "admin/cadastrar-empresa?msg=sucesso");
    }

    // Dentro da classe CompanyController
    public function listarEmpresas()
    {
        AuthController::check('admin'); // Apenas ADM pode listar
        $pdo = Conectar_DB();
        $empresas = $pdo->query("SELECT * FROM companies")->fetchAll();

        include VIEWS_PATH . 'admin/listar-empresas.php';
    }
}
