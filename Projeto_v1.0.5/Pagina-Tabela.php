<?php
include_once('../database.php');

$nome_tabela = filter_input(INPUT_GET, 'nome', FILTER_DEFAULT);
$termo_busca = filter_input(INPUT_GET, 'busca', FILTER_DEFAULT);

if (!$nome_tabela) {
    die("Erro: Informe o nome da tabela na URL.");
}

$db_pdo = Conectar_DB();

// 1. Valida se a tabela existe (Whitelist)
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

// =========================================================
// 3. LÓGICA INTELIGENTE: PESQUISA + LIXEIRA
// =========================================================
$where_sql = "";
$parametros_busca = [];
$condicoes_where = [];

// Passo A: Verifica se esta tabela específica possui a coluna 'deleted_at'
$tem_lixeira = false;
foreach ($colunas as $coluna) {
    if ($coluna['Field'] === 'deleted_at') {
        $tem_lixeira = true;
        break;
    }
}

// Se a tabela tiver lixeira, obriga a mostrar apenas registros não excluídos
if ($tem_lixeira) {
    $condicoes_where[] = "`deleted_at` IS NULL";
}

// Passo B: Aplica a pesquisa do usuário
if (!empty($termo_busca)) {
    $condicoes_busca = [];
    $contador = 0;

    foreach ($colunas as $coluna) {
        $nome_campo = $coluna['Field'];
        $tipo_campo = strtolower($coluna['Type']);

        if (strpos($tipo_campo, 'geometry') === false && strpos($tipo_campo, 'point') === false) {
            $nome_parametro = ":busca_" . $contador;
            $condicoes_busca[] = "`$nome_campo` LIKE " . $nome_parametro;
            $parametros_busca[$nome_parametro] = "%" . $termo_busca . "%";
            $contador++;
        }
    }

    if (!empty($condicoes_busca)) {
        // Colocamos os ORs entre parênteses para não conflitar com a lixeira
        $condicoes_where[] = "(" . implode(" OR ", $condicoes_busca) . ")";
    }
}

// Passo C: Junta a Lixeira com a Pesquisa (se existirem)
if (!empty($condicoes_where)) {
    $where_sql = " WHERE " . implode(" AND ", $condicoes_where);
}
// =========================================================

// 4. PAGINAÇÃO E CONTAGEM
$itens_por_pagina = 20;
$pagina_atual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
if (!$pagina_atual || $pagina_atual < 1) $pagina_atual = 1;

$offset = ($pagina_atual - 1) * $itens_por_pagina;

$sql_total = "SELECT COUNT(*) as total FROM `$nome_tabela`" . $where_sql;
$stmt_total = $db_pdo->prepare($sql_total);

if (!empty($parametros_busca)) {
    foreach ($parametros_busca as $parametro => $valor) {
        $stmt_total->bindValue($parametro, $valor, PDO::PARAM_STR);
    }
}

$stmt_total->execute();
$total_registros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina) ?: 1;

// 5. SELECT DOS DADOS (Convertendo geometria)
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

// Identifica a primeira coluna como sendo o ID (PK)
$coluna_id = $colunas[0]['Field']; 

// Adiciona o ORDER BY antes do LIMIT
$sql_dados = "SELECT $lista_campos FROM `$nome_tabela` $where_sql ORDER BY `$coluna_id` DESC LIMIT :limite OFFSET :offset";
$stmt = $db_pdo->prepare($sql_dados);

