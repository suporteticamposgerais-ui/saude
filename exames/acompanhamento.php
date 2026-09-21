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
            background: #f0f4f8;
            margin: 0
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px
        }

        .titulo-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            margin-bottom: 20px;
        }

        h2 {
            text-align: left;
            color: #2a5298;
            margin: 0;
            flex: 1;
        }

        .btn-fila {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 10px;
            background: #e8f5e9;
            color: #175e2a;
            border: 1px solid #a7f3d0;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            margin-left: auto;
            font-size: 14px;
            line-height: 1.2;
        }

        .btn-fila:hover {
            background: #d1fae5;
            text-decoration: none;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px
        }

        .status {
            font-weight: bold;
            color: #065f46
        }

        small {
            color: #555
        }

        a {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #2a5298;
            text-decoration: none
        }

        .btn-docs {
            margin-top: 10px;
            padding: 8px 12px;
            border: none;
            background: #2a5298;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-docs:hover {
            background: #1e3c72;
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
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-filtro {
            padding: 8px 14px;
            border-radius: 20px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            cursor: pointer;
            font-size: 14px;
            transition: .2s;
        }

        .btn-filtro:hover {
            background: #e2e8f0;
        }

        .btn-filtro.ativo {
            background: #2a5298;
            color: #fff;
            border-color: #2a5298;
        }
    </style>
</head>

<body>
  
    <div class="filtros container">
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

    <!-- MODAL -->
    <div class="modal" id="modalDocs">
        <div class="modal-content">
            <span class="close" onclick="fecharModal()">×</span>
            <h3 id="modalTitulo"></h3>
            <ul class="lista-docs" id="listaDocs"></ul>
        </div>
    </div>

    <div class="container">
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

        <a href="logout.php">Sair</a>
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