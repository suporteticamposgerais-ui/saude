<?php
// redefinir_senha.php
declare(strict_types=1);
require_once 'conexao.php';
require_once 'helpers/seguranca.php';

$token = trim($_GET['token'] ?? '');
$erro = '';
$sucesso = false;
$tokenHash = hash('sha256', $token);

$stmt = $pdo->prepare("SELECT * FROM recuperacao_senhas WHERE (token = ? OR token = ?) AND usado = 0 AND expira_em > NOW() LIMIT 1");
$stmt->execute([$tokenHash, $token]);
$rec = $stmt->fetch();

if (!$rec) {
    http_response_code(404);
    die('Link de redefinicao invalido, expirado ou ja utilizado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessao expirada. Recarregue o link e tente novamente.';
    } else {
        $novaSenha = (string)($_POST['senha'] ?? '');
        $novaConf  = (string)($_POST['senha_conf'] ?? '');

        if ($requisitos = validarSenhaForte($novaSenha)) {
            $erro = 'A nova senha nao atende aos requisitos: ' . implode(', ', $requisitos) . '.';
        } elseif ($novaSenha !== $novaConf) {
            $erro = 'As senhas digitadas nao coincidem.';
        } else {
            try {
                $pdo->beginTransaction();
                $lockPaciente = $pdo->prepare('SELECT id FROM pacientes WHERE id = ? FOR UPDATE');
                $lockPaciente->execute([$rec['usuario_id']]);
                if (!$lockPaciente->fetchColumn()) {
                    throw new RuntimeException('Conta nao encontrada.');
                }

                $lockToken = $pdo->prepare('SELECT id FROM recuperacao_senhas WHERE id = ? AND usado = 0 AND expira_em > NOW() FOR UPDATE');
                $lockToken->execute([$rec['id']]);
                if (!$lockToken->fetchColumn()) {
                    throw new RuntimeException('Link invalido ou ja utilizado.');
                }

                $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $upPac = $pdo->prepare('UPDATE pacientes SET senha = ? WHERE id = ?');
                $upPac->execute([$hash, $rec['usuario_id']]);

                $upTokens = $pdo->prepare("UPDATE recuperacao_senhas SET usado = 1 WHERE tipo_usuario = 'paciente' AND usuario_id = ? AND usado = 0");
                $upTokens->execute([$rec['usuario_id']]);
                $pdo->commit();
                $sucesso = true;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $erro = 'Nao foi possivel redefinir a senha. Solicite um novo link.';
                error_log('Falha na redefinicao de senha: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/exames.css" rel="stylesheet">
    <style>
        body { background: #f4f6f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .box-red { max-width: 440px; width: 100%; background: #fff; border-radius: 14px; border: 1px solid #dfe4ea; padding: 30px; }
    .senha-requisitos div{transition:.15s}.senha-requisitos .text-success{font-weight:600}.senha-requisitos .text-danger{font-weight:600}</style>
</head>
<body>
<div class="box-red shadow-sm">
    <h3 class="text-center text-primary fw-bold mb-3">Criar Nova Senha</h3>
    <?php if ($erro): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alert alert-success text-center py-3">
            <p>Sua senha foi redefinida com sucesso!</p>
            <a href="login.php" class="btn btn-primary fw-bold">Fazer Login</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gerarCsrfToken()) ?>">
            <div class="mb-3">
                <label class="form-label">Nova Senha</label>
                <input type="password" name="senha" id="senha" class="form-control" required minlength="8" autocomplete="new-password">
                <div class="senha-requisitos mt-2 small" id="senhaRequisitos"><div data-regra="tamanho">○ Pelo menos 8 caracteres</div><div data-regra="maiuscula">○ Pelo menos 1 letra maiúscula</div><div data-regra="minuscula">○ Pelo menos 1 letra minúscula</div><div data-regra="numero">○ Pelo menos 1 número</div></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirmar Nova Senha</label>
                <input type="password" name="senha_conf" id="senha_conf" class="form-control" required minlength="8" autocomplete="new-password">
                <div id="senhaConfirmacao" class="small mt-2"></div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Salvar Nova Senha</button>
        </form>
    <?php endif; ?>
</div>
<script>const senhaInput=document.getElementById('senha'),senhaConfInput=document.getElementById('senha_conf');function atualizar(){const v=senhaInput?.value||'';const r={tamanho:v.length>=8,maiuscula:/[A-Z]/.test(v),minuscula:/[a-z]/.test(v),numero:/\d/.test(v)};Object.entries(r).forEach(([k,ok])=>{const e=document.querySelector(`[data-regra="${k}"]`);if(e){e.textContent=(ok?'✓ ':'○ ')+e.textContent.replace(/^[✓○] /,'');e.classList.toggle('text-success',ok);e.classList.toggle('text-danger',!ok&&v.length>0)}});if(senhaConfInput){const c=document.getElementById('senhaConfirmacao');if(!senhaConfInput.value)c.textContent='';else{const ok=v===senhaConfInput.value;c.textContent=ok?'✓ As senhas conferem.':'✗ As senhas não conferem.';c.className='small mt-2 '+(ok?'text-success':'text-danger')}}}senhaInput?.addEventListener('input',atualizar);senhaConfInput?.addEventListener('input',atualizar);</script>
</body>
</html>
