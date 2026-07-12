<?php // Index.php do Catalog
$pdo = Conectar_DB();
$slug = $_GET['empresa'] ?? '';
// index.php - Trecho de roteamento
$url = $_GET['url'] ?? 'catalog/index';

// Remove barras extras para evitar erros de "Rota não encontrada"
$url = trim($url, '/');

// Agora $route_key sempre será algo como 'company' ou 'login'
$route_key = '/' . $url;

// CONFIGURAÇÃO DE PAGINAÇÃO
$itensPorPagina = 20; // Você pode alterar para até 50
$paginaAtual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// 1. Contar total de produtos para calcular páginas
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM film f JOIN companies c ON f.company_id = c.id WHERE c.slug = ? AND f.deleted_at IS NULL");
$stmtCount->execute([$slug]);
$totalItens = $stmtCount->fetchColumn();
$totalPaginas = ceil($totalItens / $itensPorPagina);

// 2. Buscar apenas os produtos da página atual
$stmt = $pdo->prepare("SELECT f.*, c.whatsapp FROM film f JOIN companies c ON f.company_id = c.id WHERE c.slug = ? AND f.deleted_at IS NULL LIMIT ? OFFSET ?");
// Nota: O PDO exige bindValue para LIMIT/OFFSET com tipos inteiros
$stmt->bindValue(1, $slug, PDO::PARAM_STR);
$stmt->bindValue(2, $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll();

$empresa_whatsapp = (!empty($produtos)) ? $produtos[0]['whatsapp'] : '0000000000';
?>

<?php include VIEWS_PATH . 'layouts/header-public.php'; ?>

<form method="GET" action="" class="mb-8 flex gap-2 p-6">
    <input type="hidden" name="empresa" value="<?= htmlspecialchars($slug) ?>">
    <input type="text" id="campo-busca" onkeyup="buscarProdutos()" placeholder="Digite para buscar..."
        class="w-full p-3 border rounded shadow-sm">
    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Buscar</button>
    <?php if (!empty($_GET['q'])): ?>
        <a href="?empresa=<?= $slug ?>" class="bg-gray-500 text-white px-4 py-2 rounded">Limpar</a>
    <?php endif; ?>
</form>

<div class="min-h-screen pb-32">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
        <?php foreach ($produtos as $p): ?>
            <div class="bg-white p-4 rounded shadow hover:shadow-lg transition">
                <img src="https://picsum.photos/seed/<?= urlencode($p['title']) ?>/400/200"
                    class="w-full h-32 object-cover rounded mb-4" loading="lazy">
                <h3 class="font-bold text-lg"><?= $p['title'] ?></h3>
                <p class="text-blue-600 font-bold mb-4">R$ <?= number_format($p['replacement_cost'], 2, ',', '.') ?></p>
                <button onclick="adicionarAoCarrinho('<?= $p['title'] ?>', <?= $p['replacement_cost'] ?>)"
                    class="w-full bg-green-600 text-white p-2 rounded hover:bg-green-700 transition">
                    + Adicionar
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="flex justify-center mt-12 mb-12 space-x-2">
        <?php
        $busca = $_GET['q'] ?? '';
        if ($paginaAtual > 1): ?>
            <a href="?empresa=<?= $slug ?>&page=<?= $paginaAtual - 1 ?>&q=<?= urlencode($busca) ?>" class="px-4 py-2 border rounded bg-white hover:bg-gray-100">Anterior</a>
        <?php endif; ?>

        <?php
        $inicio = max(1, $paginaAtual - 2);
        $fim = min($totalPaginas, $paginaAtual + 2);
        for ($i = $inicio; $i <= $fim; $i++): ?>
            <a href="?empresa=<?= $slug ?>&page=<?= $i ?>&q=<?= urlencode($busca) ?>"
                class="px-4 py-2 border rounded shadow-sm <?= ($i == $paginaAtual) ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100' ?>">
                <?= $i ?>
            </a>
        <?php endfor;

        if ($paginaAtual < $totalPaginas): ?>
            <a href="?empresa=<?= $slug ?>&page=<?= $paginaAtual + 1 ?>&q=<?= urlencode($busca) ?>" class="px-4 py-2 border rounded bg-white hover:bg-gray-100">Próximo</a>
        <?php endif; ?>
    </div>
</div>

<div class="fixed right-6 bottom-20 flex flex-col gap-3">
    <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="bg-gray-800 text-white p-3 rounded-full shadow-lg"><i class="fas fa-arrow-up"></i></button>
    <a href="<?= BASE_URL ?>catalog/carrinho" class="bg-blue-600 text-white p-4 rounded-full shadow-lg"><i class="fas fa-shopping-cart"></i></a>
</div>

<?php include VIEWS_PATH . 'layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/cart.js"></script>
<script>
    function buscarProdutos() {
        let termo = document.getElementById('campo-busca').value;
        let slug = "<?= $slug ?>"; // O PHP injeta o slug aqui

        // Faz a requisição para o servidor sem recarregar a página
        fetch(`<?= BASE_URL ?>catalog/index?empresa=${slug}&q=${termo}&ajax=1`)
            .then(response => response.text())
            .then(html => {
                // Substitui apenas o grid de produtos
                document.querySelector('.grid').innerHTML = html;
            });
    }
</script>