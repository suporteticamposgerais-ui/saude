<?php
// helpers/pedidos.php
declare(strict_types=1);

function gerarProtocolo(PDO $pdo): string
{
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    // Dia + hora + segundos
    $protocolo = date('dHs');

    // Completa com 4 caracteres aleatórios
    for ($i = 0; $i < 4; $i++) {
        $protocolo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }

    return $protocolo;
}

function gerarColocacao(PDO $pdo, int $exameId): int
{
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(colocacao), 0) + 1
        FROM pedidos_exames
        WHERE exame_id = ?
    ");

    $stmt->execute([$exameId]);

    return (int)$stmt->fetchColumn();
}

function registrarHistorico(
    PDO $pdo,
    int $pedidoId,
    ?string $statusAnterior,
    string $statusNovo,
    string $acao,
    ?string $observacao,
    string $autorTipo,
    ?int $autorId,
    string $autorNome
): void {
    $stmt = $pdo->prepare("
        INSERT INTO historico_pedidos 
        (pedido_id, status_anterior, status_novo, acao, observacao, autor_tipo, autor_id, autor_nome)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $pedidoId,
        $statusAnterior,
        $statusNovo,
        $acao,
        $observacao,
        $autorTipo,
        $autorId,
        $autorNome
    ]);
}

function criarNotificacaoAdmin(PDO $pdo, int $pedidoId, string $tipo, string $mensagem): void
{
    $stmt = $pdo->prepare("
        INSERT INTO notificacoes_admin (pedido_id, tipo, mensagem)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$pedidoId, $tipo, $mensagem]);
}

function formatarTempoDecorrido(string $data): string
{
    $inicio = new DateTime($data);
    $agora = new DateTime();
    $diff = $inicio->diff($agora);
    $partes = [];
    if ($diff->d > 0) $partes[] = $diff->d . ($diff->d === 1 ? ' dia' : ' dias');
    if ($diff->h > 0) $partes[] = $diff->h . ($diff->h === 1 ? ' hora' : ' horas');
    if ($diff->i > 0 && count($partes) < 2) $partes[] = $diff->i . ($diff->i === 1 ? ' minuto' : ' minutos');
    if (!$partes) return 'menos de 1 minuto';
    return implode(' e ', $partes);
}
