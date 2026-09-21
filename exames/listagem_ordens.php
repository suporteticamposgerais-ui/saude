<?php

require 'conexao.php';

function normalizarCategoriaPedido($valor) {
    $valor = trim((string) $valor);
    $valor = preg_replace('/\s+/', ' ', $valor);
    $valorLower = strtolower($valor);

    if (stripos($valorLower, 'consulta') !== false) {
        return 'Consulta';
    }

    if (stripos($valorLower, 'exame') !== false) {
        return 'Exame';
    }

    return 'Outro';
}

function normalizarEspecialidade($valor) {
    $valor = trim((string) $valor);
    $valor = preg_replace('/\s+/', ' ', $valor);

    if (preg_match('/^(Consulta|Exame)\s*[-–]\s*(.+)$/i', $valor, $m)) {
        return trim($m[2]);
    }

    return $valor;
}

/************************************************
 * listagem_ordens.php
 * - Lista os pedidos com filtro por categoria e especialidade/exame
 * - Mantém o status do pedido e ação de emergência
 ************************************************/
$totais = [
    'Solicitação recebida' => 0,
    'Visualizado' => 0,
    'Em andamento' => 0,
    'Finalizado' => 0
];

$sqlTotais = "
    SELECT status, COUNT(*) as total
    FROM pedidos_exames
    GROUP BY status
";
$stmtTotais = $pdo->query($sqlTotais);

while ($row = $stmtTotais->fetch(PDO::FETCH_ASSOC)) {
    $totais[$row['status']] = $row['total'];
}

// Ações
if (isset($_GET['atender'])) {
    $id = (int) $_GET['atender'];
    $pdo->prepare(
        "UPDATE pedidos_exames 
         SET status='Em andamento', robo=0 
         WHERE id=? and status <> 'Finalizado'"
    )->execute([$id]);
}

if (isset($_GET['finalizar'])) {
    $id = (int) $_GET['finalizar'];
    $pdo->prepare(
        "UPDATE pedidos_exames 
         SET status='Finalizado', robo=0 
         WHERE id=? and status <> 'Finalizado'"
    )->execute([$id]);
}

if (isset($_GET['marcar_emergencia'])) {
    $id = (int) $_GET['marcar_emergencia'];
    $pdo->prepare(
        "UPDATE pedidos_exames 
         SET emergencia=1, prioridade=1 
         WHERE id=?"
    )->execute([$id]);
}

