<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este utilitario so pode ser executado pelo terminal.\n");
}

if (!in_array('--confirm', $argv, true)) {
    fwrite(STDERR, "Este script criara ate 50 pacientes de teste no banco conectado.\n" .
        "Execute novamente com --confirm para continuar.\n");
    exit(2);
}

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../helpers/seguranca.php';

const TOTAL_PACIENTES_TESTE = 50;
const SENHA_PACIENTE_TESTE = 'Teste@123';

function gerarCpfTeste(PDO $pdo, int $numero, array &$cpfsUsados): string
{
    for ($tentativa = 0; $tentativa < 10000; $tentativa++) {
        $base = str_pad((string)(100000000 + (($numero - 1) * 10000) + $tentativa), 9, '0', STR_PAD_LEFT);
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += (int)$base[$i] * (10 - $i);
        }
        $digito1 = ($soma * 10) % 11;
        if ($digito1 === 10) {
            $digito1 = 0;
        }

        $cpfDez = $base . $digito1;
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int)$cpfDez[$i] * (11 - $i);
        }
        $digito2 = ($soma * 10) % 11;
        if ($digito2 === 10) {
            $digito2 = 0;
        }
        $cpf = $cpfDez . $digito2;

        if (isset($cpfsUsados[$cpf]) || !validarCPF($cpf)) {
            continue;
        }

        $stmt = $pdo->prepare('SELECT 1 FROM pacientes WHERE cpf = ? LIMIT 1');
        $stmt->execute([$cpf]);
        if (!$stmt->fetchColumn()) {
            $cpfsUsados[$cpf] = true;
            return $cpf;
        }
    }

    throw new RuntimeException('Nao foi possivel gerar um CPF de teste livre.');
}

function gerarCnsTeste(PDO $pdo, int $numero, array &$cnsUsados): string
{
    for ($tentativa = 0; $tentativa < 10000; $tentativa++) {
        $base14 = substr('710' . str_pad((string)(10000000000 + ($numero * 10000) + $tentativa), 11, '0', STR_PAD_LEFT), 0, 14);
        for ($ultimoDigito = 0; $ultimoDigito <= 9; $ultimoDigito++) {
            $cns = $base14 . $ultimoDigito;
            if (isset($cnsUsados[$cns]) || !validarCNS($cns)) {
                continue;
            }

            $stmt = $pdo->prepare('SELECT 1 FROM pacientes WHERE cns = ? LIMIT 1');
            $stmt->execute([$cns]);
            if (!$stmt->fetchColumn()) {
                $cnsUsados[$cns] = true;
                return $cns;
            }
        }
    }

    throw new RuntimeException('Nao foi possivel gerar um CNS de teste livre.');
}

function dataNascimentoTeste(int $numero): string
{
    $ano = 1978 + (($numero - 1) % 23);
    $mes = (($numero - 1) % 12) + 1;
    $dia = (($numero * 3 - 1) % 25) + 1;
    return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
}

$dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
echo "Banco conectado: {$dbName}\n";
echo "Contas alvo: usuario.teste01@example.com ate usuario.teste50@example.com\n";
echo "Senha das contas novas: " . SENHA_PACIENTE_TESTE . "\n\n";

$hashSenha = password_hash(SENHA_PACIENTE_TESTE, PASSWORD_DEFAULT);
if (!is_string($hashSenha)) {
    throw new RuntimeException('Nao foi possivel criar o hash da senha de teste.');
}

$stmtBusca = $pdo->prepare('SELECT id FROM pacientes WHERE email = ? LIMIT 1');
$stmtInsert = $pdo->prepare(<<<'SQL'
    INSERT INTO pacientes
        (nome, cpf, cns, data_nascimento, telefone, email, cep, rua, numero, bairro, cidade, estado, complemento, senha, ativo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
SQL);

$cpfsUsados = [];
$cnsUsados = [];
$criados = 0;
$ignorados = 0;
$erros = [];

for ($i = 1; $i <= TOTAL_PACIENTES_TESTE; $i++) {
    $sufixo = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
    $email = "usuario.teste{$sufixo}@example.com";

    try {
        $stmtBusca->execute([$email]);
        if ($stmtBusca->fetchColumn()) {
            $ignorados++;
            echo "{$email}: ja existe; mantido sem alteracao.\n";
            continue;
        }

        $cpf = gerarCpfTeste($pdo, $i, $cpfsUsados);
        $cns = gerarCnsTeste($pdo, $i, $cnsUsados);
        $telefone = '3599999' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
        if (!validarTelefone($telefone)) {
            throw new RuntimeException('O telefone sintetico nao passou na validacao.');
        }

        $stmtInsert->execute([
            "Usuario Teste {$sufixo}",
            $cpf,
            $cns,
            dataNascimentoTeste($i),
            $telefone,
            $email,
            '37160000',
            "Rua de Teste {$sufixo}",
            (string)(100 + $i),
            'Bairro de Teste',
            'Campos Gerais',
            'MG',
            "Casa {$sufixo}",
            $hashSenha
        ]);

        $criados++;
        echo "{$email}: criado (ID {$pdo->lastInsertId()}).\n";
    } catch (Throwable $e) {
        $erros[] = "{$email}: {$e->getMessage()}";
        fwrite(STDERR, "ERRO {$email}: {$e->getMessage()}\n");
    }
}

echo "\nResumo: {$criados} criados; {$ignorados} ja existentes; " . count($erros) . " erros.\n";
if ($erros) {
    exit(1);
}
