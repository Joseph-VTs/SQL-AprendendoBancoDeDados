<?php include VIEWS_PATH . 'layouts/header.php'; ?>

<div class="max-w-2xl bg-white p-8 rounded shadow">
    <h2 class="text-2xl font-bold mb-6">Cadastrar Nova Empresa</h2>
    <form action="<?= BASE_URL ?>company/store" method="POST">
        <div class="mb-4">
            <label class="block mb-1">Nome da Empresa</label>
            <input type="text" name="nome" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1">WhatsApp (com DDD)</label>
            <input type="text" name="whatsapp" placeholder="555199999999" class="w-full p-2 border rounded" required>
        </div>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Salvar Empresa</button>
    </form>
</div>

<?php include VIEWS_PATH . 'layouts/footer.php'; ?>