<?php
// paciente/painel.php
declare(strict_types=1);
require_once '../conexao.php';
require_once '../helpers/auth.php';
require_once '../helpers/seguranca.php';
require_once '../helpers/pedidos.php';

$pacienteLogado = exigirLoginPaciente();
$pacienteId = (int)$pacienteLogado['id'];
$stmtPac = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
$stmtPac->execute([$pacienteId]);
$paciente = $stmtPac->fetch();

$sql = "SELECT p.*, e.nome AS exame_nome, e.tipo AS exame_tipo, esp.nome AS especialidade_nome, u.nome AS unidade_nome,
               ag.data_hora AS agendado_para, ag.local_unidade, ag.profissional, ag.orientacoes
        FROM pedidos_exames p
        INNER JOIN exames e ON e.id = p.exame_id
        INNER JOIN especialidades esp ON esp.id = e.especialidade_id
        INNER JOIN unidades u ON u.id = p.unidade_id
        LEFT JOIN agendamentos ag ON ag.pedido_id = p.id AND ag.status IN ('agendado','confirmado')
        WHERE p.paciente_id = ? ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql); $stmt->execute([$pacienteId]); $todosPedidos = $stmt->fetchAll();

$pedidosAguardando = array_filter($todosPedidos, fn($p) => in_array($p['status'], ['aguardando_analise','aguardando_nova_analise'], true));
$pedidosEspera = array_filter($todosPedidos, fn($p) => $p['status'] === 'em_espera');
$pedidosVaga = array_filter($todosPedidos, fn($p) => $p['status'] === 'aguardando_vaga');
$pedidosAgendados = array_filter($todosPedidos, fn($p) => $p['status'] === 'agendado');
$pedidosFinalizados = array_filter($todosPedidos, fn($p) => in_array($p['status'], ['realizado','finalizado','cancelado'], true));

$stmtDocs = $pdo->prepare("SELECT id,pedido_id,titulo_documento,arquivo_original,status,observacao_admin FROM documentos_exames WHERE paciente_id=? ORDER BY criado_em DESC");
$stmtDocs->execute([$pacienteId]); $docsPorPedido=[];
while($doc=$stmtDocs->fetch()) $docsPorPedido[$doc['pedido_id']][]=$doc;

