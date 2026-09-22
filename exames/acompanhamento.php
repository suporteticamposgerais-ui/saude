<?php
/* =========================
   ARQUIVO: acompanhamento.php
   ========================= */
session_start();
require 'conexao.php';

if (!isset($_SESSION['login_tipo']) && !isset($_SESSION['telefone'])) {
    header('Location: login.php');
    exit;
}

$tipoLogin = $_SESSION['login_tipo'] ?? 'telefone';
$valorLogin = $_SESSION['login_valor'] ?? ($_SESSION['telefone'] ?? '');

if ($tipoLogin === 'telefone') {
    $valorLogin = preg_replace('/\D/', '', $valorLogin);
    $campo = 'telefone';
} elseif ($tipoLogin === 'email') {
    $valorLogin = strtolower(trim($valorLogin));
    $campo = 'email';
} else {
    $valorLogin = preg_replace('/\D/', '', $valorLogin);
    $campo = 'cartao_sus';
}

$stmt = $pdo->prepare("SELECT * FROM pedidos_exames WHERE {$campo} = ? ORDER BY id DESC");
$stmt->execute([$valorLogin]);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca documentos
$stmtDocs = $pdo->query("
    SELECT pedido_id, titulo_documento, arquivo
    FROM documentos_exames
");

$docsPorPedido = [];
while ($doc = $stmtDocs->fetch(PDO::FETCH_ASSOC)) {
    $docsPorPedido[$doc['pedido_id']][] = $doc;
}


?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhamento</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f8;
            margin: 0;
        }

        .container {
            max-width: 980px;
            margin: 24px auto 32px;
            padding: 0 16px;
        }

        .content-wrap {
            padding: 0 18px 20px;
        }

        .card-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        }

        .card-header {
            background: linear-gradient(135deg, #0d6efd, #084298);
            color: #fff;
            padding: 18px 20px;
            font-size: 21px;
            font-weight: 600;
            text-align: center;
        }

        .titulo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin: 18px 0 16px;
        }

        h2 {
            text-align: left;
            color: #2a5298;
            margin: 0;
            flex: 1;
            font-size: 26px;
            font-weight: 700;
        }

        .btn-fila {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border-radius: 12px;
            background: #e8f5e9;
            color: #175e2a;
            border: 1px solid #a7f3d0;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            margin-left: auto;
            font-size: 14px;
            line-height: 1.2;
            min-height: 42px;
            height: 42px;
        }

        .btn-fila:hover {
            background: #d1fae5;
            text-decoration: none;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 16px;
            background: #fff;
        }

        .status {
            font-weight: bold;
            color: #065f46;
            display: inline-block;
            margin-top: 10px;
        }

        small {
            color: #555;
        }

        a {
            color: #2a5298;
            text-decoration: none;
        }

        .btn-docs {
            margin-top: 10px;
            padding: 8px 12px;
            border: none;
            background: #2a5298;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-docs:hover {
            background: #1e3c72;
        }

        .link-sair {
            display: block;
            text-align: center;
            margin-top: 18px;
            font-weight: 600;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .6);
            z-index: 999;
        }

        .modal-content {
            background: #fff;
            max-width: 600px;
            margin: 10% auto;
            padding: 20px;
            border-radius: 12px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 22px;
            cursor: pointer;
        }

        .lista-docs {
            list-style: none;
            padding: 0;
        }

        .lista-docs li {
            background: #f1f5f9;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .hr-fade-right {
            border: none;
            height: 0.5px;
            background: linear-gradient(to right,
                    #94a3b8 0%,
                    #cbd5e1 1%,
                    transparent 100%);
        }

        .filtros {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 18px auto 22px;
            flex-wrap: nowrap;
            width: 100%;
            max-width: 700px;
        }

        .btn-filtro {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 200px;
            height: 52px;
            padding: 0 18px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            cursor: pointer;
            font-size: 16px;
            transition: .2s;
            color: #2d2d2d;
            font-weight: 500;
        }

        .btn-filtro:hover {
            background: #e2e8f0;
        }

        .btn-filtro.ativo {
            background: #2a5298;
            color: #fff;
            border-color: #2a5298;
        }

        @media (max-width: 767px) {
            .container {
                padding: 0 10px;
                margin-top: 12px;
            }

            .content-wrap {
                padding: 0 12px 14px;
            }

            .card-box {
                border-radius: 10px;
            }

            .card-header {
                padding: 16px 18px;
                font-size: 18px;
            }

            .titulo-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 10px;
                margin: 14px 0 12px;
            }

            h2 {
                text-align: center;
                font-size: 22px;
                width: 100%;
            }

            .btn-fila {
                width: 100%;
                white-space: normal;
                margin-left: 0;
                font-size: 13px;
                min-height: 40px;
                height: 40px;
            }

            .filtros {
                gap: 8px;
                flex-wrap: wrap;
                max-width: 100%;
                margin: 12px auto 14px;
            }

            .btn-filtro {
                min-width: 0;
                width: calc(50% - 4px);
                height: 44px;
                font-size: 13px;
                padding: 0 8px;
            }

            .card {
                padding: 12px 10px;
                margin-bottom: 12px;
            }

            .btn-docs {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card-box">
            <div class="card-header">
                Pedido de Exame
            </div>

            <div class="filtros">
                <button class="btn-filtro ativo" onclick="filtrar('todos')">
                    Todos
                </button>
                <button class="btn-filtro" onclick="filtrar('baixa')">
                    🟢 Secretaria de Saúde
                </button>
                <button class="btn-filtro" onclick="filtrar('media')">
                    🔴 Policlínica
                </button>
            </div>

            <div class="modal" id="modalDocs">
                <div class="modal-content">
                    <span class="close" onclick="fecharModal()">×</span>
                    <h3 id="modalTitulo"></h3>
                    <ul class="lista-docs" id="listaDocs"></ul>
                </div>
            </div>

            <div class="content-wrap">
                <div class="titulo-header">
                    <h2>Suas Solicitações</h2>
                    <a href="acompanhamento_fila.php" class="btn-fila">Acompanhe sua posição na lista de espera</a>
                </div>

                <?php foreach ($dados as $d): ?>
                    <div class="card" data-complexidade="<?= strtolower($d['complexidade'] ?? 'baixa') ?>">
                        <strong><?= htmlspecialchars($d['nome_paciente']) ?></strong><br>
                        <small><?= htmlspecialchars($d['exame_solicitado']) ?></small><br><br>
                        <hr class="hr-fade-right">

                        📍 <?= htmlspecialchars($d['cartao_sus']) ?><br>
                        📞 <?= htmlspecialchars($d['telefone']) ?><br>

                        <span class="status">Status: <?= htmlspecialchars($d['status']) ?></span>
                        <br>
                        <hr>
                        <button class="btn-docs" onclick="abrirModal(<?= $d['id'] ?>)">
                            📎 Ver documentos
                        </button>
                    </div>
                <?php endforeach; ?>

                <a href="logout.php" class="link-sair">Sair</a>
            </div>
        </div>
    </div>
    <script>
        const documentos = <?= json_encode($docsPorPedido, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script>
        function abrirModal(pedidoId) {
            const modal = document.getElementById('modalDocs');
            const lista = document.getElementById('listaDocs');
            const titulo = document.getElementById('modalTitulo');

            lista.innerHTML = '';
            titulo.innerText = 'Documentos do pedido #' + pedidoId;

            if (documentos[pedidoId]) {
                documentos[pedidoId].forEach(doc => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                    📄 ${doc.titulo_documento}<br>
                    <a href="uploads/exames/${pedidoId}/${doc.arquivo}" target="_blank">
                        Abrir documento
                    </a>
                `;
                    lista.appendChild(li);
                });
            } else {
                lista.innerHTML = '<li>Nenhum documento anexado.</li>';
            }

            modal.style.display = 'block';
        }

        function fecharModal() {
            document.getElementById('modalDocs').style.display = 'none';
        }
    </script>

    <script>
        function filtrar(complexidade) {
            const cards = document.querySelectorAll('.card');
            const botoes = document.querySelectorAll('.btn-filtro');

            botoes.forEach(b => b.classList.remove('ativo'));
            event.target.classList.add('ativo');

            cards.forEach(card => {
                const valor = (card.dataset.complexidade || 'baixa').toLowerCase();

                if (complexidade === 'todos' || valor === complexidade) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>


</body>

</html>