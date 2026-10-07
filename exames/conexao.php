<?php
// conexao.php
declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'servicos';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS');

if ($pass === false) {
    $pass = '';
}

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Erro na conexão: ' . $e->getMessage());
    die('Erro ao conectar com o banco de dados. Verifique as credenciais no arquivo conexao.php.');
}
