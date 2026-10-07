<?php
// login.php
declare(strict_types=1);
require_once 'conexao.php';
require_once 'helpers/seguranca.php';
require_once 'helpers/auth.php';

iniciarSessaoSegura();

if (!isset($msg)) {
    $msg = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $msg = 'Sessao expirada. Recarregue a pagina e tente novamente.';
    } else {
        $login = trim($_POST['identificador'] ?? '');
        $senha = (string)($_POST['senha'] ?? '');

        $paciente = autenticarPaciente($pdo, $login, $senha);

        if ($paciente) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['paciente_id'] = $paciente['id'];
            $_SESSION['paciente_nome'] = $paciente['nome'];
            $_SESSION['paciente_cpf'] = $paciente['cpf'];
            header('Location: paciente/painel.php');
            exit;
        } else {
            $msg = 'Dados de acesso inválidos. Verifique CPF/CNS/E-mail e senha.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhamento de Solicitações - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/exames.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .box {
            width: 100%;
            max-width: 440px;
            background: #fff;
            padding: 32px 28px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
            border: 1px solid #dfe4ea;
        }
        h2 { text-align: center; color: #2a5298; margin-bottom: 22px; font-size: 26px; font-weight: 700; }
        .btn-entrar {
            width: 100%; padding: 13px; margin-top: 18px; border: none;
            border-radius: 10px; background: #2a5298; color: #fff;
            font-size: 16px; font-weight: 600; cursor: pointer;
        }
        .btn-entrar:hover { background: #1e3c72; }
        .senha-input-wrap { position: relative; }
        .senha-input-wrap .form-control { padding-right: 48px; }
        .senha-visibility {
            position: absolute;
            top: 50%;
            right: 4px;
            width: 40px;
            height: 40px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: #526173;
        }
        .senha-visibility:hover, .senha-visibility:focus-visible { background: #edf2f7; color: #1e3c72; }
    </style>
</head>
<body>
<div class="box">
    <h2>Área do Paciente</h2>
    <?php if (!empty($msg)): ?>
        <div class="alert alert-danger py-2 text-center small"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCsrfToken()) ?>">
        <div class="mb-3">
            <label class="form-label">CPF, Cartão SUS ou E-mail</label>
            <input type="text" name="identificador" class="form-control" placeholder="Digite seu CPF, CNS ou E-mail" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Senha</label>
            <div class="senha-input-wrap">
                <input type="password" name="senha" id="senhaPaciente" class="form-control" placeholder="Sua senha cadastrada" required>
                <button type="button" class="senha-visibility" data-senha-alvo="senhaPaciente" aria-label="Mostrar senha" aria-controls="senhaPaciente" aria-pressed="false">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn-entrar">Entrar no Painel</button>
    </form>

    <div class="text-center mt-3 pt-2 border-top small">
        <div class="mb-2">
            Ainda não tem cadastro? <a href="cadastro_paciente.php" class="fw-bold text-decoration-none">Cadastre-se</a>
        </div>
        <a href="recuperar_senha.php" class="text-secondary text-decoration-none">Esqueceu sua senha?</a>
    </div>
</div>
<script>
    document.querySelectorAll('.senha-visibility').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.senhaAlvo);
            const icon = button.querySelector('i');
            if (!input || !icon) return;

            const mostrar = input.type === 'password';
            input.type = mostrar ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(mostrar));
            button.setAttribute('aria-label', `${mostrar ? 'Ocultar' : 'Mostrar'} senha`);
            icon.classList.toggle('fa-eye', !mostrar);
            icon.classList.toggle('fa-eye-slash', mostrar);
        });
    });
</script>
</body>
</html>
