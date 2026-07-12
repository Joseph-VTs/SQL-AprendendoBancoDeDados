<!DOCTYPE html>
<html lang="pt-br">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Login Sakila</title>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <form action="<?= BASE_URL ?>auth/authenticate" method="POST" class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6">Login Sakila</h2>
        <input type="text" name="username" placeholder="Usuário" class="w-full p-2 mb-4 border rounded">
        <input type="password" name="password" placeholder="Senha" class="w-full p-2 mb-4 border rounded">
        <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Entrar</button>
    </form>
</body>
</html>