if (isset($_GET['desmarcar_emergencia'])) {
    $id = (int) $_GET['desmarcar_emergencia'];
    $pdo->prepare(
        "UPDATE pedidos_exames 
         SET emergencia=0, prioridade=2 
         WHERE id=?"
    )->execute([$id]);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/brasao.jpg" type="image/jpeg">
    <title>Ordens de Serviço</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f8;
            padding: 20px
        }

        .box {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .1)
        }

        .header {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .titulo {
            font-weight: bold;
            color: #2a5298
        }

        .detalhes {
            display: none;
            margin-top: 15px;
            font-size: 14px;
            border-top: 1px solid #eee;
            padding-top: 10px
        }

        button {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer
        }

        .btn-view {
            background: #e2e8f0
        }

        .btn-finalizar {
            background: #2a5298;
            color: #fff
        }

        .btn-atender {
            background: #39982a;
            color: #fff;
        }

        .btn-emergencia {
            background: #dc2626;
            color: #fff;
        }

        .btn-desmarcar-emergencia {
            background: #f59e0b;
            color: #fff;
        }

        .box.status-em-andamento {
            background-color: aqua;
        }

        .box.status-visualizado {
            background-color: #e0e082;
        }

        .box.status-solicitação-recebida {
            background-color: lightcoral;
        }

        .box.status-finalizado {
            background-color: lightgray;
        }

        .resumo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .resumo-box {
            padding: 15px;
            border-radius: 10px;
            color: #333;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .1);
            text-align: center;
        }

        .resumo-box span {
            display: block;
            font-size: 28px;
            margin-top: 5px;
        }

        .resumo-solicitacao {
            background: lightcoral;
        }

        .resumo-visualizado {
            background: #e0e082;
        }

        .resumo-andamento {
            background: aqua;
        }

        .resumo-finalizado {
            background: lightgray;
        }
    </style>

    <script>
        function toggle(id) {
            const el = document.getElementById('det_' + id);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
    </script>
    <style>
        /* =============================
   FILTROS
============================= */

        .filtro-box {
            background: #ffffff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .filtro-box form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .filtro-box select,
        .filtro-box input {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            min-width: 160px;
            background: #fff;
        }

        .filtro-box input {
            flex: 1;
            min-width: 220px;
        }

        .filtro-box button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s;
        }

        /* botão filtrar */

        .btn-filtrar {
            background: #2a5298;
            color: #fff;
        }

        .btn-filtrar:hover {
            background: #1d3f7a;
        }

        /* botão limpar */

        .btn-limpar {
            background: #e2e8f0;
        }

        .btn-limpar:hover {
            background: #cbd5e1;
        }

        /* =============================
   BADGES DE CATEGORIA
============================= */

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-consulta {
            background: #c7f9cc;
            color: #1b5e20;
        }

        .badge-exame {
            background: #d0e7ff;
            color: #0d47a1;
        }

        /* =============================
   RESPONSIVO
============================= */

        @media (max-width:700px) {

            .filtro-box form {
                flex-direction: column;
                align-items: stretch;
            }

            .filtro-box select,
            .filtro-box input,
            .filtro-box button {
                width: 100%;
            }

        }
    </style>
</head>

<body>
    <!-- Modal de envio de mensagem -->
    <div id="modalMensagem" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
    background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
        <div style="background:#fff; padding:20px; border-radius:10px; width:400px; max-width:90%;">
            <h3>Enviar Mensagem</h3>
            <textarea id="mensagemTexto" style="width:100%; height:100px;"
                placeholder="Digite a mensagem..."> <?php echo $no ?></textarea>
            <div style="margin-top:10px; text-align:right;">
                <button onclick="fecharModal()">Cancelar</button>
                <button onclick="enviarMensagem()">Enviar</button>
            </div>
        </div>
    </div>

    <h2>Ordens de Serviço</h2>
    <div class="filtro-box">
        <form method="GET">

            <select name="complexidade" id="filtroComplexidade">
                <option value="">Unidade / Complexidade</option>
                <option value="baixa">Secretaria de Saúde</option>
                <option value="media">Policlínica</option>
                <option value="consulta">Consulta</option>
                <option value="exame">Exame</option>
                <option value="outro">Outro</option>
            </select>

            <select name="especialidade" id="filtroEspecialidade">
                <option value="">Especialidade / Exame</option>

                <!-- CONSULTAS -->

                <option data-tipo="Consulta">Clínica Geral</option>
                <option data-tipo="Consulta">Pediatria</option>
                <option data-tipo="Consulta">Ginecologia</option>
                <option data-tipo="Consulta">Obstetrícia</option>
                <option data-tipo="Consulta">Cardiologia</option>
                <option data-tipo="Consulta">Dermatologia</option>
                <option data-tipo="Consulta">Neurologia</option>
                <option data-tipo="Consulta">Ortopedia</option>
                <option data-tipo="Consulta">Oftalmologia</option>
                <option data-tipo="Consulta">Otorrinolaringologia</option>
                <option data-tipo="Consulta">Urologia</option>
                <option data-tipo="Consulta">Endocrinologia</option>
                <option data-tipo="Consulta">Gastroenterologia</option>
                <option data-tipo="Consulta">Pneumologia</option>
                <option data-tipo="Consulta">Reumatologia</option>
                <option data-tipo="Consulta">Oncologia</option>
                <option data-tipo="Consulta">Psiquiatria</option>
                <option data-tipo="Consulta">Psicologia</option>
                <option data-tipo="Consulta">Fonoaudiologia</option>
                <option data-tipo="Consulta">Fisioterapia</option>
                <option data-tipo="Consulta">Nutrição</option>
                <option data-tipo="Consulta">Terapia Ocupacional</option>
                <option data-tipo="Consulta">Assistência Social</option>

                <!-- EXAMES -->

                <option data-tipo="Exame">Hemograma completo</option>
                <option data-tipo="Exame">Glicemia de jejum</option>
                <option data-tipo="Exame">Hemoglobina glicada</option>
                <option data-tipo="Exame">Colesterol total</option>
                <option data-tipo="Exame">HDL</option>
                <option data-tipo="Exame">LDL</option>
                <option data-tipo="Exame">Triglicerídeos</option>
                <option data-tipo="Exame">Ureia</option>
                <option data-tipo="Exame">Creatinina</option>
                <option data-tipo="Exame">TGO</option>
                <option data-tipo="Exame">TGP</option>
                <option data-tipo="Exame">Ácido úrico</option>
                <option data-tipo="Exame">TSH</option>
                <option data-tipo="Exame">T4 Livre</option>
                <option data-tipo="Exame">PCR</option>

                <option data-tipo="Exame">EAS (Urina tipo 1)</option>
                <option data-tipo="Exame">Urocultura</option>
                <option data-tipo="Exame">Microalbuminúria</option>

                <option data-tipo="Exame">Parasitológico de fezes</option>
                <option data-tipo="Exame">Sangue oculto nas fezes</option>

                <option data-tipo="Exame">Raio-X</option>
                <option data-tipo="Exame">Ultrassonografia abdominal</option>
                <option data-tipo="Exame">Ultrassonografia pélvica</option>
                <option data-tipo="Exame">Ultrassonografia obstétrica</option>
                <option data-tipo="Exame">Ultrassonografia transvaginal</option>
                <option data-tipo="Exame">Mamografia</option>

                <option data-tipo="Exame">Eletrocardiograma</option>
                <option data-tipo="Exame">Teste ergométrico</option>
                <option data-tipo="Exame">Holter 24h</option>
                <option data-tipo="Exame">MAPA 24h</option>
                <option data-tipo="Exame">Ecocardiograma</option>

                <option data-tipo="Exame">Papanicolau</option>
                <option data-tipo="Exame">PSA total</option>
                <option data-tipo="Exame">PSA livre</option>

                <option data-tipo="Exame">HIV</option>
                <option data-tipo="Exame">Hepatite B</option>
                <option data-tipo="Exame">Hepatite C</option>
                <option data-tipo="Exame">VDRL</option>
                <option data-tipo="Exame">Dengue</option>
                <option data-tipo="Exame">Toxoplasmose</option>

            </select>

            <input type="text" name="busca" placeholder="Paciente ou exame">

            <button type="submit" class="btn-filtrar">Filtrar</button>

            <a href="listagem_ordens.php">
                <button type="button" class="btn-limpar">Limpar</button>
            </a>

        </form>
    </div>
    <div class="resumo">
        <div class="resumo-box resumo-solicitacao">
            Solicitação recebida
            <span><?= $totais['Solicitação recebida'] ?></span>
        </div>

        <div class="resumo-box resumo-visualizado">
            Visualizado
            <span><?= $totais['Visualizado'] ?></span>
        </div>

        <div class="resumo-box resumo-andamento">
            Em andamento
            <span><?= $totais['Em andamento'] ?></span>
        </div>

        <div class="resumo-box resumo-finalizado">
            Finalizado
            <span><?= $totais['Finalizado'] ?></span>
        </div>
    </div>

    <?php
    $where = [];
    $params = [];

    if (!empty($_GET['busca'])) {
        $where[] = "(nome_paciente LIKE ? OR exame_solicitado LIKE ?)";
        $params[] = "%" . $_GET['busca'] . "%";
        $params[] = "%" . $_GET['busca'] . "%";
    }

    $complexidadeFiltro = $_GET['complexidade'] ?? '';
    if (!empty($complexidadeFiltro)) {
        $complexidadeFiltro = strtolower(trim($complexidadeFiltro));

        if ($complexidadeFiltro === 'baixa' || $complexidadeFiltro === 'media') {
            $where[] = "(LOWER(complexidade) = ? OR LOWER(complexidade) = ? OR LOWER(complexidade) = ?)";
            $params[] = $complexidadeFiltro;
            $params[] = ($complexidadeFiltro === 'media' ? 'alta' : 'baixa');
            $params[] = ($complexidadeFiltro === 'media' ? 'media' : 'baixa');
        } elseif ($complexidadeFiltro === 'consulta') {
            $where[] = "(
                LOWER(COALESCE(exame_solicitado, '')) LIKE 'consulta%'
                OR LOWER(COALESCE(exame_solicitado, '')) LIKE '% consulta%'
                OR LOWER(COALESCE(exame_solicitado, '')) LIKE '%consulta%'
                OR LOWER(COALESCE(especialidade, '')) LIKE 'consulta%'
                OR LOWER(COALESCE(especialidade, '')) LIKE '% consulta%'
                OR LOWER(COALESCE(especialidade, '')) LIKE '%consulta%'
            )";
        } elseif ($complexidadeFiltro === 'exame') {
            $where[] = "(
                LOWER(COALESCE(exame_solicitado, '')) LIKE 'exame%'
                OR LOWER(COALESCE(exame_solicitado, '')) LIKE '% exame%'
                OR LOWER(COALESCE(exame_solicitado, '')) LIKE '%- exame%'
                OR LOWER(COALESCE(exame_solicitado, '')) LIKE '%exame%'
                OR LOWER(COALESCE(especialidade, '')) LIKE 'exame%'
                OR LOWER(COALESCE(especialidade, '')) LIKE '% exame%'
                OR LOWER(COALESCE(especialidade, '')) LIKE '%- exame%'
                OR LOWER(COALESCE(especialidade, '')) LIKE '%exame%'
            )";
        } elseif ($complexidadeFiltro === 'outro') {
            $where[] = "(
                LOWER(COALESCE(exame_solicitado, '')) NOT LIKE 'consulta%'
                AND LOWER(COALESCE(exame_solicitado, '')) NOT LIKE '% consulta%'
                AND LOWER(COALESCE(exame_solicitado, '')) NOT LIKE '%consulta%'
                AND LOWER(COALESCE(exame_solicitado, '')) NOT LIKE 'exame%'
                AND LOWER(COALESCE(exame_solicitado, '')) NOT LIKE '% exame%'
                AND LOWER(COALESCE(exame_solicitado, '')) NOT LIKE '%- exame%'
                AND LOWER(COALESCE(exame_solicitado, '')) NOT LIKE '%exame%'
            )";
        }
    }

    if (!empty($_GET['especialidade'])) {
        $especialidadeFiltro = strtolower(trim($_GET['especialidade']));
        $where[] = "(LOWER(especialidade) = ? OR LOWER(exame_solicitado) = ? OR LOWER(exame_solicitado) LIKE ?)";
        $params[] = $especialidadeFiltro;
        $params[] = $especialidadeFiltro;
        $params[] = '%' . $especialidadeFiltro . '%';
    }

    $sql = "SELECT * FROM pedidos_exames";

    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY criado_em DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    while ($s = $stmt->fetch(PDO::FETCH_ASSOC)):
        $classe = "status-" . strtolower(str_replace(' ', '-', $s['status']));
        $tipoPedido = normalizarCategoriaPedido($s['exame_solicitado'] ?? '');
        $especialidadePedido = normalizarEspecialidade($s['especialidade'] ?? $s['exame_solicitado'] ?? '');
        ?>
        <div class="box <?= $classe ?>  ">
            <div class="header" onclick="toggle(<?= $s['id'] ?>)">
                <div class="titulo">#<?= $s['id'] ?> – <?= htmlspecialchars($s['nome_paciente']) ?></div>
                <div>▼</div>
            </div>

            <?php $endereco = $s['rua'] . " " . $s['numero'] . " " . $s['bairro']; ?>

            <div class="detalhes" id="det_<?= $s['id'] ?>">
                <p><b>Categoria:</b>
                    <?= htmlspecialchars($tipoPedido) ?>
                </p>
                <p><b>Especialidade:</b>
                    <?= htmlspecialchars($especialidadePedido ?: ($s['especialidade'] ?? '')) ?>
                </p>
                <p><b>Cartão do Sus:</b> <?= htmlspecialchars($s['cartao_sus']) ?></p>
                <p><b>Exame:</b> <?= nl2br(htmlspecialchars($s['exame_solicitado'])) ?></p>
                <p><b>Observacoes:</b> <?= htmlspecialchars($s['observacoes']) ?></p>
                <p><b>Endereço:</b> <?= htmlspecialchars($endereco) ?></p>
                <p><b>Telefone:</b> <?= htmlspecialchars($s['telefone']) ?></p>
                <p><b>Status atual:</b> <?= htmlspecialchars($s['status']) ?></p>
                <?php 
                $emergencia = isset($s['emergencia']) ? $s['emergencia'] : 0;
                if ($emergencia == 1): ?>
                    <p><b style="color: #c53030;">⚠️ EMERGÊNCIA</b></p>
                <?php endif; ?>
                <?php
                if ($s['status'] !== 'Finalizado') {
                    if ($s['status'] !== 'Em andamento') { ?>
                        <a href="?atender=<?= $s['id'] ?>">
                            <button class="btn-atender">Atender</button>
                        </a> <?php
                    } ?>

                    <a href="?finalizar=<?= $s['id'] ?>">
                        <button class="btn-finalizar">Finalizar</button>
                    </a>
                    
                    <?php if ($emergencia == 0): ?>
                        <a href="?marcar_emergencia=<?= $s['id'] ?>">
                            <button class="btn-emergencia">Marcar Emergência</button>
                        </a>
                    <?php else: ?>
                        <a href="?desmarcar_emergencia=<?= $s['id'] ?>">
                            <button class="btn-desmarcar-emergencia">Remover Emergência</button>
                        </a>
                    <?php endif; ?>
                <?php } ?>
                <button class="btn-mensagem" onclick="abrirModal(<?= $s['id'] ?>,'<?= $s['nome_paciente'] ?>')">
                    Mensagem
                </button>
            </div>
        </div>
    <?php endwhile; ?>
    <script>
        function toggle(id) {
            const el = document.getElementById('det_' + id);
            const box = el.closest('.box');
            const aberto = el.style.display === 'block';

            el.style.display = aberto ? 'none' : 'block';

            // Só dispara quando ABRIR
            if (!aberto && !box.classList.contains('status-finalizado') && !box.classList.contains('status-visualizado')) {
                fetch('visualizar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + id
                });
            }
        }
    </script>
    <script>
        let pedidoSelecionado = 0;

        function abrirModal(id, nome) {
            pedidoSelecionado = id;
            document.getElementById('mensagemTexto').value = 'Ola, ' + nome + ' Pedimos que entre em contato com a Secretaria de Saúde para verificar documentos do seu exame';
            document.getElementById('modalMensagem').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modalMensagem').style.display = 'none';
        }

        function enviarMensagem() {
            const mensagem = document.getElementById('mensagemTexto').value.trim();
            if (!mensagem) {
                alert('Digite a mensagem antes de enviar.');
                return;
            }

            // Envia via fetch para PHP
            fetch('enviar_mensagem.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `pedido_id=${pedidoSelecionado}&mensagem=${encodeURIComponent(mensagem)}`
            })
                .then(res => res.text())
                .then(res => {
                    alert(res);
                    fecharModal();
                })
                .catch(err => {
                    alert('Erro ao enviar mensagem.');
                    console.error(err);
                });
        }
    </script>
    <script>

        document.getElementById("filtroTipo").addEventListener("change", function () {

            let tipo = this.value;
            let especialidade = document.getElementById("filtroEspecialidade");
            let opcoes = especialidade.querySelectorAll("option");

            opcoes.forEach(function (op) {

                if (op.value === "") {
                    op.style.display = "block";
                    return;
                }

                if (tipo === "") {
                    op.style.display = "block";
                }
                else if (op.dataset.tipo === tipo) {
                    op.style.display = "block";
                }
                else {
                    op.style.display = "none";
                }

            });

            especialidade.value = "";

        });

    </script>
</body>

</html>