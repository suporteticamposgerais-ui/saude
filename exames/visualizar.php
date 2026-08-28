<?php
require 'conexao.php';

if (!isset($_POST['id'])) exit;

$id = (int)$_POST['id'];

$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->prepare(
    "UPDATE pedidos_exames 
     SET status='Visualizado', robo=0 
     WHERE id=?"
)->execute([$id]);

echo 'OK';
