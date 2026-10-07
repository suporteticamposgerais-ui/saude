<?php
// cadastro_paciente.php
declare(strict_types=1);
require_once 'conexao.php';
require_once 'helpers/seguranca.php';

$erro = '';
$sucesso = false;
iniciarSessaoSegura();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $nome        = trim($_POST['nome'] ?? '');
        $cpf         = limparDigitos($_POST['cpf'] ?? '');
        $cns         = limparDigitos($_POST['cns'] ?? '');
        $nascimento  = trim($_POST['data_nascimento'] ?? '');
        $telefone    = limparDigitos($_POST['telefone'] ?? '');
        $email       = strtolower(trim($_POST['email'] ?? ''));
        $rua         = trim($_POST['rua'] ?? '');
        $numero      = trim($_POST['numero'] ?? '');
        $bairro      = trim($_POST['bairro'] ?? '');
        $complemento = trim($_POST['complemento'] ?? '');
        $senha       = (string)($_POST['senha'] ?? '');
        $senhaConf   = (string)($_POST['senha_conf'] ?? '');

        if (!$nome || strlen($nome) < 3) {
            $erro = 'Informe seu nome completo.';
        } elseif (!validarCPF($cpf)) {
            $erro = 'CPF informado é inválido.';
        } elseif (!validarCNS($cns)) {
            $erro = 'Cartão SUS (CNS) inválido.';
        } elseif (!$nascimento || strtotime($nascimento) === false) {
            $erro = 'Data de nascimento inválida.';
        } elseif (!validarTelefone($telefone)) {
            $erro = 'Telefone inválido (informe DDD + 8 ou 9 dígitos).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail informado é inválido.';
        } elseif (!$rua || !$numero || !$bairro) {
            $erro = 'Endereço incompleto. Preencha Rua, Número e Bairro.';
        } elseif ($requisitosSenha = validarSenhaForte($senha)) {
            $erro = 'A senha não atende aos requisitos: ' . implode(', ', $requisitosSenha) . '.';
        } elseif ($senha !== $senhaConf) {
            $erro = 'As senhas digitadas não conferem.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE cpf = ? OR cns = ? OR email = ? LIMIT 1");
            $stmt->execute([$cpf, $cns, $email]);
            if ($stmt->fetch()) {
                $erro = 'Já existe um paciente cadastrado com este CPF, CNS ou E-mail.';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("
                    INSERT INTO pacientes 
                    (nome, cpf, cns, data_nascimento, telefone, email, rua, numero, bairro, complemento, senha)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([
                    $nome, $cpf, $cns, $nascimento, $telefone, $email, $rua, $numero, $bairro, $complemento, $hash
                ]);
                $sucesso = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Paciente - Saúde Municipal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/exames.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .container-cadastro { max-width: 840px; margin: 25px auto; }
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
        .senha-requisitos { display: grid; gap: 5px; padding: 0; margin-bottom: 0; list-style: none; }
        .senha-requisitos li { display: flex; align-items: center; gap: 8px; color: #64748b; transition: color .15s ease; }
        .senha-requisitos li i { width: 16px; text-align: center; }
        .senha-requisitos li.valido { color: #15803d; font-weight: 600; }
        #senhaConfirmacao.valido { color: #15803d; }
        #senhaConfirmacao.invalido { color: #b42318; }
    </style>
</head>
<body>
<div class="container container-cadastro">
    <div class="card-box">
        <div class="card-header-custom">Criar Conta de Paciente</div>
        <div class="p-4 bg-light">
            <?php if ($erro): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alert alert-success text-center py-4">
                    <h4>Cadastro concluído com sucesso!</h4>
                    <p class="mb-3">Você agora pode acessar sua conta utilizando seu CPF, Cartão SUS ou E-mail.</p>
                    <a href="login.php" class="btn btn-primary px-4 py-2 fw-bold">Fazer Login Agora</a>
                </div>
            <?php else: ?>
                <form method="POST" id="formCadastro">
                    <input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>">

                    <h5 class="section-title">1. Dados Pessoais</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CPF</label>
                            <input type="text" name="cpf" id="cpf" maxlength="14" class="form-control" placeholder="000.000.000-00" required value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data de Nascimento</label>
                            <input type="date" name="data_nascimento" class="form-control" required value="<?= htmlspecialchars($_POST['data_nascimento'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cartão SUS (CNS)</label>
                            <input type="text" name="cns" id="cns" maxlength="18" class="form-control" placeholder="000 0000 0000 0000" required value="<?= htmlspecialchars($_POST['cns'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefone (WhatsApp)</label>
                            <input type="tel" name="telefone" id="telefone" class="form-control" placeholder="(00) 00000-0000" maxlength="15" required value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <h5 class="section-title">2. Endereço Residencial</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Rua / Logradouro</label>
                            <input type="text" name="rua" class="form-control" required value="<?= htmlspecialchars($_POST['rua'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Número</label>
                            <input type="text" name="numero" class="form-control" required value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bairro</label>
                            <input type="text" name="bairro" class="form-control" required value="<?= htmlspecialchars($_POST['bairro'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Complemento (opcional)</label>
                            <input type="text" name="complemento" class="form-control" value="<?= htmlspecialchars($_POST['complemento'] ?? '') ?>">
                        </div>
                    </div>

                    <h5 class="section-title">3. Senha de Acesso</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Senha</label>
                            <div class="senha-input-wrap">
                                <input type="password" name="senha" id="senha" class="form-control" required minlength="8" autocomplete="new-password">
                                <button type="button" class="senha-visibility" data-senha-alvo="senha" aria-label="Mostrar senha" aria-controls="senha" aria-pressed="false">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <ul class="senha-requisitos mt-2 small" id="senhaRequisitos" aria-live="polite" aria-label="Requisitos da senha">
                                <li data-regra="tamanho"><i class="fa-regular fa-circle" aria-hidden="true"></i><span>Pelo menos 8 caracteres</span></li>
                                <li data-regra="maiuscula"><i class="fa-regular fa-circle" aria-hidden="true"></i><span>Pelo menos 1 letra maiúscula</span></li>
                                <li data-regra="minuscula"><i class="fa-regular fa-circle" aria-hidden="true"></i><span>Pelo menos 1 letra minúscula</span></li>
                                <li data-regra="numero"><i class="fa-regular fa-circle" aria-hidden="true"></i><span>Pelo menos 1 número</span></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmar Senha</label>
                            <div class="senha-input-wrap">
                                <input type="password" name="senha_conf" id="senha_conf" class="form-control" required minlength="8" autocomplete="new-password">
                                <button type="button" class="senha-visibility" data-senha-alvo="senha_conf" aria-label="Mostrar confirmação da senha" aria-controls="senha_conf" aria-pressed="false">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div id="senhaConfirmacao" class="small mt-2" aria-live="polite"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-4 py-2 fw-bold">Criar Minha Conta</button>
                    <div class="text-center mt-3 small">
                        Já possui cadastro? <a href="login.php" class="fw-bold text-decoration-none">Entrar na minha conta</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    const cpfInput = document.getElementById('cpf');
    const cnsInput = document.getElementById('cns');
    const telInput = document.getElementById('telefone');
    const senhaInput = document.getElementById('senha');
    const senhaConfirmacaoInput = document.getElementById('senha_conf');

    cpfInput?.addEventListener('input', e => {
        let v = e.target.value.replace(/\D/g, '').slice(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = v;
    });

    cnsInput?.addEventListener('input', e => {
        let v = e.target.value.replace(/\D/g, '').slice(0, 15);
        e.target.value = v.replace(/^(\d{3})(\d{4})(\d{4})(\d{4})$/, '$1 $2 $3 $4');
    });

    telInput?.addEventListener('input', e => {
        let v = e.target.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 10) {
            e.target.value = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
        } else {
            e.target.value = v.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
        }
    });

    document.querySelectorAll('.senha-visibility').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.senhaAlvo);
            const icon = button.querySelector('i');
            if (!input || !icon) return;

            const mostrar = input.type === 'password';
            const descricao = input.id === 'senha_conf' ? 'confirmação da senha' : 'senha';
            input.type = mostrar ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(mostrar));
            button.setAttribute('aria-label', `${mostrar ? 'Ocultar' : 'Mostrar'} ${descricao}`);
            icon.classList.toggle('fa-eye', !mostrar);
            icon.classList.toggle('fa-eye-slash', mostrar);
        });
    });

    const atualizarRequisitosSenha = () => {
        if (!senhaInput) return;

        const regras = {
            tamanho: senhaInput.value.length >= 8,
            maiuscula: /[A-Z]/.test(senhaInput.value),
            minuscula: /[a-z]/.test(senhaInput.value),
            numero: /\d/.test(senhaInput.value)
        };

        document.querySelectorAll('#senhaRequisitos [data-regra]').forEach(item => {
            const valido = regras[item.dataset.regra];
            const icon = item.querySelector('i');
            item.classList.toggle('valido', valido);
            if (icon) {
                icon.classList.toggle('fa-regular', !valido);
                icon.classList.toggle('fa-circle', !valido);
                icon.classList.toggle('fa-solid', valido);
                icon.classList.toggle('fa-circle-check', valido);
            }
        });

        atualizarConfirmacaoSenha();
    };

    const atualizarConfirmacaoSenha = () => {
        const mensagem = document.getElementById('senhaConfirmacao');
        if (!senhaConfirmacaoInput || !mensagem) return;

        if (!senhaConfirmacaoInput.value) {
            mensagem.textContent = '';
            mensagem.classList.remove('valido', 'invalido');
            return;
        }

        const confere = senhaInput.value === senhaConfirmacaoInput.value;
        mensagem.textContent = confere ? 'As senhas conferem.' : 'As senhas não conferem.';
        mensagem.classList.toggle('valido', confere);
        mensagem.classList.toggle('invalido', !confere);
    };

    senhaInput?.addEventListener('input', atualizarRequisitosSenha);
    senhaConfirmacaoInput?.addEventListener('input', atualizarConfirmacaoSenha);
</script>
</body>
</html>
