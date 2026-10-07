<?php
// login_admin.php
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
        $email = trim($_POST['email'] ?? '');
        $senha = (string)($_POST['senha'] ?? '');

        $admin = autenticarAdmin($pdo, $email, $senha);

        if ($admin) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nome'] = $admin['nome'];
            $_SESSION['admin_perfil'] = $admin['perfil'];
            header('Location: admin/listagem_ordens.php');
            exit;
        } else {
            $msg = 'E-mail ou senha administrativa inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Administrativo - Regulação de Saúde</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/exames.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .box-admin {
            width: 100%;
            max-width: 420px;
            background: #fff;
            padding: 32px 28px;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .25);
        }
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
<div class="box-admin">
    <div class="text-center mb-3">
        <h3 class="fw-bold text-primary">Regulação / Gestão</h3>
        <p class="text-muted small">Acesso restrito aos operadores de saúde</p>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-danger py-2 text-center small"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCsrfToken()) ?>">
        <div class="mb-3">
            <label class="form-label">E-mail Administrativo</label>
            <input type="email" name="email" class="form-control" placeholder="Digite seu e-mail" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Senha</label>
            <div class="senha-input-wrap">
                <input type="password" name="senha" id="senhaAdmin" class="form-control" placeholder="Senha do operador" required>
                <button type="button" class="senha-visibility" data-senha-alvo="senhaAdmin" aria-label="Mostrar senha" aria-controls="senhaAdmin" aria-pressed="false">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Acessar Sistema</button>
    </form>
    <div class="text-center mt-3">
        <a href="index.php" class="text-secondary small text-decoration-none">← Voltar para o Portal</a>
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
