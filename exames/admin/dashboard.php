<?php
// admin/dashboard.php
declare(strict_types=1);
require_once '../conexao.php';
require_once '../helpers/auth.php';

$admin = exigirLoginAdmin();

// Métricas gerais
$totalPacientes = $pdo->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
$totalPedidos   = $pdo->query("SELECT COUNT(*) FROM pedidos_exames")->fetchColumn();

$totais = [
    'aguardando_analise' => 0,
    'aguardando_nova_analise' => 0,
    'em_espera'          => 0,
    'aguardando_vaga'    => 0,
    'agendado'           => 0,
    'finalizado'         => 0,
    'cancelado'          => 0
];
$stmt = $pdo->query("SELECT status, COUNT(*) as qtd FROM pedidos_exames GROUP BY status");
while ($r = $stmt->fetch()) {
    if (isset($totais[$r['status']])) {
        $totais[$r['status']] = (int)$r['qtd'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel de Controle - Regulação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/exames.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 25px; }
        .stat-card {
            background: #fff; border-radius: 12px; border: 1px solid #dfe4ea;
            padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); text-align: center;
        }
        .stat-num { font-size: 32px; font-weight: bold; color: #2a5298; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-1">Painel Regulatório de Saúde</h2>
            <p class="text-muted">Visão consolidada de demanda e atendimento</p>
        </div>
        <div>
            <a href="listagem_ordens.php" class="btn btn-primary fw-bold me-2">Gerenciar Solicitações</a>
            <a href="catalogo_exames.php" class="btn btn-outline-primary fw-bold me-2">Gerenciar Catálogo</a>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="text-muted mb-1">Total de Pacientes</div>
                <div class="stat-num"><?= $totalPacientes ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="text-muted mb-1">Total de Pedidos</div>
                <div class="stat-num"><?= $totalPedidos ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-warning bg-opacity-10 border-warning">
                <div class="text-warning fw-bold mb-1">Aguardando Análise</div>
                <div class="stat-num text-warning"><?= $totais['aguardando_analise'] ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="stat-card bg-primary bg-opacity-10 border-primary">
                <div class="text-primary fw-bold mb-1">Novas Respostas</div>
                <div class="stat-num text-primary"><?= $totais['aguardando_nova_analise'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-danger fw-bold mb-1">Em Espera</div>
                <div class="stat-num text-danger"><?= $totais['em_espera'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-info fw-bold mb-1">Aguardando Vaga</div>
                <div class="stat-num text-info"><?= $totais['aguardando_vaga'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-success fw-bold mb-1">Agendados</div>
                <div class="stat-num text-success"><?= $totais['agendado'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-secondary fw-bold mb-1">Finalizados</div>
                <div class="stat-num text-secondary"><?= $totais['finalizado'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-danger fw-bold mb-1">Cancelados</div>
                <div class="stat-num text-danger"><?= $totais['cancelado'] ?></div>
            </div>
        </div>
    </div>
    <div class="text-center mt-4"><a href="pendencias.php" class="btn btn-warning fw-bold"><i class="fa-solid fa-clock"></i> Abrir Central de Pendências</a></div>
</div>
</body>
</html>
