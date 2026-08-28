<?php
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedido_id = (int)($_POST['pedido_id'] ?? 0);
    $mensagem = trim($_POST['mensagem'] ?? '');

    if (!$pedido_id || !$mensagem) {
        echo 'Dados inválidos.';
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO mensagens_exames (pedido_id, mensagem, status, tipo, robo, criado_em)
        VALUES (?, ?, 'Pendente', 'sms', 0, NOW())
    ");
    $stmt->execute([$pedido_id, $mensagem]);

    echo 'Mensagem enviada com sucesso!';
}
