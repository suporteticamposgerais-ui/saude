<?php
// admin/paciente_perfil.php
declare(strict_types=1);
require_once '../conexao.php';
require_once '../helpers/auth.php';
require_once '../helpers/seguranca.php';
require_once '../helpers/pedidos.php';

$admin = exigirLoginAdmin();
$pacienteId = (int)($_GET['id'] ?? 0);
if (!$pacienteId) { header('Location: listagem_ordens.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
$stmt->execute([$pacienteId]);
$paciente = $stmt->fetch();
if (!$paciente) die('Paciente não encontrado.');

$msgSucesso = '';
$msgErro = $_SESSION['flash_erro'] ?? '';
unset($_SESSION['flash_erro']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novo_exame_manual'])) {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $msgErro = 'Sessão expirada.';
    } else {
        $exameId = (int)($_POST['exame_id'] ?? 0);
        $obs = trim($_POST['observacoes'] ?? '');
        $stmtEx = $pdo->prepare("SELECT id, unidade_id, complexidade FROM exames WHERE id = ? AND ativo = 1");
        $stmtEx->execute([$exameId]);
        $exDados = $stmtEx->fetch();
        if (!$exDados) {
            $msgErro = 'Procedimento inválido.';
        } else {
            $pdo->beginTransaction();
            try {
                $proto = gerarProtocolo($pdo);
                $ins = $pdo->prepare("INSERT INTO pedidos_exames (protocolo, colocacao, paciente_id, exame_id, unidade_id, complexidade, status, prioridade, observacoes, data_entrada_fila, criado_em) VALUES (?, ?, ?, ?, ?, ?, 'aguardando_vaga', 2, ?, NOW(), NOW())");
                $colocacao = gerarColocacao($pdo, $exameId);
                $ins->execute([$proto, $colocacao, $pacienteId, $exameId, $exDados['unidade_id'], $exDados['complexidade'], $obs]);
                $novoId = (int)$pdo->lastInsertId();
                registrarHistorico($pdo, $novoId, null, 'aguardando_vaga', 'Pedido criado manualmente pelo administrador', $obs ?: null, 'admin', $admin['id'], $admin['nome']);
                $pdo->commit();
                $msgSucesso = 'Pedido adicionado diretamente à fila de vagas.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $msgErro = 'Não foi possível cadastrar o pedido: ' . $e->getMessage();
            }
        }
    }
}

// Ao abrir o prontuário, as notificações daquele paciente/pedido são consideradas visualizadas.
$mark = $pdo->prepare("UPDATE notificacoes_admin n INNER JOIN pedidos_exames p ON p.id = n.pedido_id SET n.lida = 1, n.lida_por = ?, n.lida_em = NOW() WHERE p.paciente_id = ? AND n.lida = 0");
$mark->execute([$admin['id'], $pacienteId]);

$sqlPedidos = "
    SELECT p.*, e.nome AS exame_nome, e.tipo AS exame_tipo, esp.nome AS especialidade_nome, u.nome AS unidade_nome,
           ag.data_hora AS agendado_data, ag.local_unidade, ag.profissional, ag.orientacoes
    FROM pedidos_exames p
    INNER JOIN exames e ON e.id = p.exame_id
    INNER JOIN especialidades esp ON esp.id = e.especialidade_id
    INNER JOIN unidades u ON u.id = p.unidade_id
    LEFT JOIN agendamentos ag ON ag.pedido_id = p.id AND ag.status IN ('agendado','confirmado')
    WHERE p.paciente_id = ?
    ORDER BY p.criado_em DESC
";
$stmtPedidos = $pdo->prepare($sqlPedidos);
$stmtPedidos->execute([$pacienteId]);
$pedidos = $stmtPedidos->fetchAll();

$stmtDocs = $pdo->prepare("SELECT * FROM documentos_exames WHERE paciente_id = ? ORDER BY criado_em DESC, id DESC");
$stmtDocs->execute([$pacienteId]);
$documentos = $stmtDocs->fetchAll();
$docsPorPedido = [];
foreach ($documentos as $doc) $docsPorPedido[$doc['pedido_id']][] = $doc;

$historicoPorPedido = [];
if ($pedidos) {
    $ids = array_column($pedidos, 'id');
    $place = implode(',', array_fill(0, count($ids), '?'));
    $h = $pdo->prepare("SELECT * FROM historico_pedidos WHERE pedido_id IN ($place) ORDER BY criado_em DESC, id DESC");
    $h->execute($ids);
    while ($row = $h->fetch()) $historicoPorPedido[$row['pedido_id']][] = $row;
}

$catalogoExames = $pdo->query("SELECT id, nome, tipo, complexidade, unidade_id FROM exames WHERE ativo = 1 ORDER BY nome")->fetchAll();
$naoLidas = (int)$pdo->query("SELECT COUNT(*) FROM notificacoes_admin WHERE lida = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prontuário de <?= htmlspecialchars($paciente['nome']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/exames.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;padding:20px}.pedido-card-admin{border:1px solid #dfe4ea;background:#fff;border-radius:10px;padding:18px;margin-bottom:14px}.mini-info{font-size:.84rem;color:#6c757d}.timeline-item{border-left:3px solid #dee2e6;padding:0 0 14px 14px;margin-left:5px}.doc-pill{display:inline-flex;align-items:center;gap:5px;margin:3px}.wait-time{font-weight:700}.new-response{border:2px solid #0d6efd;background:#eef6ff}.action-wrap .btn{margin:2px}.section-title{font-weight:700;color:#2a5298}
</style>
</head>
<body>
<div class="container-fluid" style="max-width:1150px;">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="listagem_ordens.php" class="btn btn-outline-secondary btn-sm">← Voltar à Listagem</a>
        <div class="d-flex gap-2">
            <a href="pendencias.php" class="btn btn-outline-warning btn-sm"><i class="fa-solid fa-clock"></i> Pendências<?php if($naoLidas): ?> <span class="badge bg-danger"><?= $naoLidas ?></span><?php endif; ?></a>
            <button class="btn btn-success btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalNovoExame">+ Cadastrar Solicitação Manual</button>
        </div>
    </div>

    <?php if ($msgSucesso): ?><div class="alert alert-success py-2"><?= htmlspecialchars($msgSucesso) ?></div><?php endif; ?>
    <?php if ($msgErro): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($msgErro) ?></div><?php endif; ?>

    <div class="card-box mb-4">
        <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Prontuário Individual do Paciente</span>
            <div><button class="btn btn-light btn-sm text-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalDocumentos"><i class="fa-solid fa-folder-open"></i> Ver todos os arquivos (<?= count($documentos) ?>)</button> <span class="badge bg-light text-primary fs-6">ID #<?= $paciente['id'] ?></span></div>
        </div>
        <div class="p-4 bg-light"><div class="row g-3">
            <div class="col-md-6"><strong>Nome:</strong> <?= htmlspecialchars($paciente['nome']) ?></div>
            <div class="col-md-3"><strong>CPF:</strong> <?= formatarCPF($paciente['cpf']) ?></div>
            <div class="col-md-3"><strong>Cartão SUS:</strong> <?= formatarCNS($paciente['cns']) ?></div>
            <div class="col-md-3"><strong>Nascimento:</strong> <?= date('d/m/Y', strtotime($paciente['data_nascimento'])) ?></div>
            <div class="col-md-3"><strong>Telefone:</strong> <?= formatarTelefone($paciente['telefone']) ?></div>
            <div class="col-md-6"><strong>E-mail:</strong> <?= htmlspecialchars($paciente['email']) ?></div>
            <div class="col-md-12"><strong>Endereço:</strong> <?= htmlspecialchars("{$paciente['rua']}, {$paciente['numero']} - {$paciente['bairro']}, {$paciente['cidade']}/{$paciente['estado']}") ?></div>
        </div></div>
    </div>

    <h4 class="fw-bold text-primary mb-3">Solicitações e Exames Cadastrados</h4>
    <?php if (!$pedidos): ?><div class="alert alert-light border text-center py-4 text-muted">Nenhum pedido cadastrado.</div><?php endif; ?>
    <?php foreach ($pedidos as $p): ?>
        <?php $docs = $docsPorPedido[$p['id']] ?? []; $historico = $historicoPorPedido[$p['id']] ?? []; $novaResposta = $p['status'] === 'aguardando_nova_analise'; $podeRevisarDocumentos = in_array($p['status'], ['aguardando_analise','aguardando_nova_analise','em_espera','aguardando_vaga'], true); ?>
        <div class="pedido-card-admin shadow-sm <?= $novaResposta ? 'new-response' : '' ?>">
            <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                <div><span class="fw-bold text-primary me-2">Protocolo: <?= htmlspecialchars($p['protocolo']) ?></span><h5 class="d-inline-block fw-bold mb-0"><?= htmlspecialchars($p['exame_nome']) ?></h5>
                <div class="text-muted small">Especialidade: <?= htmlspecialchars($p['especialidade_nome']) ?> | Unidade: <?= htmlspecialchars($p['unidade_nome']) ?> | Complexidade: <?= ucfirst($p['complexidade']) ?></div></div>
                <span class="badge-status st-<?= htmlspecialchars($p['status']) ?>"><?= ucwords(str_replace('_',' ',$p['status'])) ?></span>
            </div>

            <?php if ($novaResposta): ?><div class="alert alert-primary py-2 small"><strong><i class="fa-solid fa-bell"></i> NOVA RESPOSTA DO PACIENTE.</strong> Documento(s) enviado(s) em <?= $p['ultima_resposta_paciente'] ? date('d/m/Y H:i', strtotime($p['ultima_resposta_paciente'])) : 'data não informada' ?>. Analise antes de liberar.</div><?php endif; ?>
            <?php if (in_array($p['status'], ['em_espera','aguardando_nova_analise'], true) && $p['motivo_espera']): ?><div class="alert alert-warning py-2 my-2 small"><strong>Motivo da pendência:</strong> <?= htmlspecialchars($p['motivo_espera']) ?><?php if ($p['espera_desde']): ?><br><span class="wait-time"><i class="fa-regular fa-clock"></i> Em espera há <?= formatarTempoDecorrido($p['espera_desde']) ?></span> <span class="text-muted">(desde <?= date('d/m/Y H:i', strtotime($p['espera_desde'])) ?>)</span><?php endif; ?><?php if ($p['ultima_resposta_paciente']): ?><br><span class="text-primary">Última resposta do paciente: <?= date('d/m/Y H:i', strtotime($p['ultima_resposta_paciente'])) ?></span><?php endif; ?></div><?php endif; ?>
            <?php if ($p['status'] === 'cancelado' && $p['motivo_cancelamento']): ?><div class="alert alert-danger py-2 small"><strong>Motivo do cancelamento:</strong> <?= htmlspecialchars($p['motivo_cancelamento']) ?></div><?php endif; ?>
            <?php if (!empty($p['observacoes'])): ?><div class="alert alert-light border py-2 small"><strong>Observações do pedido:</strong> <?= nl2br(htmlspecialchars($p['observacoes'])) ?></div><?php endif; ?>
            <?php if (!empty($p['agendado_data'])): ?><div class="alert alert-success py-2 my-2 small"><strong><i class="fa-regular fa-calendar-check"></i> Agendado:</strong> <?= date('d/m/Y \à\s H:i', strtotime($p['agendado_data'])) ?> — <?= htmlspecialchars($p['local_unidade']) ?><?php if($p['profissional']): ?> — <?= htmlspecialchars($p['profissional']) ?><?php endif; ?><?php if($p['orientacoes']): ?><br><strong>Orientações ao paciente:</strong> <?= nl2br(htmlspecialchars($p['orientacoes'])) ?><?php endif; ?></div><?php endif; ?>

            <div class="mb-2"><strong>Documentos (<?= count($docs) ?>):</strong><br>
                <?php if ($docs): foreach ($docs as $doc): ?><div class="d-inline-flex align-items-center gap-1 flex-wrap doc-pill"><a class="btn btn-outline-secondary btn-sm" href="documento_download.php?id=<?= $doc['id'] ?>" target="_blank"><i class="fa-solid fa-paperclip"></i><?= htmlspecialchars($doc['titulo_documento']) ?></a><span class="badge <?= ($doc['status'] ?? 'pendente') === 'validado' ? 'bg-success' : (($doc['status'] ?? 'pendente') === 'rejeitado' ? 'bg-danger' : 'bg-warning text-dark') ?>"><?= ucfirst($doc['status'] ?? 'pendente') ?></span><?php if ($podeRevisarDocumentos && ($doc['status'] ?? 'pendente') === 'pendente'): ?><form method="POST" action="validar_documento.php" class="d-inline"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="documento_id" value="<?= $doc['id'] ?>"><input type="hidden" name="acao" value="validar"><button class="btn btn-success btn-sm" title="Validar documento"><i class="fa-solid fa-check"></i></button></form><button type="button" class="btn btn-danger btn-sm" onclick="abrirModalRejeitarDocumento(<?= $doc['id'] ?>)" title="Rejeitar documento"><i class="fa-solid fa-xmark"></i></button><?php endif; ?></div><?php endforeach; else: ?><span class="text-muted small">Nenhum documento.</span><?php endif; ?>
            </div>

            <div class="action-wrap mt-3 pt-2 border-top d-flex gap-1 flex-wrap">
                <?php if (in_array($p['status'], ['aguardando_analise','aguardando_nova_analise'], true)): ?>
                    <form method="POST" action="acoes_pedido.php"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="pedido_id" value="<?= $p['id'] ?>"><input type="hidden" name="acao" value="aprovar_vaga"><button class="btn btn-success btn-sm fw-bold">Aprovar p/ Fila</button></form>
                <?php endif; ?>
                <?php if (in_array($p['status'], ['aguardando_analise','aguardando_nova_analise','aguardando_vaga'], true)): ?><button class="btn btn-warning btn-sm fw-bold" onclick="abrirModalEspera(<?= $p['id'] ?>)">Colocar em Espera</button><?php endif; ?>
                <?php if ($p['status'] === 'em_espera'): ?><form method="POST" action="acoes_pedido.php"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="pedido_id" value="<?= $p['id'] ?>"><input type="hidden" name="acao" value="liberar_espera"><button class="btn btn-info btn-sm text-white fw-bold">Liberar p/ Fila</button></form><?php endif; ?>
                <?php if ($p['status'] === 'aguardando_vaga'): ?><button class="btn btn-primary btn-sm fw-bold" onclick="abrirModalAgendar(<?= $p['id'] ?>)">Agendar Atendimento</button><?php endif; ?>
                <?php if ($p['status'] === 'agendado'): ?><button class="btn btn-primary btn-sm fw-bold" onclick="abrirModalAgendar(<?= $p['id'] ?>)">Alterar Agendamento</button><button class="btn btn-outline-warning btn-sm fw-bold" onclick="abrirModalFila(<?= $p['id'] ?>)">Voltar para Fila</button><button class="btn btn-success btn-sm fw-bold" onclick="abrirModalConclusao(<?= $p['id'] ?>)">Concluir Ordem</button><?php endif; ?>
                <?php if (!in_array($p['status'], ['finalizado','cancelado'], true)): ?><button class="btn btn-outline-danger btn-sm fw-bold" onclick="abrirModalCancelamento(<?= $p['id'] ?>)">Cancelar Pedido</button><?php endif; ?>
                <?php if ($p['status'] === 'aguardando_vaga' && !$p['emergencia']): ?><form method="POST" action="acoes_pedido.php"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="pedido_id" value="<?= $p['id'] ?>"><input type="hidden" name="acao" value="marcar_emergencia"><button class="btn btn-outline-danger btn-sm">Marcar Emergência</button></form><?php elseif ($p['status'] === 'aguardando_vaga' && $p['emergencia']): ?><form method="POST" action="acoes_pedido.php"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="pedido_id" value="<?= $p['id'] ?>"><input type="hidden" name="acao" value="remover_emergencia"><button class="btn btn-outline-secondary btn-sm">Remover Emergência</button></form><?php endif; ?>
            </div>

            <div class="mt-3"><button class="btn btn-link btn-sm p-0" data-bs-toggle="collapse" data-bs-target="#historico<?= $p['id'] ?>">Ver histórico do pedido</button><div class="collapse mt-2" id="historico<?= $p['id'] ?>">
                <?php if (!$historico): ?><span class="small text-muted">Sem histórico.</span><?php else: foreach ($historico as $h): ?><div class="timeline-item"><div class="small fw-bold"><?= htmlspecialchars($h['acao']) ?></div><div class="small text-muted"><?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?> — <?= htmlspecialchars($h['autor_nome']) ?></div><?php if($h['observacao']): ?><div class="small mt-1"><?= nl2br(htmlspecialchars($h['observacao'])) ?></div><?php endif; ?></div><?php endforeach; endif; ?>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modais -->
<div class="modal fade" id="modalNovoExame" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="novo_exame_manual" value="1"><div class="modal-header"><h5 class="modal-title fw-bold">Cadastrar Solicitação para o Paciente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Procedimento</label><select name="exame_id" class="form-select mb-3" required><option value="">Selecione...</option><?php foreach($catalogoExames as $ce): ?><option value="<?= $ce['id'] ?>"><?= htmlspecialchars($ce['nome']) ?> (<?= ucfirst($ce['complexidade']) ?>)</option><?php endforeach; ?></select><label class="form-label">Observações</label><textarea name="observacoes" class="form-control" rows="3"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary fw-bold">Cadastrar Pedido</button></div></form></div></div>

<div class="modal fade" id="modalEsperaDialog" tabindex="-1"><div class="modal-dialog"><form method="POST" action="acoes_pedido.php" class="modal-content"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="pedido_id" id="espera_pedido_id"><input type="hidden" name="acao" value="colocar_espera"><div class="modal-header"><h5 class="modal-title fw-bold">Colocar em Espera</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label fw-bold">Motivo da pendência</label><textarea name="motivo_espera" class="form-control" rows="4" required minlength="10" placeholder="Ex.: Ausência do pedido médico assinado."></textarea><div class="form-text">Informe claramente o que o paciente precisa providenciar.</div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-warning fw-bold">Confirmar Espera</button></div></form></div></div>

<div class="modal fade" id="modalAgendarDialog" tabindex="-1"><div class="modal-dialog"><form method="POST" action="acoes_pedido.php" class="modal-content"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="pedido_id" id="agendar_pedido_id"><input type="hidden" name="acao" value="agendar_pedido"><div class="modal-header"><h5 class="modal-title fw-bold">Agendar Atendimento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Data e Hora</label><input type="datetime-local" name="data_hora" class="form-control mb-3" required><label class="form-label">Local / Unidade</label><input type="text" name="local_unidade" class="form-control mb-3" required><label class="form-label">Profissional (opcional)</label><input type="text" name="profissional" class="form-control mb-3"><label class="form-label fw-bold">Orientações para o paciente</label><textarea name="orientacoes" class="form-control" rows="4" placeholder="Ex.: Levar documento de identidade, pedido médico original e exames anteriores."></textarea></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-primary fw-bold">Salvar Agendamento</button></div></form></div></div>

<div class="modal fade" id="modalFilaDialog" tabindex="-1"><div class="modal-dialog"><form method="POST" action="acoes_pedido.php" class="modal-content"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="pedido_id" id="fila_pedido_id"><input type="hidden" name="acao" value="voltar_fila"><div class="modal-header"><h5 class="modal-title fw-bold">Voltar Paciente para a Fila</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="small text-muted">O agendamento atual será cancelado e o pedido receberá uma nova entrada na fila.</p><textarea name="motivo" class="form-control" rows="4" minlength="20" required placeholder="Informe o motivo, com pelo menos 20 caracteres."></textarea></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-outline-warning fw-bold">Voltar para Fila</button></div></form></div></div>

<div class="modal fade" id="modalCancelamento" tabindex="-1"><div class="modal-dialog"><form method="POST" action="acoes_pedido.php" class="modal-content"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="pedido_id" id="cancelar_pedido_id"><input type="hidden" name="acao" value="cancelar"><div class="modal-header"><h5 class="modal-title fw-bold text-danger">Cancelar Pedido</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>O cancelamento <strong>não</strong> devolverá o paciente para a fila.</p><textarea name="motivo" class="form-control" rows="4" minlength="20" required placeholder="Informe o motivo do cancelamento (mínimo 20 caracteres)."></textarea></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-danger fw-bold">Confirmar Cancelamento</button></div></form></div></div>

<div class="modal fade" id="modalConclusao" tabindex="-1"><div class="modal-dialog"><form method="POST" action="acoes_pedido.php" class="modal-content"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="pedido_id" id="conclusao_pedido_id"><input type="hidden" name="acao" value="finalizar"><div class="modal-header"><h5 class="modal-title fw-bold">Concluir Ordem</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label fw-bold">Observações da conclusão (opcional)</label><textarea name="observacao_conclusao" class="form-control" rows="4" placeholder="Ex.: Exame realizado normalmente. Paciente apresentou a documentação solicitada."></textarea></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-success fw-bold">Concluir Ordem</button></div></form></div></div>

<div class="modal fade" id="modalDocumentos" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title fw-bold">Todos os arquivos de <?= htmlspecialchars($paciente['nome']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><?php if(!$documentos): ?><div class="text-muted">Nenhum arquivo enviado.</div><?php else: ?><?php foreach($documentos as $doc): ?><div class="border rounded p-2 mb-2"><div class="d-flex justify-content-between align-items-center gap-2"><div><strong><?= htmlspecialchars($doc['titulo_documento']) ?></strong><div class="small text-muted">Pedido #<?= htmlspecialchars((string)$doc['pedido_id']) ?> · <?= htmlspecialchars($doc['arquivo_original']) ?> · <?= date('d/m/Y H:i', strtotime($doc['criado_em'])) ?></div></div><div class="d-flex gap-1 align-items-center"><span class="badge <?= ($doc['status'] ?? 'pendente') === 'validado' ? 'bg-success' : (($doc['status'] ?? 'pendente') === 'rejeitado' ? 'bg-danger' : 'bg-warning text-dark') ?>"><?= ucfirst($doc['status'] ?? 'pendente') ?></span><a class="btn btn-outline-primary btn-sm" href="documento_download.php?id=<?= $doc['id'] ?>" target="_blank">Abrir</a><?php if (($doc['status'] ?? 'pendente') === 'pendente'): ?><form method="POST" action="validar_documento.php" class="d-inline"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="documento_id" value="<?= $doc['id'] ?>"><input type="hidden" name="acao" value="validar"><button class="btn btn-success btn-sm">Validar</button></form><button type="button" class="btn btn-danger btn-sm" onclick="abrirModalRejeitarDocumento(<?= $doc['id'] ?>)">Rejeitar</button><?php endif; ?></div></div><?php if (($doc['status'] ?? '') === 'rejeitado' && !empty($doc['observacao_admin'])): ?><div class="alert alert-danger py-2 small mt-2 mb-0"><strong>Motivo da rejeição:</strong> <?= htmlspecialchars($doc['observacao_admin']) ?></div><?php endif; ?></div><?php endforeach; ?><?php endif; ?></div></div></div></div>
<div class="modal fade" id="modalRejeitarDocumento" tabindex="-1"><div class="modal-dialog"><form method="POST" action="validar_documento.php" class="modal-content"><input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>"><input type="hidden" name="documento_id" id="rejeitar_documento_id"><input type="hidden" name="acao" value="rejeitar"><div class="modal-header"><h5 class="modal-title fw-bold text-danger">Rejeitar Documento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="small text-muted">Informe claramente o que o paciente precisa corrigir ou reenviar.</p><textarea name="observacao" class="form-control" rows="4" minlength="10" required placeholder="Ex.: Documento ilegível. Envie uma nova imagem com todas as informações visíveis."></textarea></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button class="btn btn-danger fw-bold">Rejeitar Documento</button></div></form></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function abrirModalEspera(id){document.getElementById('espera_pedido_id').value=id;new bootstrap.Modal('#modalEsperaDialog').show()}
function abrirModalAgendar(id){document.getElementById('agendar_pedido_id').value=id;new bootstrap.Modal('#modalAgendarDialog').show()}
function abrirModalFila(id){document.getElementById('fila_pedido_id').value=id;new bootstrap.Modal('#modalFilaDialog').show()}
function abrirModalCancelamento(id){document.getElementById('cancelar_pedido_id').value=id;new bootstrap.Modal('#modalCancelamento').show()}
function abrirModalConclusao(id){document.getElementById('conclusao_pedido_id').value=id;new bootstrap.Modal('#modalConclusao').show()}
function abrirModalRejeitarDocumento(id){document.getElementById('rejeitar_documento_id').value=id;new bootstrap.Modal('#modalRejeitarDocumento').show()}
</script>
</body></html>
