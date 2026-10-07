<?php
// admin/acoes_pedido.php
declare(strict_types=1);
require_once '../conexao.php';
require_once '../helpers/auth.php';
require_once '../helpers/seguranca.php';
require_once '../helpers/pedidos.php';

$admin = exigirLoginAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrfToken($_POST['csrf_token'] ?? '')) {
    die('Requisição inválida ou sessão expirada.');
}

$pedidoId = (int)($_POST['pedido_id'] ?? 0);
$acao = trim($_POST['acao'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM pedidos_exames WHERE id = ?");
$stmt->execute([$pedidoId]);
$pedido = $stmt->fetch();
if (!$pedido) die('Pedido não encontrado.');

$statusAntigo = $pedido['status'];
$redirecionar = "paciente_perfil.php?id=" . $pedido['paciente_id'];

function motivoObrigatorio(string $motivo, int $minimo = 20): string
{
    $motivo = trim($motivo);
    if (mb_strlen($motivo) < $minimo) {
        throw new RuntimeException("A justificativa deve ter pelo menos {$minimo} caracteres.");
    }
    return $motivo;
}

try {
    switch ($acao) {
        case 'aprovar_vaga':
            if (!in_array($statusAntigo, ['aguardando_analise', 'aguardando_nova_analise'], true)) {
                throw new RuntimeException('Este pedido não está aguardando análise.');
            }
            $pdo->beginTransaction();
            $up = $pdo->prepare("UPDATE pedidos_exames SET status = 'aguardando_vaga', data_entrada_fila = NOW(), motivo_espera = NULL, espera_desde = NULL, ultima_resposta_paciente = NULL WHERE id = ?");
            $up->execute([$pedidoId]);
            registrarHistorico($pdo, $pedidoId, $statusAntigo, 'aguardando_vaga', 'Aprovado na triagem e colocado na fila', null, 'admin', $admin['id'], $admin['nome']);
            $pdo->commit();
            break;

        case 'colocar_espera':
            if (!in_array($statusAntigo, ['aguardando_analise', 'aguardando_nova_analise', 'aguardando_vaga'], true)) {
                throw new RuntimeException('Este pedido não pode ser colocado em espera neste momento.');
            }
            $motivo = motivoObrigatorio($_POST['motivo_espera'] ?? '', 10);
            $pdo->beginTransaction();
            $up = $pdo->prepare("UPDATE pedidos_exames SET status = 'em_espera', motivo_espera = ?, espera_desde = NOW(), data_entrada_fila = NULL, ultima_resposta_paciente = NULL WHERE id = ?");
            $up->execute([$motivo, $pedidoId]);
            registrarHistorico($pdo, $pedidoId, $statusAntigo, 'em_espera', 'Pedido colocado em espera', $motivo, 'admin', $admin['id'], $admin['nome']);
            $pdo->commit();
            break;

        case 'liberar_espera':
            if ($statusAntigo !== 'em_espera') {
                throw new RuntimeException('O pedido não está em espera.');
            }
            $pdo->beginTransaction();
            $up = $pdo->prepare("UPDATE pedidos_exames SET status = 'aguardando_vaga', motivo_espera = NULL, espera_desde = NULL, ultima_resposta_paciente = NULL, data_entrada_fila = NOW() WHERE id = ?");
            $up->execute([$pedidoId]);
            registrarHistorico($pdo, $pedidoId, $statusAntigo, 'aguardando_vaga', 'Liberado da espera e colocado na fila', null, 'admin', $admin['id'], $admin['nome']);
            $pdo->commit();
            break;

        case 'agendar_pedido':
            if (!in_array($statusAntigo, ['aguardando_vaga', 'agendado'], true)) {
                throw new RuntimeException('Somente pedidos na fila ou já agendados podem ser agendados.');
            }
            $dataHora = trim($_POST['data_hora'] ?? '');
            $local = trim($_POST['local_unidade'] ?? '');
            $prof = trim($_POST['profissional'] ?? '');
            $orientacoes = trim($_POST['orientacoes'] ?? '');
            if (!$dataHora || !$local) throw new RuntimeException('Informe a data, horário e unidade.');
            $dataSql = date('Y-m-d H:i:s', strtotime($dataHora));
            if ($dataSql === '1970-01-01 00:00:00') throw new RuntimeException('Data e horário inválidos.');

            $pdo->beginTransaction();
            $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE pedido_id = ? AND status IN ('agendado','confirmado')")->execute([$pedidoId]);
            $insAg = $pdo->prepare("INSERT INTO agendamentos (pedido_id, data_hora, local_unidade, profissional, orientacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?)");
            $insAg->execute([$pedidoId, $dataSql, $local, $prof ?: null, $orientacoes ?: null, $admin['id']]);
            $up = $pdo->prepare("UPDATE pedidos_exames SET status = 'agendado', data_entrada_fila = NULL WHERE id = ?");
            $up->execute([$pedidoId]);
            $acaoHistorico = $statusAntigo === 'agendado' ? 'Agendamento alterado' : 'Pedido agendado';
            registrarHistorico($pdo, $pedidoId, $statusAntigo, 'agendado', $acaoHistorico, $orientacoes ?: "Agendado para {$dataSql} em {$local}", 'admin', $admin['id'], $admin['nome']);
            $pdo->commit();
            break;

        case 'voltar_fila':
            if ($statusAntigo !== 'agendado') throw new RuntimeException('Somente pedidos agendados podem voltar para a fila.');
            $motivo = motivoObrigatorio($_POST['motivo'] ?? '', 20);
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE pedido_id = ? AND status IN ('agendado','confirmado')")->execute([$pedidoId]);
            $pdo->prepare("UPDATE pedidos_exames SET status = 'aguardando_vaga', data_entrada_fila = NOW(), motivo_cancelamento = NULL WHERE id = ?")->execute([$pedidoId]);
            registrarHistorico($pdo, $pedidoId, $statusAntigo, 'aguardando_vaga', 'Retornado para a fila', $motivo, 'admin', $admin['id'], $admin['nome']);
            $pdo->commit();
            break;

        case 'cancelar':
            if (in_array($statusAntigo, ['finalizado', 'cancelado'], true)) throw new RuntimeException('Este pedido não pode mais ser cancelado.');
            $motivo = motivoObrigatorio($_POST['motivo'] ?? '', 20);
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE pedido_id = ? AND status IN ('agendado','confirmado')")->execute([$pedidoId]);
            $pdo->prepare("UPDATE pedidos_exames SET status = 'cancelado', data_entrada_fila = NULL, motivo_cancelamento = ? WHERE id = ?")->execute([$motivo, $pedidoId]);
            registrarHistorico($pdo, $pedidoId, $statusAntigo, 'cancelado', 'Pedido cancelado', $motivo, 'admin', $admin['id'], $admin['nome']);
            $pdo->commit();
            break;

        case 'marcar_emergencia':
            if ($statusAntigo !== 'aguardando_vaga') throw new RuntimeException('Somente pedidos na fila podem ser marcados como emergência.');
            $pdo->prepare("UPDATE pedidos_exames SET emergencia = 1, prioridade = 1 WHERE id = ?")->execute([$pedidoId]);
            registrarHistorico($pdo, $pedidoId, $statusAntigo, $statusAntigo, 'Classificado como Emergência', null, 'admin', $admin['id'], $admin['nome']);
            break;

        case 'remover_emergencia':
            if ($statusAntigo !== 'aguardando_vaga') throw new RuntimeException('Somente pedidos na fila podem ter a emergência removida.');
            $pdo->prepare("UPDATE pedidos_exames SET emergencia = 0, prioridade = 2 WHERE id = ?")->execute([$pedidoId]);
            registrarHistorico($pdo, $pedidoId, $statusAntigo, $statusAntigo, 'Emergência desmarcada', null, 'admin', $admin['id'], $admin['nome']);
            break;

        case 'finalizar':
            if ($statusAntigo !== 'agendado') throw new RuntimeException('A ordem só pode ser concluída depois de ser agendada.');
            $observacao = trim($_POST['observacao_conclusao'] ?? '');
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE pedido_id = ? AND status IN ('agendado','confirmado')")->execute([$pedidoId]);
            $pdo->prepare("UPDATE pedidos_exames SET status = 'finalizado' WHERE id = ?")->execute([$pedidoId]);
            registrarHistorico($pdo, $pedidoId, $statusAntigo, 'finalizado', 'Procedimento concluído', $observacao ?: null, 'admin', $admin['id'], $admin['nome']);
            $pdo->commit();
            break;

        default:
            throw new RuntimeException('Ação administrativa inválida.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_erro'] = $e->getMessage();
}

header('Location: ' . $redirecionar);
exit;
