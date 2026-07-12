<div class="mt-8">
    <h3 class="font-bold mb-2">Empresas já cadastradas:</h3>
    <table class="w-full text-left">
        <thead>
            <tr class="border-b">
                <th class="p-2">Nome</th>
                <th class="p-2">Slug</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $pdo = Conectar_DB();
            $empresas = $pdo->query("SELECT nome, slug FROM companies")->fetchAll();
            foreach ($empresas as $e): ?>
                <tr class="border-b">
                    <td class="p-2"><?= $e['nome'] ?></td>
                    <td class="p-2 font-mono text-blue-600 bg-blue-50 px-2 rounded"><?= $e['slug'] ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>catalog/index?empresa=<?= $e['slug'] ?>" target="_blank" class="text-blue-500 underline">
                            Acessar Loja
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>