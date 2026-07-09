<?php
require_once('../database.php');
$db_pdo = Conectar_DB();

$sql = "SHOW TABLES";
$stmt = $db_pdo->query($sql);
$tabelas = $stmt->fetchAll(PDO::FETCH_NUM);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu de Tabelas</title>
    <style>
        * {
            list-style: none;
            text-decoration: none;
            color: black;
            padding: 0;
            border: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f8fafc;
            padding: 2rem;
        }

        /* O Container agora usa Grid para alinhar os dropdowns lado a lado sem quebrar */
        .container-dropdowns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
            align-items: start;
        }

        /* Estilizando o Dropdown Nativo (<details name="menus">) */
        details {
            background-color: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        /* Estilizando o Título Clicável (<summary>) */
        summary {
            font-size: 1.25rem;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid transparent;
        }

        /* Efeito de separador quando o dropdown está aberto */
        details[open] summary {
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 12px;
        }

        /* Estilizando a lista (ul e li) dentro do dropdown */
        details ul {
            padding-left: 8px;
        }

        details ul li {
            list-style: circle;
            margin-left: 20px;
            margin-bottom: 10px;
            transition: 0.2s ease-in;
        }

        details ul li:hover {
            transform: scale(1.02);
        }

        details ul li a {
            font-size: 1.04rem;
            letter-spacing: 0.6px;
            color: #1e293b;
            transition: all 0.2s ease-in;
        }

        details ul li a:hover {
            color: #4f46e5;
            border-bottom: 1px solid #4f46e5;
        }

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
    <div class="container-dropdowns">
        <ul>
            <h2>Tabelas de como deveria ser feitas com ia em Versões</h2>
            <details name="menus">
                <summary>1.0.1 - Table</summary>
                <ul>
                    <?php
                    foreach ($tabelas as $linha) {
                        $nome_tabela = $linha[0];
                        echo '<li><a href="pagina_tabela.php?nome=' . urlencode($nome_tabela) . '">' . $nome_tabela . '</a></li>';
                    }
                    ?>
                </ul>
            </details>
            <details name="menus">
                <summary>1.0.2 - Table</summary>
                <ul>
                    <?php
                    foreach ($tabelas as $linha) {
                        $nome_tabela = $linha[0];
                        echo '<li><a href="pagina_tabela_2.php?nome=' . urlencode($nome_tabela) . '">' . $nome_tabela . '</a></li>';
                    }
                    ?>
                </ul>
            </details>
            <details name="menus">
                <summary>1.0.3 - Table</summary>
                <ul>
                    <?php
                    foreach ($tabelas as $linha) {
                        $nome_tabela = $linha[0];
                        echo '<li><a href="pagina_tabela_3.php?nome=' . urlencode($nome_tabela) . '">' . $nome_tabela . '</a></li>';
                    }
                    ?>
                </ul>
            </details>
            <details name="menus">
                <summary>1.0.4 - Cards</summary>
                <ul>
                    <?php
                    foreach ($tabelas as $linha) {
                        $nome_tabela = $linha[0];
                        // Corrigido para pagina_tabela_4.php
                        echo '<li><a href="pagina_tabela_4.php?nome=' . urlencode($nome_tabela) . '">' . $nome_tabela . '</a></li>';
                    }
                    ?>
                </ul>
            </details>
            <details name="menus">
                <summary>1.0.5 - Cards</summary>
                <ul>
                    <?php
                    foreach ($tabelas as $linha) {
                        $nome_tabela = $linha[0];
                        // Corrigido para pagina_tabela_4.php
                        echo '<li><a href="pagina_tabela_5.php?nome=' . urlencode($nome_tabela) . '">' . $nome_tabela . '</a></li>';
                    }
                    ?>
                </ul>
            </details>
        </ul>
    </div>
    <button id="btnVoltarTopo" onclick="voltarAoTopo()" title="Voltar ao topo">
        ⬆️
    </button>

    <script>
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