$stmt->bindValue(':limite', $itens_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

if (!empty($parametros_busca)) {
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
    <title>Gerenciador (Cards) - <?= htmlspecialchars($nome_tabela); ?></title>
    <style>
        * {
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
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto 0 auto;
        }

        /* CSS CORRIGIDO (Removido o aninhamento para evitar bugs em navegadores) */
        .container .div-menu {
            display: flex;
            justify-content: space-between;
            text-align: center;
            align-items: center;
            gap: 32px;
            margin-bottom: 1.5rem;
        }

        .container .div-menu a {
            padding: 8px;
        }

        .container .div-menu a span {
            font-size: 1.06rem;
            font-weight: 500;
            letter-spacing: 0.8px;
        }

        h2 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge {
            background-color: var(--primary);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            position: relative;
            /* Segura o overlay */
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15);
        }

        .card-id-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background-color: var(--text);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.9rem;
            z-index: 30;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .card-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid var(--border);
            background-color: #f1f5f9;
        }

        .card-body {
            padding: 1.25rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.5rem;
            word-break: break-all;
        }

        .card-field {
            font-size: 0.9rem;
            line-height: 1.4;
            display: flex;
            flex-direction: column;
        }

        .card-field strong {
            color: var(--text);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .card-field span {
            color: var(--text-light);
            word-break: break-word;
        }

        /* OVERLAY E BOTÕES */
        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.75);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 20;
            backdrop-filter: blur(2px);
        }

        .card:hover .card-overlay {
            opacity: 1;
            visibility: visible;
        }

        .btn-action {
            width: 75%;
            padding: 0.6rem;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .btn-view {
            background-color: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }

        .btn-view:hover {
            background-color: var(--primary-hover);
            transform: scale(1.05);
        }

        .btn-edit {
            background-color: #f59e0b;
            color: white;
            border: 1px solid #f59e0b;
        }

        .btn-edit:hover {
            background-color: #d97706;
            transform: scale(1.05);
        }

        .btn-delete {
            background-color: #ef4444;
            color: white;
            border: 1px solid #ef4444;
        }

        .btn-delete:hover {
            background-color: #dc2626;
            transform: scale(1.05);
        }

        /* COMPONENTES COMPARTILHADOS */
        .info-trigger {
            position: fixed;
            top: 1rem;
            left: 1rem;
            background-color: var(--primary);
            color: white;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            z-index: 1000;
        }

        .dropdown-content {
            display: none;
            position: fixed;
            top: 4rem;
            left: 1rem;
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
            transition: 0.2s;
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

        #btnVoltarTopo {
            display: none;
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: opacity 0.3s, visibility 0.3s, transform 0.2s;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
        }

        #btnVoltarTopo.mostrar {
            opacity: 1;
            visibility: visible;
            display: flex;
        }

        #btnVoltarTopo:hover {
            background-color: var(--primary-hover);
            transform: scale(1.1);
        }
    </style>
</head>

