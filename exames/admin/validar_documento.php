<?php
declare(strict_types=1);
require_once '../conexao.php';
require_once '../helpers/auth.php';
require_once '../helpers/seguranca.php';
require_once '../helpers/pedidos.php';

$admin = exigirLoginAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrfToken($_POST['csrf_token'] ?? '')) die('Requisição inválida ou sessão expirada.');
$docId = (int)($_POST['documento_id'] ?? 0);
$acao = $_POST['acao'] ?? '';
$stmt = $pdo->prepare("SELECT d.*, p.protocolo, p.status AS pedido_status, p.paciente_id FROM documentos_exames d INNER JOIN pedidos_exames p ON p.id=d.pedido_id WHERE d.id=? LIMIT 1");
$stmt->execute([$docId]);
$doc = $stmt->fetch();
if (!$doc) die('Documento não encontrado.');
$redirect='paciente_perfil.php?id='.(int)$doc['paciente_id'];
try {
    if ($doc['status'] !== 'pendente') throw new RuntimeException('Somente documentos pendentes podem ser validados ou rejeitados.');
    $statusQuePermiteRejeicao = ['aguardando_analise', 'aguardando_nova_analise', 'em_espera', 'aguardando_vaga'];
    if ($acao === 'rejeitar' && !in_array($doc['pedido_status'], $statusQuePermiteRejeicao, true)) {
        throw new RuntimeException('Documentos nao podem ser rejeitados depois que o pedido foi agendado ou encerrado.');
    }

    $pdo->beginTransaction();
    if ($acao === 'validar') {
        $up=$pdo->prepare("UPDATE documentos_exames SET status='validado', validado_por=?, validado_em=NOW(), observacao_admin=NULL WHERE id=?");
        $up->execute([$admin['id'],$docId]);
        registrarHistorico($pdo,(int)$doc['pedido_id'],$doc['pedido_status'],$doc['pedido_status'],'Documento validado','Documento "'.$doc['titulo_documento'].'" validado pelo administrador.','admin',$admin['id'],$admin['nome']);
    } elseif ($acao === 'rejeitar') {
        $obs=trim((string)($_POST['observacao']??''));
        if (mb_strlen($obs)<10) throw new RuntimeException('Informe um motivo de rejeição com pelo menos 10 caracteres.');
        $up=$pdo->prepare("UPDATE documentos_exames SET status='rejeitado', validado_por=?, validado_em=NOW(), observacao_admin=? WHERE id=?");
        $up->execute([$admin['id'],$obs,$docId]);
        $motivo='Documento "'.$doc['titulo_documento'].'" rejeitado: '.$obs;
        $upPed=$pdo->prepare("UPDATE pedidos_exames SET status='em_espera', motivo_espera=?, espera_desde=NOW(), ultima_resposta_paciente=NULL, data_entrada_fila=NULL WHERE id=? AND status IN ('aguardando_analise','aguardando_nova_analise','em_espera','aguardando_vaga')");
        $upPed->execute([$motivo,$doc['pedido_id']]);
        if ($upPed->rowCount() !== 1) throw new RuntimeException('O status do pedido mudou. Atualize a pagina e tente novamente.');
        registrarHistorico($pdo,(int)$doc['pedido_id'],$doc['pedido_status'],'em_espera','Documento rejeitado',$motivo,'admin',$admin['id'],$admin['nome']);
    } else { throw new RuntimeException('Ação de documento inválida.'); }
    $pdo->commit();
} catch (Throwable $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_erro']=$e->getMessage();
}
header('Location: '.$redirect); exit;