$historicoCancel = [];
if ($todosPedidos) {
    $ids=array_column($todosPedidos,'id'); $ph=implode(',',array_fill(0,count($ids),'?'));
    $h=$pdo->prepare("SELECT pedido_id, observacao FROM historico_pedidos WHERE pedido_id IN ($ph) AND status_novo='cancelado' ORDER BY id DESC");
    $h->execute($ids); while($r=$h->fetch()) { if(!isset($historicoCancel[$r['pedido_id']])) $historicoCancel[$r['pedido_id']]=$r['observacao']; }
}
?>
<!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Painel do Paciente</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../css/exames.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>.container-painel{max-width:1050px;margin:24px auto 32px;padding:0 16px}.card-header-top{background:linear-gradient(135deg,#0d6efd,#084298);color:#fff;padding:18px 20px;font-size:20px;font-weight:600;display:flex;justify-content:space-between;align-items:center}.nav-tabs .nav-link{font-weight:600;color:#4b5563}.nav-tabs .nav-link.active{color:#0d6efd}.card-pedido{border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;margin-bottom:14px;background:#fff}.alert-pendente{border:2px solid #ffc107;background:#fff8df}.alert-novo{border:2px solid #0d6efd;background:#eef6ff}.btn-consultar-fila{display:inline-flex;align-items:center;justify-content:center;gap:.5rem}@media(max-width:575.98px){.btn-consultar-fila{width:100%}.card-header-top{gap:12px;flex-wrap:wrap}.card-header-top>div{display:flex;gap:8px;flex-wrap:wrap}.card-header-top .me-2{margin-right:0!important}}</style>
</head><body><div class="container container-painel"><div class="card-box shadow-sm">
<div class="card-header-top"><span>Meu Painel</span><div><a href="solicitar.php" class="btn btn-light btn-sm fw-bold me-2">+ Solicitar Exame</a><a href="../logout.php" class="btn btn-outline-light btn-sm">Sair</a></div></div></div>
<?php if(isset($_GET['documento_enviado'])): ?><div class="alert alert-success m-3 mb-0">Documento enviado. Seu pedido voltou para análise da equipe.</div><?php endif; ?>
<div class="p-3"><div class="d-flex justify-content-end mb-3"><a href="../acompanhamento_fila.php" class="btn btn-outline-primary btn-consultar-fila"><i class="fa-solid fa-list-ol" aria-hidden="true"></i> Consultar posição na fila</a></div><ul class="nav nav-tabs mb-3"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-todos">Todos (<?=count($todosPedidos)?>)</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-analise">Análise (<?=count($pedidosAguardando)?>)</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-espera">Em Espera (<?=count($pedidosEspera)?>)</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-vaga">Aguardando Vaga (<?=count($pedidosVaga)?>)</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-agendados">Agendados (<?=count($pedidosAgendados)?>)</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-finalizados">Histórico (<?=count($pedidosFinalizados)?>)</button></li></ul>
<div class="tab-content">
<?php $render=function(array $lista, string $aba) use($docsPorPedido,$historicoCancel){if(!$lista){echo '<div class="alert alert-light text-center border py-4 text-muted">Nenhuma solicitação encontrada nesta aba.</div>';return;} foreach($lista as $p): $docs=$docsPorPedido[$p['id']]??[]; $modalKey=$aba.'-'.$p['id']; ?>
<div class="card-pedido <?= $p['status']==='aguardando_nova_analise'?'alert-novo':'' ?> <?= $p['status']==='em_espera'?'alert-pendente':'' ?>">
<div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-bold text-primary">Protocolo: <?=htmlspecialchars($p['protocolo'])?></span><span class="badge-status st-<?=htmlspecialchars($p['status'])?>"><?=ucwords(str_replace('_',' ',$p['status']))?></span></div>
<h5 class="mb-1 fw-bold"><?=htmlspecialchars($p['exame_nome'])?></h5><div class="text-muted small mb-2">Especialidade: <?=htmlspecialchars($p['especialidade_nome'])?> | Unidade: <?=htmlspecialchars($p['unidade_nome'])?></div>
<?php if($p['status']==='em_espera' && $p['motivo_espera']): ?><div class="alert alert-warning py-2 small mb-2"><strong>Motivo da espera:</strong> <?=htmlspecialchars($p['motivo_espera'])?><?php if($p['espera_desde']): ?><br><span class="text-muted">Desde <?=date('d/m/Y H:i',strtotime($p['espera_desde']))?>.</span><?php endif; ?></div><button class="btn btn-warning btn-sm fw-bold mb-2" data-bs-toggle="modal" data-bs-target="#modalDoc<?=$modalKey?>"><i class="fa-solid fa-file-circle-plus"></i> Adicionar documento / Responder pendência</button><?php endif; ?>
<?php if($p['status']==='aguardando_nova_analise'): ?><div class="alert alert-primary py-2 small"><strong>Documento enviado com sucesso.</strong> A equipe administrativa está analisando sua resposta.</div><?php endif; ?>
<?php if($p['status']==='cancelado' && !empty($p['motivo_cancelamento'])): ?><div class="alert alert-danger py-2 small"><strong>Motivo do cancelamento:</strong> <?=htmlspecialchars($p['motivo_cancelamento'])?></div><?php endif; ?>
<?php if($p['agendado_para']): ?><div class="alert alert-success py-2 small"><strong><i class="fa-regular fa-calendar-check"></i> Agendado:</strong> <?=date('d/m/Y \à\s H:i',strtotime($p['agendado_para']))?> — <?=htmlspecialchars($p['local_unidade'])?><?php if($p['profissional']): ?> — <?=htmlspecialchars($p['profissional'])?><?php endif; ?><?php if($p['orientacoes']): ?><br><strong>Orientações:</strong> <?=nl2br(htmlspecialchars($p['orientacoes']))?><?php endif; ?></div><?php endif; ?>
<div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top"><span class="small text-muted">Criado em: <?=date('d/m/Y H:i',strtotime($p['criado_em']))?></span><div><?php foreach($docs as $doc): ?><a href="documento_download.php?id=<?=$doc['id']?>" class="btn btn-outline-secondary btn-sm me-1 mb-1" target="_blank"><i class="fa-solid fa-paperclip"></i> <?=htmlspecialchars($doc['titulo_documento'])?> — <?=htmlspecialchars(ucfirst($doc['status'] ?? 'pendente'))?></a><?php if(($doc['status'] ?? '')==='rejeitado' && !empty($doc['observacao_admin'])): ?><div class="alert alert-danger py-2 small mt-1"><strong>Documento rejeitado:</strong> <?=htmlspecialchars($doc['observacao_admin'])?></div><?php endif; ?><?php endforeach; ?></div></div>
</div>
<?php if($p['status']==='em_espera'): ?><div class="modal fade" id="modalDoc<?=$modalKey?>" tabindex="-1"><div class="modal-dialog"><form method="POST" action="adicionar_documento.php" enctype="multipart/form-data" class="modal-content"><input type="hidden" name="csrf_token" value="<?=gerarCsrfToken()?>"><input type="hidden" name="pedido_id" value="<?=$p['id']?>"><div class="modal-header"><h5 class="modal-title fw-bold">Responder pendência</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="small">Pendência: <strong><?=htmlspecialchars($p['motivo_espera'])?></strong></p><div id="docs<?=$modalKey?>"><div class="doc-row border rounded p-2 mb-2"><input name="titulo_documento[]" class="form-control mb-2" maxlength="150" placeholder="Nome do documento" required><input type="file" name="documentos[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div></div><button type="button" class="btn btn-outline-primary btn-sm" onclick="addDoc('<?=$modalKey?>')">+ Adicionar outro arquivo</button></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary fw-bold">Enviar documentos</button></div></form></div></div><?php endif; ?>
<?php endforeach;}; ?>
<div class="tab-pane fade show active" id="tab-todos"><?php $render($todosPedidos,'todos'); ?></div><div class="tab-pane fade" id="tab-analise"><?php $render($pedidosAguardando,'analise'); ?></div><div class="tab-pane fade" id="tab-espera"><?php $render($pedidosEspera,'espera'); ?></div><div class="tab-pane fade" id="tab-vaga"><?php $render($pedidosVaga,'vaga'); ?></div><div class="tab-pane fade" id="tab-agendados"><?php $render($pedidosAgendados,'agendados'); ?></div><div class="tab-pane fade" id="tab-finalizados"><?php $render($pedidosFinalizados,'finalizados'); ?></div>
</div></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script><script>
function addDoc(id){const c=document.getElementById('docs'+id);const row=document.createElement('div');row.className='doc-row border rounded p-2 mb-2';row.innerHTML='<input name="titulo_documento[]" class="form-control mb-2" maxlength="150" placeholder="Nome do documento" required><div class="d-flex gap-2"><input type="file" name="documentos[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required><button type="button" class="btn btn-outline-danger" onclick="this.closest(\'.doc-row\').remove()">Remover</button></div>';c.appendChild(row)}
</script></body></html>
