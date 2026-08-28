
<?php
/* =========================
   ARQUIVO: enviar.php
   ========================= */
session_start();
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $telefone = preg_replace('/\D/', '', $_POST['telefone']);
    $sql = "INSERT INTO servicos 
        (titulo, descricao, responsavel, endereco, telefone, status) 
        VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['titulo'],
        $_POST['descricao'],
        $_POST['responsavel'],
        $_POST['endereco'],
        $telefone,
        'Solicitação recebida'
    ]);

    $_SESSION['telefone'] = $telefone;

    header('Location: login.php');
    exit;
}
