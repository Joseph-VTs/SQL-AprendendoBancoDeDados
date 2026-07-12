<?php include VIEWS_PATH . 'layouts/header.php'; ?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>company/cadastrar-produto" class="bg-green-600 text-white px-4 py-2 rounded">
        + Cadastrar Novo Produto
    </a>
</div>
<h2 class="text-2xl font-bold mb-6">Meus Produtos</h2>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($produtos as $p): ?>
        <div class="bg-white p-4 rounded shadow">
            <h3 class="font-bold"><?= $p['title'] ?></h3>
            <p class="text-sm text-gray-600 mb-2">Cliques: <?= $p['clicks'] ?></p>
            
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?= min($p['clicks'] * 10, 100) ?>%"></div>
            </div>
            
            <a href="<?= BASE_URL ?>product/delete/<?= $p['film_id'] ?>" class="text-red-500 text-sm mt-4 block">Excluir</a>
        </div>
    <?php endforeach; ?>
</div>

<?php include VIEWS_PATH . 'layouts/footer.php'; ?>