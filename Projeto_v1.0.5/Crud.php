<?php
// ==========================================
// ARQUIVO CENTRAL DE AÇÕES (CONTROLLER)
// ==========================================
require_once('../database.php');

// Adicione esta função ao seu crud.php
function tabelaExiste($pdo, $tabela)
{
    try {
        $pdo->query("DESCRIBE `$tabela`");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

$acao = filter_input(INPUT_GET, 'acao', FILTER_DEFAULT);
$nome_tabela = filter_input(INPUT_GET, 'tabela', FILTER_DEFAULT);
$id_registro = filter_input(INPUT_GET, 'id', FILTER_DEFAULT);
$origem = filter_input(INPUT_GET, 'origem', FILTER_DEFAULT);

if (!$acao || !$nome_tabela) die("Erro: Ação ou tabela não informadas.");
$acoes_com_id = ['ver', 'editar', 'salvar', 'excluir', 'restaurar', 'destruir'];
if (in_array($acao, $acoes_com_id) && !$id_registro) die("Erro: ID não informado.");

$pdo = Conectar_DB();
$url_retorno = ($origem === 'lixeira') ? "Lixeira.php?nome=" . urlencode($nome_tabela) : "Pagina-Tabela.php?nome=" . urlencode($nome_tabela);

switch ($acao) {
    case 'ver':
        verDetalhes($pdo, $nome_tabela, $id_registro, $url_retorno);
        break;
    case 'editar':
        renderizarFormulario($pdo, $nome_tabela, $id_registro, $url_retorno);
        break;
    case 'salvar':
        salvarEdicao($pdo, $nome_tabela, $id_registro, $url_retorno);
        break;
    case 'excluir':
        excluirRegistro($pdo, $nome_tabela, $id_registro, $url_retorno);
        break;
    case 'restaurar':
        restaurarRegistro($pdo, $nome_tabela, $id_registro, $url_retorno);
        break;
    case 'destruir':
        destruirRegistro($pdo, $nome_tabela, $id_registro, $url_retorno);
        break;
    case 'adicionar':
        renderizarFormulario($pdo, $nome_tabela, null, $url_retorno);
        break;
    case 'inserir':
        inserirRegistro($pdo, $nome_tabela, $url_retorno);
        break;
    default:
        die("Erro: Ação desconhecida.");
}

// ------------------------------------------
// FUNÇÃO: VER DETALHES
// ------------------------------------------
function verDetalhes($pdo, $tabela, $id, $url_retorno)
{
    $coluna_id = pegarColunaID($pdo, $tabela);
    $stmt = $pdo->prepare("SELECT * FROM `$tabela` WHERE `$coluna_id` = :id");
    $stmt->execute(['id' => $id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) die("Registo não encontrado.");
    $seed = md5($tabela . $id);
?>
    <div style="max-width:900px; margin:2rem auto; padding:2rem; background:white; border-radius:12px; font-family:sans-serif; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);">
        <img src="https://picsum.photos/seed/<?= $seed ?>/900/250" style="width:100%; border-radius:8px; margin-bottom:1rem;">
        <h1>Detalhes: <?= ucfirst($tabela) ?> #<?= $id ?></h1>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-top:1rem;">
            <?php foreach ($registro as $k => $v): ?>
                <div style="background:#f8fafc; padding:1rem; border-radius:8px;">
                    <strong style="display:block; color:#64748b; font-size:0.8rem;"><?= strtoupper($k) ?></strong>
                    <p><?= htmlspecialchars($v ?? '-') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="<?= $url_retorno ?>" style="display:inline-block; margin-top:2rem; padding:0.7rem 1.5rem; background:#4f46e5; color:white; border-radius:6px; text-decoration:none;">Voltar</a>
    </div>
<?php
}
function mapearTabelaPai($campo)
{
    // Mapeamento explícito para erros comuns do Sakila
    $map = [
        'manager_staff_id' => 'staff',
        'customer_id'      => 'customer',
        'address_id'       => 'address',
        'city_id'          => 'city',
        'country_id'       => 'country'
    ];

    if (isset($map[$campo])) return $map[$campo];

    // Se não estiver no mapa, tenta adivinhar, mas de forma segura
    $tabela = str_replace('_id', '', $campo);
    return $tabela;
}

// ------------------------------------------
// FUNÇÃO ÚNICA: FORMULÁRIO (ADD/EDIT)
// ------------------------------------------
function renderizarFormulario($pdo, $tabela, $id = null, $url_retorno)
{
    $is_edit = ($id !== null);
    $coluna_id = pegarColunaID($pdo, $tabela);
    $registro = [];

    if ($is_edit) {
        $stmt = $pdo->prepare("SELECT * FROM `$tabela` WHERE `$coluna_id` = :id");
        $stmt->execute(['id' => $id]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $stmt_cols = $pdo->query("DESCRIBE `$tabela`");
    $colunas = $stmt_cols->fetchAll(PDO::FETCH_ASSOC);

    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>';
?>
    <div style="max-width: 1000px; margin: 2rem auto; display: flex; gap: 2rem; align-items: flex-start; padding: 0 1rem; font-family: sans-serif;">

        <div style="flex: 2; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h1><?= $is_edit ? "✏️ Editar Registo #$id" : "➕ Adicionar Novo Registo" ?></h1>
            <form action="crud.php?acao=<?= $is_edit ? 'salvar&id=' . $id : 'inserir' ?>&tabela=<?= urlencode($tabela) ?>&origem=<?= filter_input(INPUT_GET, 'origem') ?>" method="POST">
                <?php foreach ($colunas as $col):
                    $nome = $col['Field'];
                    $val_atual = isset($registro[$nome]) ? $registro[$nome] : '';
                    $eh_automatico = ($col['Extra'] == 'auto_increment' || strpos($col['Default'], 'CURRENT_TIMESTAMP') !== false);
                    $eh_protegido = in_array($nome, [$coluna_id, 'deleted_at', 'last_update']);
                    if ($eh_automatico || $eh_protegido) continue;

                    $opcoes = [];
                    if (strpos($nome, '_id') !== false) {
                        $tabela_pai = mapearTabelaPai($nome);
                        if (tabelaExiste($pdo, $tabela_pai)) {
                            $coluna_display = descobrirColunaDisplay($pdo, $tabela_pai);
                            $opcoes = obterOpcoesSelect($pdo, $tabela_pai, $nome, $coluna_display);
                        }
                    }
                ?>
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; font-weight:bold; margin-bottom:0.3rem;"><?= $nome ?></label>
                        <?php if (!empty($opcoes)): ?>
                            <select name="<?= $nome ?>" class="choices-select" style="width:100%;">
                                <?php foreach ($opcoes as $opt): ?>
                                    <option value="<?= $opt['id'] ?>" <?= ($val_atual == $opt['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opt['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="<?= $nome ?>" value="<?= htmlspecialchars($val_atual) ?>" style="width:100%; padding:0.6rem; border:1px solid #ccc; border-radius:4px;">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" style="background:#4f46e5; color:white; padding:0.7rem 1.5rem; border:none; border-radius:6px; cursor:pointer;">Gravar</button>
                <a href="<?= $url_retorno ?>" style="margin-left:1rem; color:#64748b;">Cancelar</a>
            </form>
        </div>

        <div style="flex: 1; background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; position: sticky; top: 1rem;">
            <h3 style="margin-top:0;">⚙️ Estrutura da Tabela</h3>
            <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 1rem;">Use estes dados para preencher o formulário:</p>
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 4px 0;">Coluna</th>
                        <th style="padding: 4px 0;">Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($colunas as $coluna): ?>
                        <tr>
                            <td style="padding: 6px 0; border-bottom: 1px solid #e2e8f0; font-weight:500;"><?= htmlspecialchars($coluna['Field']); ?></td>
                            <td style="padding: 6px 0; border-bottom: 1px solid #e2e8f0;"><code><?= htmlspecialchars($coluna['Type']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        document.querySelectorAll('.choices-select').forEach(el => {
            new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                searchPlaceholderValue: 'Pesquisar...'
            });
        });
    </script>
<?php
}

// ------------------------------------------
// FUNÇÕES AUXILIARES
// ------------------------------------------
function pegarColunaID($pdo, $tabela)
{
    $stmt = $pdo->query("SHOW KEYS FROM `$tabela` WHERE Key_name = 'PRIMARY'");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) return $res['Column_name'];

    // Se não achar Primary Key, tenta procurar uma coluna chamada 'id' ou '[tabela]_id'
    $cols = $pdo->query("DESCRIBE `$tabela`")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('id', $cols)) return 'id';
    if (in_array($tabela . '_id', $cols)) return $tabela . '_id';

    return null; // Retorna nulo se não encontrar nada
}

function descobrirColunaDisplay($pdo, $tabela)
{
    try {
        $stmt = $pdo->query("DESCRIBE `$tabela`");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lista otimizada para os nomes comuns no Sakila
        $possiveis_colunas = ['name', 'title', 'first_name', 'country', 'city', 'last_name', 'email', 'description'];

        foreach ($cols as $c) {
            // Verifica se o nome da coluna está na nossa lista de "inteligência"
            if (in_array($c['Field'], $possiveis_colunas)) {
                return $c['Field'];
            }
        }

        // Fallback: Se não encontrar nada na lista, tenta usar a 2ª coluna da tabela
        // (Geralmente a 1ª é o ID e a 2ª é o Nome/Título em bancos bem estruturados)
        return isset($cols[1]) ? $cols[1]['Field'] : $cols[0]['Field'];
    } catch (Exception $e) {
        return 'id';
    }
}

// ------------------------------------------
// FUNÇÃO AUXILIAR: Busca dados para Selects (Deduplicada)
// ------------------------------------------
function obterOpcoesSelect($pdo, $tabela_origem, $coluna_valor, $coluna_texto)
{
    // Usamos MIN(`id`) para garantir que pegamos um ID válido, 
    // e GROUP BY para agrupar todos os nomes iguais.
    $sql = "SELECT MIN(`$coluna_valor`) as id, TRIM(`$coluna_texto`) as nome 
        FROM `$tabela_origem` 
        GROUP BY TRIM(`$coluna_texto`) 
        ORDER BY nome ASC";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


// ------------------------------------------
// FUNÇÕES DE AÇÃO
// ------------------------------------------
function inserirRegistro($pdo, $tabela, $url)
{
    $colunas = $pdo->query("DESCRIBE `$tabela`")->fetchAll(PDO::FETCH_COLUMN);
    $dados = array_intersect_key($_POST, array_flip($colunas));
    $sql = "INSERT INTO `$tabela` (`" . implode('`, `', array_keys($dados)) . "`) VALUES (:" . implode(', :', array_keys($dados)) . ")";
    $pdo->prepare($sql)->execute($dados);
    header("Location: $url");
    exit;
}

function salvarEdicao($pdo, $tabela, $id, $url)
{
    $col_id = pegarColunaID($pdo, $tabela);
    $colunas = $pdo->query("DESCRIBE `$tabela`")->fetchAll(PDO::FETCH_COLUMN);
    $dados = array_intersect_key($_POST, array_flip($colunas));
    $sets = [];
    foreach ($dados as $k => $v) $sets[] = "`$k` = :$k";
    $sql = "UPDATE `$tabela` SET " . implode(', ', $sets) . " WHERE `$col_id` = :id";
    $dados['id'] = $id;
    $pdo->prepare($sql)->execute($dados);
    header("Location: $url");
    exit;
}

function excluirRegistro($pdo, $tabela, $id, $url)
{
    $col_id = pegarColunaID($pdo, $tabela);
    $pdo->prepare("UPDATE `$tabela` SET `deleted_at` = NOW() WHERE `$col_id` = :id")->execute(['id' => $id]);
    header("Location: $url");
    exit;
}

function restaurarRegistro($pdo, $tabela, $id, $url)
{
    $col_id = pegarColunaID($pdo, $tabela);
    $pdo->prepare("UPDATE `$tabela` SET `deleted_at` = NULL WHERE `$col_id` = :id")->execute(['id' => $id]);
    header("Location: $url");
    exit;
}

function destruirRegistro($pdo, $tabela, $id, $url)
{
    $col_id = pegarColunaID($pdo, $tabela);
    $pdo->prepare("DELETE FROM `$tabela` WHERE `$col_id` = :id")->execute(['id' => $id]);
    header("Location: $url");
    exit;
}
?>


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