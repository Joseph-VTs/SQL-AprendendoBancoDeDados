<?php 
    include_once('../database.php');
    $nome_tabela = filter_input(INPUT_GET, 'nome', FILTER_DEFAULT); 
    $db_pdo = Conectar_DB();

    // ==========================================
    // CONFIGURAÇÃO DA PAGINAÇÃO
    // ==========================================
    $itens_por_pagina = 50; // Altere aqui a quantidade de linhas por página
    $pagina_atual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
    if (!$pagina_atual || $pagina_atual < 1) {
        $pagina_atual = 1;
    }
    $offset = ($pagina_atual - 1) * $itens_por_pagina;

    // 1. Conta o total de registros na tabela para saber o fim das páginas
    $sql_total = "SELECT COUNT(*) as total FROM `$nome_tabela`";
    $stmt_total = $db_pdo->query($sql_total);
    $total_registros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $itens_por_pagina);

    // 2. Busca a estrutura da tabela
    $sql_estrutura = "DESCRIBE `$nome_tabela`";
    $stmt = $db_pdo->prepare($sql_estrutura);
    $stmt->execute();
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Busca os dados limitados pela paginação (LIMIT e OFFSET)
    $sql_dados = "SELECT * FROM `$nome_tabela` LIMIT :limite OFFSET :offset"; 
    $stmt = $db_pdo->prepare($sql_dados);
    // Vinculamos como INT para o MySQL não dar erro de sintaxe no LIMIT
    $stmt->bindValue(':limite', $itens_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador - <?= htmlspecialchars($nome_tabela); ?></title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --background: #f8fafc;
            --surface: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--background);
            color: var(--text);
            margin: 0;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            font-size: 1.5rem;
            color: var(--text);
            margin-top: 2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge {
            background-color: var(--primary);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .table-container {
            background: var(--surface);
            border-radius: 8px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.95rem;
        }

        th {
            background-color: #f1f5f9;
            color: var(--text);
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-bottom: 2px solid var(--border);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-light);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        .empty-message {
            padding: 1.5rem;
            text-align: center;
            color: var(--text-light);
            background: var(--surface);
            border-radius: 8px;
            border: 1px dashed var(--border);
        }

        /* ESTILOS DA PAGINAÇÃO */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            background: var(--surface);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .pagination-info {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .pagination-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-page {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text);
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-page:hover:not(.disabled) {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
        }

        .btn-page.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-page.disabled {
            color: #cbd5e1;
            background-color: #f8fafc;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>
<body>
<div class="container">

    <!-- SEÇÃO 1: ESTRUTURA DA TABELA -->
    <style>
        .info-trigger {
            position: fixed;
            top: 1rem;
            right: 2rem;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: background 0.2s;
        }

        .info-trigger:hover {
            background-color: var(--primary-hover);
        }

        .dropdown-content {
            display: none;
            position: fixed;
            top: 4rem;
            right: 2rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            z-index: 999;
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
        }

        .dropdown-content h3 {
            margin-top: 0;
            font-size: 1.1rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.5rem;
            margin-bottom: 0.5rem;
        }

        /* Ajuste para o container principal não colidir com o botão fixo */
        .container {
            margin-top: 2rem;
        }
    </style>
    <!-- BOTÃO FLUTUANTE DE INFORMAÇÃO (Fica no canto superior direito) -->
    <button class="info-trigger" onclick="toggleDropdown()">ℹ️ Ver Estrutura da Tabela</button>

    <!-- MENU DROPDOWN RETRÁTIL -->
    <div id="estruturaDropdown" class="dropdown-content">
        <h3>⚙️ Estrutura da Tabela: <span class="badge"><?= htmlspecialchars($nome_tabela); ?></span></h3>
        
        <?php if (!empty($colunas)): ?>
            <div class="table-container" style="box-shadow: none; margin-bottom: 0;">
                <table>
                    <thead>
                        <tr>
                            <th>Nome da Coluna</th>
                            <th>Tipo de Dado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($colunas as $coluna): ?>
                            <tr>
                                <td style="color: var(--text); font-weight: 500;"><?= htmlspecialchars($coluna['Field']); ?></td>
                                <td><code><?= htmlspecialchars($coluna['Type']); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty-message">Nenhuma estrutura encontrada ou tabela inexistente.</p>
        <?php endif; ?>
    </div>


    <!-- SEÇÃO 2: DADOS DA TABELA (DINÂMICO) -->
    <h2>📊 Dados Armazenados</h2>

    <?php if (!empty($resultados)): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($resultados[0]) as $nome_coluna): ?>
                            <th><?= htmlspecialchars($nome_coluna); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $linha): ?>
                        <tr>
                            <?php foreach ($linha as $valor): ?>
                                <td><?= htmlspecialchars($valor ?? '-'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- BARRA DE PAGINAÇÃO -->
        <div class="pagination-container">
            <div class="pagination-info">
                Mostrando página <strong><?= $pagina_atual; ?></strong> de <strong><?= $total_paginas; ?></strong> (Total: <?= $total_registros; ?> registros)
            </div>
            
            <div class="pagination-buttons">
                <!-- Botão Voltar -->
                <a href="?nome=<?= urlencode($nome_tabela); ?>&pagina=<?= $pagina_atual - 1; ?>" 
                   class="btn-page <?= ($pagina_atual <= 1) ? 'disabled' : ''; ?>">
                    &laquo; Anterior
                </a>

                <!-- Número da Página Atual -->
                <span class="btn-page active"><?= $pagina_atual; ?></span>

                <!-- Botão Avançar -->
                <a href="?nome=<?= urlencode($nome_tabela); ?>&pagina=<?= $pagina_atual + 1; ?>" 
                   class="btn-page <?= ($pagina_atual >= $total_paginas) ? 'disabled' : ''; ?>">
                    Próximo &raquo;
                </a>
            </div>
        </div>

    <?php else: ?>
        <p class="empty-message">Nenhum dado cadastrado nesta tabela ainda.</p>
    <?php endif; ?>
</div>

<!-- ADICIONE ESTE SCRIPT JAVASCRIPT ANTES DE FECHAR A TAG </body> -->
<script>
    function toggleDropdown() {
        const dropdown = document.getElementById('estruturaDropdown');
        if (dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
        } else {
            dropdown.style.display = 'block';
        }
    }

    // Fecha o dropdown se o usuário clicar fora dele
    window.onclick = function(event) {
        if (!event.target.matches('.info-trigger') && !event.target.closest('.dropdown-content')) {
            const dropdown = document.getElementById('estruturaDropdown');
            if (dropdown) dropdown.style.display = 'none';
        }
    }
</script>
</body>
</html>