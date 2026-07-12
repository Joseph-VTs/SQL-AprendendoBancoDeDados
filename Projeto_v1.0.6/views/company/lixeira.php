<?php
// views/company/lixeira.php
AuthController::check('empresa');
$pdo = Conectar_DB();
$stmt = $pdo->prepare("SELECT * FROM film WHERE company_id = ? AND deleted_at IS NOT NULL");
$stmt->execute([$_SESSION['company_id']]);
$excluidos = $stmt->fetchAll();
?>

<h2 class="text-2xl font-bold mb-6 text-red-600">🗑️ Lixeira de Produtos</h2>
<ul>
    <?php foreach ($excluidos as $p): ?>
        <li class="mb-2 p-2 bg-white rounded shadow flex justify-between">
            <?= $p['title'] ?>
            <a href="<?= BASE_URL ?>product/restore/<?= $p['film_id'] ?>" class="text-green-600">Restaurar</a>
        </li>
    <?php endforeach; ?>
</ul>