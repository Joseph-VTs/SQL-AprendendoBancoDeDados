<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config.php';
    require_once __DIR__ . '/../../config/Connection.php';
}
AuthController::check();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Dashboard Sakila</title>
</head>

<body class="bg-gray-50 flex">

    <aside class="w-64 bg-gray-900 text-white min-h-screen p-6">
        <h1 class="text-xl font-bold mb-8">Sakila Admin</h1>
        <nav class="space-y-4">
            <a href="<?= BASE_URL ?>company/dashboard" class="block hover:text-blue-400">Dashboard</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="<?= BASE_URL ?>admin/cadastrar-empresa" class="block hover:text-blue-400">Cadastrar Empresa</a>
                <a href="<?= BASE_URL ?>admin/listar-empresas" class="block hover:text-blue-400">Listar Empresas</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>logout" class="block text-red-400 hover:text-red-300">Sair</a>
        </nav>
    </aside>
    <main class="flex-1 p-8">