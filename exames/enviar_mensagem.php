<?php
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedido_id = (int)($_POST['pedido_id'] ?? 0);
    $mensagem = trim($_POST['mensagem'] ?? '');
    $canalEnvio = 'sms';

    if (!$pedido_id || !$mensagem) {
        echo 'Dados inválidos.';
        exit;
    }

    // O campo "tipo" nesta tabela representa o canal de envio da mensagem.
    $stmt = $pdo->prepare("
        INSERT INTO mensagens_exames (pedido_id, mensagem, status, tipo, robo, criado_em)
        VALUES (?, ?, 'Pendente', ?, 0, NOW())
    ");
    $stmt->execute([$pedido_id, $mensagem, $canalEnvio]);

    echo 'Mensagem enviada com sucesso!';
}
