<?php
// lixeira.php - Quase idêntico à vitrine, mas com lógica inversa!
include_once('../database.php');

$nome_tabela = filter_input(INPUT_GET, 'nome', FILTER_DEFAULT);
$termo_busca = filter_input(INPUT_GET, 'busca', FILTER_DEFAULT);

if (!$nome_tabela) {
    die("Erro: Informe o nome da tabela na URL.");
}

$db_pdo = Conectar_DB();

$stmt_tabelas = $db_pdo->query("SHOW TABLES");
$tabelas_permitidas = $stmt_tabelas->fetchAll(PDO::FETCH_COLUMN);

if (!in_array($nome_tabela, $tabelas_permitidas)) {
    die("Tabela inválida.");
}

$sql_estrutura = "DESCRIBE `$nome_tabela`";
$stmt = $db_pdo->prepare($sql_estrutura);
$stmt->execute();
$colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$where_sql = "";
$parametros_busca = [];
$condicoes_where = [];

// =======================================================
// A MÁGICA DA LIXEIRA ACONTECE AQUI
// =======================================================
$tem_lixeira = false;
foreach ($colunas as $coluna) {
    if ($coluna['Field'] === 'deleted_at') {
        $tem_lixeira = true;
        break;
    }
}

if ($tem_lixeira) {
    // INVERSÃO DA LÓGICA: Queremos apenas quem NÃO É nulo!
    $condicoes_where[] = "`deleted_at` IS NOT NULL";
} else {
    die("<div style='padding: 2rem; font-family: sans-serif; text-align: center;'><h2>⚠️ Esta tabela não possui o sistema de Lixeira.</h2><a href='Pagina-Tabela.php?nome=" . urlencode($nome_tabela) . "'>Voltar para Vitrine</a></div>");
}

// Aplica a pesquisa (Igual a vitrine)
if (!empty($termo_busca)) {
    $condicoes_busca = [];
    $contador = 0;
    foreach ($colunas as $coluna) {
        $nome_campo = $coluna['Field'];
        $tipo_campo = strtolower($coluna['Type']);

        // Ignora campos geométricos na pesquisa
        if (strpos($tipo_campo, 'geometry') === false && strpos($tipo_campo, 'point') === false) {
            $nome_parametro = ":busca_" . $contador;
            $condicoes_busca[] = "`$nome_campo` LIKE " . $nome_parametro;
            $parametros_busca[$nome_parametro] = "%" . $termo_busca . "%";
            $contador++;
        }
    }
    if (!empty($condicoes_busca)) {
        $condicoes_where[] = "(" . implode(" OR ", $condicoes_busca) . ")";
    }
}

if (!empty($condicoes_where)) {
    $where_sql = " WHERE " . implode(" AND ", $condicoes_where);
}

$itens_por_pagina = 12;
$pagina_atual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?: 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$sql_total = "SELECT COUNT(*) as total FROM `$nome_tabela`" . $where_sql;
$stmt_total = $db_pdo->prepare($sql_total);
if (!empty($parametros_busca)) {
    foreach ($parametros_busca as $parametro => $valor) {
        $stmt_total->bindValue($parametro, $valor, PDO::PARAM_STR);
    }
}
$stmt_total->execute();
$total_paginas = ceil($stmt_total->fetch(PDO::FETCH_ASSOC)['total'] / $itens_por_pagina) ?: 1;

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

$sql_dados = "SELECT $lista_campos FROM `$nome_tabela` $where_sql LIMIT :limite OFFSET :offset";
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
    <title>Lixeira - <?= htmlspecialchars($nome_tabela); ?></title>
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
            --background: #f8fafc;
            --surface: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --danger: #ef4444;
            --danger-hover: #dc2626;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--background);
            color: var(--text);
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .div-menu {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        h2 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge {
            background-color: var(--danger);
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
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15);
        }

        .card-id-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background-color: var(--danger);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.9rem;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* FILTRO PRETO E BRANCO NA FOTO */
        .card-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid var(--border);
            background-color: #f1f5f9;
            filter: grayscale(100%);
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
            color: var(--danger);
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

        /* OVERLAY E BOTÕES DA LIXEIRA */
        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.85);
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
            color: white;
        }
        .btn-view {
            background-color: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }

        .btn-view:hover {
            background-color: #4338ca;
            transform: scale(1.05);
        }

        .btn-restore {
            background-color: #10b981;
            border: 1px solid #10b981;
        }

        .btn-restore:hover {
            background-color: #059669;
            transform: scale(1.05);
        }

        .btn-destroy {
            background-color: #991b1b;
            border: 1px solid #991b1b;
        }

        .btn-destroy:hover {
            background-color: #7f1d1d;
            transform: scale(1.05);
        }

        /* PAGINAÇÃO */
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
            background-color: #4338ca;
            transform: scale(1.1);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="div-menu">
            <h2>🗑️ Lixeira: <span class="badge"><?= htmlspecialchars($nome_tabela); ?></span></h2>
            <a href="Pagina-Tabela.php?nome=<?= urlencode($nome_tabela); ?>">
                <span class="badge" style="background-color: var(--primary);">⬅ Voltar para Vitrine</span>
            </a>
        </div>

        <div style="background: var(--surface); padding: 1rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center;">
            <form method="GET" action="" style="display: flex; gap: 0.5rem; width: 100%;">
                <input type="hidden" name="nome" value="<?= htmlspecialchars($nome_tabela); ?>">
                <input type="text" name="busca" value="<?= htmlspecialchars($termo_busca ?? ''); ?>"
                    placeholder="Pesquisar nos itens excluídos..."
                    style="flex-grow: 1; padding: 0.6rem 1rem; border: 1px solid var(--border); border-radius: 6px; outline: none; font-size: 0.95rem; background: var(--background);">
                <button type="submit" style="background: var(--danger); color: white; padding: 0.6rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    🔍 Buscar na Lixeira
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
                            <a href="Crud.php?acao=restaurar&tabela=<?= urlencode($nome_tabela); ?>&id=<?= urlencode($valor_id); ?>&origem=lixeira" class="btn-action btn-restore">
                                ♻️ Restaurar
                            </a>
                            <a href="Crud.php?acao=destruir&tabela=<?= urlencode($nome_tabela); ?>&id=<?= urlencode($valor_id); ?>&origem=lixeira"
                                class="btn-action btn-destroy"
                                onclick="return confirm('⚠️ ATENÇÃO! Essa ação não pode ser desfeita. Excluir definitivamente?');">
                                ☠️ Excluir Definitivamente
                            </a>
                        </div>

                        <img src="https://picsum.photos/seed/<?= $seed ?>/400/200" alt="Imagem Excluída" class="card-img" loading="lazy">

                        <div class="card-body">
                            <div class="card-title">
                                <del><?= htmlspecialchars($coluna_titulo); ?>: <?= htmlspecialchars($valor_titulo); ?></del>
                            </div>

                            <div class="card-field" style="margin-bottom: 0.5rem;">
                                <strong>Data de Exclusão:</strong>
                                <span style="color: var(--danger); font-weight: 600;">
                                    <?= date('d/m/Y H:i', strtotime($linha['deleted_at'])); ?>
                                </span>
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
                <p style="font-size: 1.2rem;">A lixeira está vazia ou nenhum item foi encontrado.</p>
            </div>
        <?php endif; ?>
    </div>

    <button id="btnVoltarTopo" onclick="voltarAoTopo()" title="Voltar ao topo">⬆️</button>

    <script>
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