<?php
// helpers/auth.php
declare(strict_types=1);
require_once __DIR__ . '/seguranca.php';

iniciarSessaoSegura();

function exigirLoginPaciente(): array {
    iniciarSessaoSegura();

    if (empty($_SESSION['paciente_id'])) {
        header('Location: ../login.php');
        exit;
    }

    return [
        'id' => (int)$_SESSION['paciente_id'],
        'nome' => $_SESSION['paciente_nome'] ?? '',
        'cpf' => $_SESSION['paciente_cpf'] ?? '',
    ];
}

function exigirLoginAdmin(): array {
    iniciarSessaoSegura();

    if (empty($_SESSION['admin_id'])) {
        header('Location: ../login_admin.php');
        exit;
    }

    return [
        'id' => (int)$_SESSION['admin_id'],
        'nome' => $_SESSION['admin_nome'] ?? '',
        'perfil' => $_SESSION['admin_perfil'] ?? 'atendente',
    ];
}

function autenticarPaciente(PDO $pdo, string $login, string $senha): ?array {
    $loginLimpo = limparDigitos($login);
    $loginEmail = strtolower(trim($login));

    $sql = "SELECT * FROM pacientes 
            WHERE (cpf = :cpf OR cns = :cns OR email = :email) 
              AND ativo = 1 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cpf'   => $loginLimpo,
        ':cns'   => $loginLimpo,
        ':email' => $loginEmail
    ]);
    $paciente = $stmt->fetch();

    if ($paciente && is_string($paciente['senha'] ?? null) && password_verify($senha, $paciente['senha'])) {
        return $paciente;
    }
    return null;
}

function autenticarAdmin(PDO $pdo, string $email, string $senha): ?array {
    $stmt = $pdo->prepare("SELECT * FROM usuarios_admin WHERE email = ? AND ativo = 1 LIMIT 1");
    $stmt->execute([strtolower(trim($email))]);
    $admin = $stmt->fetch();

    if ($admin && is_string($admin['senha'] ?? null) && password_verify($senha, $admin['senha'])) {
        return $admin;
    }
    return null;
}
