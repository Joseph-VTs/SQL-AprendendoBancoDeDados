<?php 
    include_once('../database.php');
    
    // 1. RECEBE OS PARÂMETROS DA URL
    $nome_tabela = filter_input(INPUT_GET, 'nome', FILTER_DEFAULT); 
    $termo_busca = filter_input(INPUT_GET, 'busca', FILTER_DEFAULT);
    
    if (!$nome_tabela) {
        die("Erro: Informe o nome da tabela na URL. Exemplo: arquivo.php?nome=usuarios");
    }

    $db_pdo = Conectar_DB();

    // 2. SEGURANÇA: Valida se a tabela existe (Whitelist)
    $stmt_tabelas = $db_pdo->query("SHOW TABLES");
    $tabelas_permitidas = $stmt_tabelas->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array($nome_tabela, $tabelas_permitidas)) {
        die("Erro: Tabela inválida, inexistente ou acesso negado.");
    }

    // 3. ESTRUTURA DA TABELA
    $sql_estrutura = "DESCRIBE `$nome_tabela`";
    $stmt = $db_pdo->prepare($sql_estrutura);
    $stmt->execute();
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. SISTEMA DE PESQUISA (FILTRO DINÂMICO)
    $where_sql = "";
    $parametros_busca = [];

    if (!empty($termo_busca)) {
        $condicoes = [];
        $contador = 0; 
        
        foreach ($colunas as $coluna) {
            $nome_campo = $coluna['Field'];
            $tipo_campo = strtolower($coluna['Type']);
            
            // Ignora campos geométricos na pesquisa
            if (strpos($tipo_campo, 'geometry') === false && strpos($tipo_campo, 'point') === false) {
                $nome_parametro = ":busca_" . $contador; 
                $condicoes[] = "`$nome_campo` LIKE " . $nome_parametro;
                $parametros_busca[$nome_parametro] = "%" . $termo_busca . "%"; 
                $contador++;
            }
        }
        
        if (!empty($condicoes)) {
            $where_sql = " WHERE " . implode(" OR ", $condicoes);
        }
    }

    // 5. CONFIGURAÇÃO DE PAGINAÇÃO E TOTAL DE REGISTROS
    $itens_por_pagina = 50; 
    $pagina_atual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
    if (!$pagina_atual || $pagina_atual < 1) {
        $pagina_atual = 1;
    }
    $offset = ($pagina_atual - 1) * $itens_por_pagina;

    // Faz o COUNT já respeitando o filtro de busca, se houver
    $sql_total = "SELECT COUNT(*) as total FROM `$nome_tabela`" . $where_sql;
    $stmt_total = $db_pdo->prepare($sql_total);
    
    if (!empty($termo_busca)) {
        foreach ($parametros_busca as $parametro => $valor) {
            $stmt_total->bindValue($parametro, $valor, PDO::PARAM_STR);
        }
    }
    
    $stmt_total->execute();
    $total_registros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_registros / $itens_por_pagina) ?: 1;

    // 6. MONTAGEM DOS CAMPOS PARA O SELECT
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

    // 7. BUSCA FINAL DOS DADOS (SELECT)
    $sql_dados = "SELECT $lista_campos FROM `$nome_tabela` $where_sql LIMIT :limite OFFSET :offset"; 
    $stmt = $db_pdo->prepare($sql_dados);
    
    $stmt->bindValue(':limite', $itens_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    if (!empty($termo_busca)) {
        foreach ($parametros_busca as $parametro => $valor) {
            $stmt->bindValue($parametro, $valor, PDO::PARAM_STR);
        }
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
            left: 0.2rem;
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
            left: 0.4rem;
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

        /* BOTÃO VOLTAR AO TOPO */
        #btnVoltarTopo {
            display: none; /* Fica escondido até rolar a tela */
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 50%; /* Deixa o botão redondo */
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s, transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.2s;
        }

        #btnVoltarTopo.mostrar {
            opacity: 1;
            visibility: visible;
        }

        #btnVoltarTopo:hover {
            background-color: var(--primary-hover);
            transform: scale(1.1); /* Dá um pequeno zoom ao passar o mouse */
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

        <div style="background: var(--surface); padding: 1rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center;">
            <form method="GET" action="" style="display: flex; gap: 0.5rem; width: 100%;">
                <input type="hidden" name="nome" value="<?= htmlspecialchars($nome_tabela); ?>">
                
                <input type="text" name="busca" value="<?= htmlspecialchars($termo_busca ?? ''); ?>" 
                       placeholder="Pesquisar em todas as colunas..." 
                       style="flex-grow: 1; padding: 0.6rem 1rem; border: 1px solid var(--border); border-radius: 6px; outline: none; font-size: 0.95rem;">
                
                <button type="submit" style="background: var(--primary); color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    🔍 Buscar
                </button>
                
                <?php if (!empty($termo_busca)): ?>
                    <a href="?nome=<?= urlencode($nome_tabela); ?>" 
                       style="background: #f1f5f9; color: var(--text); padding: 0.6rem 1rem; border-radius: 6px; text-decoration: none; border: 1px solid var(--border);">
                        Limpar
                    </a>
                <?php endif; ?>
            </form>
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
    <button id="btnVoltarTopo" onclick="voltarAoTopo()" title="Voltar ao topo">
        ⬆️
    </button>
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



        // ==========================================
        // LÓGICA DO BOTÃO VOLTAR AO TOPO
        // ==========================================
        const btnTopo = document.getElementById("btnVoltarTopo");

        // Monitora a rolagem da tela
        window.addEventListener('scroll', function() {
            // Se rolar mais de 40 pixels para baixo, mostra o botão
            if (window.scrollY > 40) {
                btnTopo.classList.add("mostrar");
            } else {
                btnTopo.classList.remove("mostrar");
            }
        });

        // Função que sobe a tela suavemente
        function voltarAoTopo() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth' // Faz a rolagem ser animada e não um pulo seco
            });
        }
    </script>
</body>
</html>
