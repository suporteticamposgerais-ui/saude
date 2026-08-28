<?php 
$host = "localhost";
$dbname = "servicos";  // ✅ CORRETO
$user = "root";
$pass = "cadastros_1@TI";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());

}