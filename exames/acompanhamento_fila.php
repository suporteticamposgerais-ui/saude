<?php
// acompanhamento_fila.php
declare(strict_types=1);
require_once 'conexao.php';
require_once 'helpers/seguranca.php';
require_once 'helpers/auth.php';

$pacienteLogado = exigirLoginPaciente();
$pacienteId = (int)$pacienteLogado['id'];
$pedidos = [];
$erro = '';

$sql = "
    SELECT p.*, e.nome AS exame_nome, esp.nome AS especialidade_nome, u.nome AS unidade_nome
    FROM pedidos_exames p
    INNER JOIN exames e ON e.id = p.exame_id
    INNER JOIN especialidades esp ON esp.id = e.especialidade_id
    INNER JOIN unidades u ON u.id = p.unidade_id
    WHERE p.paciente_id = ?
      AND p.status = 'aguardando_vaga'
    ORDER BY p.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$pacienteId]);
$pedidos = $stmt->fetchAll();
if (!$pedidos) $erro = 'Você não possui pedidos atualmente em fila.';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posição na Fila de Espera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/exames.css" rel="stylesheet">
    <style>
        body { background: #f4f6f8; font-family: Arial; padding: 20px; }
        .box-fila { max-width: 800px; margin: 0 auto; }
        .card-fila { background: #fff; border-radius: 12px; border: 1px solid #dfe4ea; padding: 20px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="box-fila">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">Consulta de Posição na Fila</h2>
        <p class="text-muted">Acompanhe sua classificação transparente conforme a Lei Geral de Proteção de Dados (LGPD)</p>
    </div>

    <div class="card shadow-sm p-4 mb-4 border-0"><div class="d-flex justify-content-between align-items-center gap-3 flex-wrap"><div><strong>Consulta protegida por login</strong><div class="small text-muted">Você está consultando somente as suas próprias solicitações.</div></div><a href="paciente/painel.php" class="btn btn-outline-primary btn-sm">Voltar ao meu painel</a></div></div>

    <?php if ($erro): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <?php foreach ($pedidos as $meu): ?>
        <?php
        // Obter posição na fila específica para aquele procedimento
        $stmtFila = $pdo->prepare("SELECT p.id, p.status, p.emergencia FROM pedidos_exames p WHERE p.exame_id = ? AND p.status = 'aguardando_vaga' ORDER BY p.prioridade ASC, p.data_entrada_fila ASC, p.criado_em ASC");
        $stmtFila->execute([$meu['exame_id']]);
        $fila = $stmtFila->fetchAll();

        $posicao = 0;
        foreach ($fila as $idx => $item) {
            if ($item['id'] === $meu['id']) {
                $posicao = $idx + 1;
                break;
            }
        }
        ?>
        <div class="card-fila shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold mb-0 text-primary"><?= htmlspecialchars($meu['exame_nome']) ?></h5>
                <span class="badge bg-secondary"><?= htmlspecialchars($meu['protocolo']) ?></span>
            </div>
            <div class="alert alert-info py-2 my-3">
                Você está na posição <strong><?= $posicao ?>º</strong> de um total de <?= count($fila) ?> pessoas na lista deste procedimento.
            </div>

            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Posição</th>
                        <th>Fila</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fila as $i => $row): ?>
                        <tr class="<?= $row['id'] === $meu['id'] ? 'table-primary fw-bold' : '' ?>">
                            <td><?= $i + 1 ?>º</td>
                            <td>
                                <?= $row['id'] === $meu['id'] ? 'Você' : 'Outro paciente' ?>
                                <?= $row['emergencia'] ? ' <span class="badge bg-danger">Emergência</span>' : '' ?>
                            </td>
                            <td><?= ucwords(str_replace('_', ' ', $row['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <div class="text-center mt-3">
        <a href="index.php" class="text-decoration-none small">← Voltar para o Portal Principal</a>
    </div>
</div>
</body>
</html>