<body>
    <button class="info-trigger" onclick="toggleDropdown()">ℹ️ Estrutura</button>

    <div id="estruturaDropdown" class="dropdown-content">
        <h3 style="margin-top:0;">⚙️ Colunas de: <?= htmlspecialchars($nome_tabela); ?></h3>
        <?php if (!empty($colunas)): ?>
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 8px; margin-top: 10px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th style="padding: 8px; border-bottom: 2px solid var(--border);">Coluna</th>
                            <th style="padding: 8px; border-bottom: 2px solid var(--border);">Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($colunas as $coluna): ?>
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid var(--border); font-weight:500;"><?= htmlspecialchars($coluna['Field']); ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid var(--border);"><code><?= htmlspecialchars($coluna['Type']); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <div class="div-menu">
            <h2>📦 Vitrine (Cards): <span class="badge"><?= htmlspecialchars($nome_tabela); ?></span></h2>
            <div>
                <a href="Crud.php?acao=adicionar&tabela=<?= urlencode($nome_tabela); ?>&origem=vitrine">
                    <span class="badge" style="background-color: #10b981;">➕ Adicionar</span>
                </a>

                <a href="Lixeira.php?nome=<?= urlencode($nome_tabela); ?>">
                    <span class="badge" style="background-color: #ef4444;">🗑️ Ver Lixeira</span>
                </a>

                <a href="BD-Tables.php">
                    <span class="badge" style="background-color: var(--primary);">⬅ Voltar</span>
                </a>
            </div>
        </div>

        <div style="background: var(--surface); padding: 1rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center;">
            <form method="GET" action="" style="display: flex; gap: 0.5rem; width: 100%;">
                <input type="hidden" name="nome" value="<?= htmlspecialchars($nome_tabela); ?>">
                <input type="text" name="busca" value="<?= htmlspecialchars($termo_busca ?? ''); ?>"
                    placeholder="Pesquisar em todas as colunas..."
                    style="flex-grow: 1; padding: 0.6rem 1rem; border: 1px solid var(--border); border-radius: 6px; outline: none; font-size: 0.95rem; background: var(--background);">
                <button type="submit" style="background: var(--primary); color: white; padding: 0.6rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    🔍 Buscar
                </button>
                <?php if (!empty($termo_busca)): ?>
                    <a href="?nome=<?= urlencode($nome_tabela); ?>" style="background: #f1f5f9; color: var(--text); padding: 0.6rem 1rem; border-radius: 6px;">Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($resultados)): ?>

            <div class="cards-grid">
                <?php foreach ($resultados as $linha): ?>
                    <?php
                    $colunas_linha = array_keys($linha);
                    $coluna_id = $colunas_linha[0];
                    $valor_id = $linha[$coluna_id];
                    $seed = md5($nome_tabela . $valor_id);
                    $coluna_titulo = isset($colunas_linha[1]) ? $colunas_linha[1] : $coluna_id;
                    $valor_titulo = $linha[$coluna_titulo];
                    ?>

                    <div class="card">
                        <div class="card-id-badge">
                            #<?= htmlspecialchars($valor_id); ?>
                        </div>

                        <div class="card-overlay">
                            <a href="Crud.php?acao=ver&tabela=<?= urlencode($nome_tabela); ?>&id=<?= urlencode($valor_id); ?>&origem=vitrine" class="btn-action btn-view">
                                👁️ Ver Detalhes
                            </a>
                            <a href="Crud.php?acao=editar&tabela=<?= urlencode($nome_tabela); ?>&id=<?= urlencode($valor_id); ?>&origem=vitrine" class="btn-action btn-edit">
                                ✏️ Editar Item
                            </a>
                            <a href="Crud.php?acao=excluir&tabela=<?= urlencode($nome_tabela); ?>&id=<?= urlencode($valor_id); ?>&origem=vitrine" class="btn-action btn-delete">
                                🗑️ Mover para Lixeira
                            </a>
                        </div>

                        <img src="https://picsum.photos/seed/<?= $seed ?>/400/200" alt="Imagem Ilustrativa" class="card-img" loading="lazy">

                        <div class="card-body">
                            <div class="card-title">
                                <?= htmlspecialchars($coluna_titulo); ?>: <?= htmlspecialchars($valor_titulo); ?>
                            </div>

                            <?php foreach ($linha as $nome_coluna => $valor): ?>
                                <?php if ($nome_coluna !== $coluna_id && $nome_coluna !== $coluna_titulo && $nome_coluna !== 'deleted_at'): ?>
                                    <div class="card-field">
                                        <strong><?= htmlspecialchars($nome_coluna); ?></strong>
                                        <span>
                                            <?php
                                            if (is_string($valor) && preg_match('~[^\x20-\x7E\t\r\n\x80-\xFF]~', $valor)) {
                                                echo '📍 <code style="color:var(--primary);">HEX:' . bin2hex($valor) . '</code>';
                                            } else {
                                                echo htmlspecialchars($valor ?? '-');
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

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
            <div style="text-align: center; padding: 3rem; background: var(--surface); border-radius: 8px; color: var(--text-light);">
                <p style="font-size: 1.2rem;">Nenhum dado encontrado (ou todos estão na lixeira).</p>
            </div>
        <?php endif; ?>
    </div>

    <button id="btnVoltarTopo" onclick="voltarAoTopo()" title="Voltar ao topo">⬆️</button>

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

        const btnTopo = document.getElementById("btnVoltarTopo");
        window.addEventListener('scroll', function() {
            if (window.scrollY > 40) {
                btnTopo.classList.add("mostrar");
            } else {
                btnTopo.classList.remove("mostrar");
            }
        });

        function voltarAoTopo() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>