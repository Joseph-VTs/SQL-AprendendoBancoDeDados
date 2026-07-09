<?php 
    include_once('../database.php');
    $nome_tabela = filter_input(INPUT_GET, 'nome', FILTER_DEFAULT); 
    
    if (!$nome_tabela) {
        die("Erro: Informe o nome da tabela na URL. Exemplo: arquivo.php?nome=usuarios");
    }

    $db_pdo = Conectar_DB();

    // 1. SEGURANÇA: Valida se a tabela existe (Whitelist)
    $stmt_tabelas = $db_pdo->query("SHOW TABLES");
    $tabelas_permitidas = $stmt_tabelas->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array($nome_tabela, $tabelas_permitidas)) {
        die("Erro: Tabela inválida, inexistente ou acesso negado.");
    }

    // 2. ESTRUTURA DA TABELA
    $sql_estrutura = "DESCRIBE `$nome_tabela`";
    $stmt = $db_pdo->prepare($sql_estrutura);
    $stmt->execute();
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. SISTEMA DE PESQUISA (FILTRO DINÂMICO)
    $termo_busca = filter_input(INPUT_GET, 'busca', FILTER_DEFAULT);
    $where_sql = "";
    $parametros_busca = [];

    if (!empty($termo_busca)) {
        $condicoes = [];
        foreach ($colunas as $coluna) {
            $nome_campo = $coluna['Field'];
            $tipo_campo = strtolower($coluna['Type']);
            // Ignora campos geométricos
            if (strpos($tipo_campo, 'geometry') === false && strpos($tipo_campo, 'point') === false) {
                $condicoes[] = "`$nome_campo` LIKE :busca";
            }
        }
        if (!empty($condicoes)) {
            $where_sql = " WHERE " . implode(" OR ", $condicoes);
            $parametros_busca[':busca'] = "%" . $termo_busca . "%"; 
        }
    }

    // 4. CONFIGURAÇÃO DE PAGINAÇÃO E TOTAL DE REGISTROS
    $itens_por_pagina = 50; 
    $pagina_atual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
    if (!$pagina_atual || $pagina_atual < 1) {
        $pagina_atual = 1;
    }
    $offset = ($pagina_atual - 1) * $itens_por_pagina;

    // Total de registros (Respeitando a pesquisa)
    $sql_total = "SELECT COUNT(*) as total FROM `$nome_tabela`" . $where_sql;
    $stmt_total = $db_pdo->prepare($sql_total);
    if (!empty($termo_busca)) {
        $stmt_total->bindValue(':busca', $parametros_busca[':busca'], PDO::PARAM_STR);
    }
    $stmt_total->execute();
    $total_registros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $itens_por_pagina) ?: 1;

    // 5. MONTAGEM DOS CAMPOS E CONVERSÃO GEOMÉTRICA
    $campos_select = [];
    foreach ($colunas as $coluna) {
        $nome_campo = $coluna['Field'];
        $tipo_campo = strtolower($coluna['Type']);
        if (strpos($tipo_campo, 'geometry') !== false || strpos($tipo_campo, 'point') !== false || strpos($tipo_campo, 'linestring') !== false) {
            $campos_select[] = "ST_AsText(`$nome_campo`) AS `$nome_campo`";
        } else {
            $campos_select[] = "`$nome_campo`";
        }
    }
    $lista_campos = implode(', ', $campos_select);

    // 6. BUSCA FINAL DOS DADOS
    $sql_dados = "SELECT $lista_campos FROM `$nome_tabela` $where_sql LIMIT :limite OFFSET :offset"; 
    $stmt = $db_pdo->prepare($sql_dados);
    
    $stmt->bindValue(':limite', $itens_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    if (!empty($termo_busca)) {
        $stmt->bindValue(':busca', $parametros_busca[':busca'], PDO::PARAM_STR);
    }
    
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
        *{
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            text-decoration: none;
            list-style: none;
            border: none;
        }
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
            margin: 2rem auto 0 auto;
        }
        .container .div-menu{
            /* background-color: aqua; */
            display: flex;
            justify-content: space-between;
            text-align: center;
            gap: 32px;
            height: auto;
            padding: 8px;
            
            a{
                padding: 8px;
                
                span{
                    font-size: 1.06rem;
                    font-weight: 500;
                    letter-spacing: 0.8px;
                }
            }
        }
        h2 {
            font-size: 1.5rem;
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
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .dropdown-content {
            display: none;
            position: fixed;
            top: 4rem;
            right: 2rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 999;
            width: 380px;
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
        }
        .table-container {
            background: var(--surface);
            border-radius: 8px;
            overflow-x: auto;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.95rem;
        }
        th {
            background-color: #f1f5f9;
            padding: 0.75rem 1rem;
            border-bottom: 2px solid var(--border);
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-light);
            white-space: nowrap;
        }
        tbody tr:hover {
            background-color: #f8fafc;
        }
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--surface);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        .pagination-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-page {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            color: var(--text);
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            text-decoration: none;
        }
        .btn-page:hover:not(.disabled) {
            background-color: #f1f5f9;
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

    <!-- BOTÃO INFORMAÇÃO -->
    <button class="info-trigger" onclick="toggleDropdown()">ℹ️ Estrutura</button>

    <!-- DROPDOWN RETRÁTIL -->
    <div id="estruturaDropdown" class="dropdown-content">
        <h3 style="margin-top:0;">⚙️ Colunas de: <?= htmlspecialchars($nome_tabela); ?></h3>
        <?php if (!empty($colunas)): ?>
            <div class="table-container" style="border:none;">
                <table>
                    <thead>
                        <tr><th>Coluna</th><th>Tipo</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($colunas as $coluna): ?>
                            <tr>
                                <td style="color:var(--text); font-weight:500;"><?= htmlspecialchars($coluna['Field']); ?></td>
                                <td><code><?= htmlspecialchars($coluna['Type']); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- TABELA PRINCIPAL DE DADOS -->
    <div class="container">
        <div class="div-menu">
            <h2>📊 Tabela: <span class="badge"><?= htmlspecialchars($nome_tabela); ?></span></h2>
            <a href="Show-Tables.php"><span class="badge">⬅ Voltar</span></a> 
        </div>

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
                                    <td>
                                        <?php 
                                            if (is_string($valor) && preg_match('~[^\x20-\x7E\t\r\n\x80-\xFF]~', $valor)) {
                                                echo '📍 <code style="color:var(--primary);">HEX:' . bin2hex($valor) . '</code>';
                                            } else {
                                                echo htmlspecialchars($valor ?? '-'); 
                                            }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- BARRA DE PAGINAÇÃO -->
            <div class="pagination-container">
                <div class="pagination-info">
                    Página <strong><?= $pagina_atual; ?></strong> de <strong><?= $total_paginas; ?></strong>
                </div>
                <div class="pagination-buttons">
                    <a href="?nome=<?= urlencode($nome_tabela); ?>&pagina=<?= $pagina_atual - 1; ?><?= !empty($termo_busca) ? '&busca=' . urlencode($termo_busca) : ''; ?>" 
                    class="btn-page <?= ($pagina_atual <= 1) ? 'disabled' : ''; ?>">&laquo; Anterior</a>

                    <a href="?nome=<?= urlencode($nome_tabela); ?>&pagina=<?= $pagina_atual + 1; ?><?= !empty($termo_busca) ? '&busca=' . urlencode($termo_busca) : ''; ?>" 
                    class="btn-page <?= ($pagina_atual >= $total_paginas) ? 'disabled' : ''; ?>">Próximo &raquo;</a>
                </div>
            </div>
        <?php else: ?>
            <p>Nenhum dado cadastrado nesta tabela.</p>
        <?php endif; ?>
    </div>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('estruturaDropdown');
            dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
        }

        window.onclick = function(event) {
            if (!event.target.matches('.info-trigger') && !event.target.closest('.dropdown-content')) {
                const dropdown = document.getElementById('estruturaDropdown');
                if (dropdown) dropdown.style.display = 'none';
            }
        }
    </script>
</body>
</html>
