<?php include VIEWS_PATH . 'layouts/header.php'; ?>
<div class="p-6 bg-white shadow rounded">
    <h2 class="text-xl font-bold mb-4">Formulário de Edição</h2>
    <form action="<?= BASE_URL ?>crud/salvar" method="POST">
        <button type="submit" class="bg-blue-600 text-white p-2">Salvar Alterações</button>
    </form>
</div>
<?php include VIEWS_PATH . 'layouts/footer.php'; ?>