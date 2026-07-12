<?php
    require_once dirname(__DIR__, 2) . '/config.php';
    require_once BASE_PATH . '/config/Connection.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Catálogo</title>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4 flex justify-between">
        <h1 class="font-bold text-xl">Nossa Loja</h1>
        <a href="<?= BASE_URL ?>login" class="text-sm underline">Área do Lojista</a>
    </nav>
    <main class="p-6">