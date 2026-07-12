<?php include VIEWS_PATH . 'layouts/header.php'; ?>
<div class="max-w-xl bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Novo Produto</h2>
    <form action="<?= BASE_URL ?>company/salvar-produto" method="POST">
        <input type="text" name="title" placeholder="Nome do Produto" class="w-full p-2 border rounded mb-4" required>
        <button type="submit" class="bg-blue-600 text-white p-2 rounded">Cadastrar</button>
    </form>
</div>
<?php include VIEWS_PATH . 'layouts/footer.php'; ?>