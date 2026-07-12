<?php 
include VIEWS_PATH . 'layouts/header.php';
$pdo = Conectar_DB();
$tabelas = obterTabelas($pdo);
?>

<div class="p-6">
    <h2 class="text-2xl font-bold mb-6">Explorador de Tabelas Sakila</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ($tabelas as $t): ?>
            <a href="<?= BASE_URL ?>views/sakila/formulario?tabela=<?= $t ?>" 
               class="p-4 bg-white shadow rounded hover:bg-blue-50">
                <i class="fas fa-database text-blue-500"></i> <?= $t ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include VIEWS_PATH . 'layouts/footer.php'; ?>