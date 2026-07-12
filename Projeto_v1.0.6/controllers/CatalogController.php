<?php
class CatalogController
{
    public function index()
    {
        $pdo = Conectar_DB();
        $slug = $_GET['empresa'] ?? '';
        $busca = $_GET['q'] ?? '';

        // 1. Busca empresa
        $stmt = $pdo->prepare("SELECT id, nome, whatsapp FROM companies WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $empresa = $stmt->fetch();

        if (!$empresa) die("Empresa não encontrada.");

        // 2. Query usando parâmetros nomeados para evitar o erro HY093
        $sql = "SELECT * FROM film WHERE company_id = :company_id AND deleted_at IS NULL";
        $params = ['company_id' => $empresa['id']];

        if (!empty($busca)) {
            $sql .= " AND title LIKE :busca";
            $params['busca'] = "%$busca%";
        }

        // Adiciona paginação à query
        $sql .= " LIMIT :limit OFFSET :offset";

        // Prepara e executa
        $stmt = $pdo->prepare($sql);

        // VINDO OS PARÂMETROS CORRETAMENTE
        $stmt->bindValue('company_id', $empresa['id'], PDO::PARAM_INT);
        if (!empty($busca)) {
            $stmt->bindValue('busca', "%$busca%", PDO::PARAM_STR);
        }
        // Para LIMIT e OFFSET é obrigatório usar bindValue com PARAM_INT
        $stmt->bindValue('limit', 20, PDO::PARAM_INT);
        $stmt->bindValue('offset', 0, PDO::PARAM_INT); // Ajuste conforme sua lógica de página

        $stmt->execute();
        $produtos = $stmt->fetchAll();

        if (isset($_GET['ajax'])) {
            // Retorna apenas os cards, sem header e footer
            foreach ($produtos as $p) {
                echo '<div class="bg-white p-4 rounded shadow">...</div>';
            }
            exit; // Para a execução aqui
        }

        include VIEWS_PATH . 'catalog/index.php';
    }
}
