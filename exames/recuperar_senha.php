<?php
// recuperar_senha.php
declare(strict_types=1);
require_once 'conexao.php';
require_once 'helpers/seguranca.php';

$msg = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $msg = 'Sessao expirada. Recarregue a pagina e tente novamente.';
    } else {
        $sucesso = true;
        $msg = 'Se os dados corresponderem a uma conta ativa e o envio de e-mail estiver configurado, voce recebera um link de redefinicao valido por duas horas.';
        $cpf = limparDigitos($_POST['cpf'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        $stmt = $pdo->prepare("SELECT id, nome, email FROM pacientes WHERE cpf = ? AND email = ? AND ativo = 1");
        $stmt->execute([$cpf, $email]);
        $paciente = $stmt->fetch();

        $publicUrl = rtrim((string)(getenv('APP_PUBLIC_URL') ?: ''), '/');
        $mailFrom = trim((string)(getenv('APP_MAIL_FROM') ?: ''));
        $urlParts = $publicUrl !== '' ? parse_url($publicUrl) : false;
        $mailConfigurado = is_array($urlParts)
            && in_array(strtolower((string)($urlParts['scheme'] ?? '')), ['http', 'https'], true)
            && !empty($urlParts['host'])
            && filter_var($mailFrom, FILTER_VALIDATE_EMAIL);

        if ($paciente && $mailConfigurado) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expira = date('Y-m-d H:i:s', strtotime('+2 hours'));

            try {
                $pdo->beginTransaction();
                $revogar = $pdo->prepare("UPDATE recuperacao_senhas SET usado = 1 WHERE tipo_usuario = 'paciente' AND usuario_id = ? AND usado = 0");
                $revogar->execute([$paciente['id']]);
                $ins = $pdo->prepare("INSERT INTO recuperacao_senhas (tipo_usuario, usuario_id, token, expira_em) VALUES ('paciente', ?, ?, ?)");
                $ins->execute([$paciente['id'], $tokenHash, $expira]);
                $pdo->commit();

                $link = $publicUrl . '/redefinir_senha.php?token=' . rawurlencode($token);
                $assunto = 'Redefinicao da senha - Portal de Saude';
                $corpo = "Ola, {$paciente['nome']}.\n\nUse o link abaixo para redefinir sua senha. Ele expira em duas horas e so pode ser usado uma vez.\n\n{$link}\n\nSe voce nao pediu essa alteracao, ignore esta mensagem.";
                $cabecalhos = "From: {$mailFrom}\r\nContent-Type: text/plain; charset=UTF-8";
                if (!mail($paciente['email'], $assunto, $corpo, $cabecalhos)) {
                    $revogarToken = $pdo->prepare('UPDATE recuperacao_senhas SET usado = 1 WHERE token = ?');
                    $revogarToken->execute([$tokenHash]);
                    error_log('Falha ao enviar email de recuperacao de senha.');
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Falha ao preparar recuperacao de senha: ' . $e->getMessage());
            }
        } elseif ($paciente) {
            error_log('Recuperacao de senha nao enviada: configure APP_PUBLIC_URL e APP_MAIL_FROM.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/exames.css" rel="stylesheet">
    <style>
        body { background: #f4f6f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .box-rec { max-width: 440px; width: 100%; background: #fff; border-radius: 14px; border: 1px solid #dfe4ea; padding: 30px; }
    </style>
</head>
<body>
<div class="box-rec shadow-sm">
    <h3 class="text-center text-primary fw-bold mb-3">Recuperar Senha</h3>
    <?php if ($msg): ?>
        <div class="alert <?= $sucesso ? 'alert-success' : 'alert-danger' ?> small"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (!$sucesso): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCsrfToken()) ?>">
            <div class="mb-3">
                <label class="form-label">Informe seu CPF</label>
                <input type="text" name="cpf" class="form-control" placeholder="000.000.000-00" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Informe seu E-mail Cadastrado</label>
                <input type="email" name="email" class="form-control" placeholder="seuemail@exemplo.com" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Gerar Redefinição</button>
        </form>
    <?php endif; ?>

    <div class="text-center mt-3">
        <a href="login.php" class="text-decoration-none small">← Voltar ao Login</a>
    </div>
</div>
</body>
</html>
