<?php
// helpers/seguranca.php
declare(strict_types=1);

function iniciarSessaoSegura(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $httpsAtivo = isset($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== ''
            && strtolower((string)$_SERVER['HTTPS']) !== 'off';

        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_secure', $httpsAtivo ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
}

function gerarCsrfToken(): string {
    iniciarSessaoSegura();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarCsrfToken(?string $token): bool {
    iniciarSessaoSegura();
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function limparDigitos(?string $str): string {
    return preg_replace('/\D/', '', (string)$str);
}

function validarCPF(string $cpf): bool {
    $cpf = limparDigitos($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += (int)$cpf[$i] * (10 - $i);
    }
    $digito1 = ($soma * 10) % 11;
    if ($digito1 === 10) {
        $digito1 = 0;
    }
    if ((int)$cpf[9] !== $digito1) {
        return false;
    }

    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += (int)$cpf[$i] * (11 - $i);
    }
    $digito2 = ($soma * 10) % 11;
    if ($digito2 === 10) {
        $digito2 = 0;
    }

    return (int)$cpf[10] === $digito2;
}

function validarCNS(string $cns): bool {
    $cns = limparDigitos($cns);
    if (strlen($cns) !== 15) return false;
    $soma = 0;
    $peso = 15;
    for ($i = 0; $i < 15; $i++) {
        $soma += (int)$cns[$i] * $peso--;
    }
    return ($soma % 11) === 0;
}

function validarTelefone(string $tel): bool {
    $num = limparDigitos($tel);
    if (strlen($num) !== 10 && strlen($num) !== 11) return false;
    return !preg_match('/^(\d)\1+$/', $num);
}

function validarSenhaForte(string $senha): array {
    $erros = [];
    if (strlen($senha) < 8) $erros[] = 'pelo menos 8 caracteres';
    if (!preg_match('/[A-Z]/', $senha)) $erros[] = 'pelo menos 1 letra maiúscula';
    if (!preg_match('/[a-z]/', $senha)) $erros[] = 'pelo menos 1 letra minúscula';
    if (!preg_match('/\d/', $senha)) $erros[] = 'pelo menos 1 número';
    return $erros;
}

function formatarCPF(string $cpf): string {
    $c = limparDigitos($cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $c) ?? $cpf;
}

function formatarCNS(string $cns): string {
    $c = limparDigitos($cns);
    return preg_replace('/(\d{3})(\d{4})(\d{4})(\d{4})/', '$1 $2 $3 $4', $c) ?? $cns;
}

function formatarTelefone(string $tel): string {
    $t = limparDigitos($tel);
    if (strlen($t) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $t) ?? $tel;
    }
    return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $t) ?? $tel;
}
