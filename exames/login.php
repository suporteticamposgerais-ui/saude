<?php
/* =========================
   ARQUIVO: login.php
   ========================= */
session_start();
require 'conexao.php';

function validarCNS($cns)
{
    $cns = preg_replace('/\D/', '', $cns);
    if (strlen($cns) !== 15) {
        return false;
    }

    $soma = 0;
    $peso = 15;
    for ($i = 0; $i < 15; $i++) {
        $soma += intval($cns[$i]) * $peso--;
    }

    return $soma % 11 === 0;
}

function telefoneValido($telefone)
{
    $numero = preg_replace('/\D/', '', $telefone);
    if (strlen($numero) !== 10 && strlen($numero) !== 11) {
        return false;
    }

    return !preg_match('/^(\d)\1+$/', $numero);
}



$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodoLogin = $_POST['tipo_login'] ?? 'telefone';
    $valorLogin = trim($_POST['identificador'] ?? '');

    $loginValido = false;

    if ($metodoLogin === 'telefone') {
        $valorLogin = preg_replace('/\D/', '', $valorLogin);
        $valorLogin = substr($valorLogin, 0, 11);
        $campo = 'telefone';
        $loginValido = telefoneValido($valorLogin);
    } elseif ($metodoLogin === 'email') {
        $valorLogin = strtolower(trim($valorLogin));
        $campo = 'email';
        $loginValido = filter_var($valorLogin, FILTER_VALIDATE_EMAIL) !== false;
    } elseif ($metodoLogin === 'cartao_sus') {
        $valorLogin = preg_replace('/\D/', '', $valorLogin);
        $valorLogin = substr($valorLogin, 0, 15);
        $campo = 'cartao_sus';
        $loginValido = validarCNS($valorLogin);
    } else {
        $valorLogin = '';
        $campo = 'telefone';
    }

    if ($loginValido) {
        $comparacao = $campo === 'email' ? 'LOWER(email)' : $campo;
        $stmt = $pdo->prepare("SELECT id, telefone FROM pedidos_exames WHERE {$comparacao} = ? LIMIT 1");
        $stmt->execute([$valorLogin]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            $_SESSION['login_tipo'] = $metodoLogin;
            $_SESSION['login_valor'] = $valorLogin;
            $_SESSION['telefone'] = $pedido['telefone'] ?? '';
            header('Location: acompanhamento.php');
            exit;
        }
    }

    $msg = 'Nenhuma solicitação encontrada para os dados informados.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhar Solicitação</title>
    <style>
        * {
            box-sizing: border-box;
        }

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
            max-width: 420px;
            background: #fff;
            padding: 30px 28px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
        }

        h2 {
            text-align: center;
            color: #2a5298;
            margin: 0 0 22px;
            font-size: 30px;
        }

        .campo {
            display: block;
            width: 100%;
            margin-bottom: 8px;
            color: #2a5298;
            font-weight: 600;
        }

        .select-login,
        .input-login {
            width: 100%;
            padding: 14px 12px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #fff;
            display: block;
        }

        .select-login {
            margin-bottom: 12px;
            color: #1f2937;
        }

        button {
            width: 100%;
            padding: 14px;
            margin-top: 18px;
            border: none;
            border-radius: 10px;
            background: #2a5298;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .msg {
            margin-top: 15px;
            color: #b91c1c;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="box">
        <h2>Acompanhar Solicitação</h2>

        <form method="post">
            <label class="campo" for="tipo_login">Método de login</label>
            <select class="select-login" name="tipo_login" id="tipo_login">
                <option value="telefone">Telefone</option>
                <option value="email">E-mail</option>
                <option value="cartao_sus">Cartão SUS</option>
            </select>

            <label class="campo" for="identificador">Digite seu dado</label>
            <input
                class="input-login"
                type="text"
                name="identificador"
                id="identificador"
                placeholder="(00) 00000-0000"
                maxlength="50"
                inputmode="numeric"
                pattern="[0-9()\-\s]*"
                required>

            <button type="submit">Entrar</button>
        </form>

        <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    </div>
    <script>
        const tipoLogin = document.getElementById('tipo_login');
        const identificador = document.getElementById('identificador');
        const formulario = document.querySelector('form');

        function formatarTelefone(valor) {
            valor = valor.replace(/\D/g, '').slice(0, 11);
            if (valor.length > 10) {
                return valor.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            }
            return valor.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
        }

        function validarCNS(cns) {
            cns = cns.replace(/\D/g, '');
            if (cns.length !== 15) return false;

            let soma = 0;
            let peso = 15;
            for (let i = 0; i < 15; i++) soma += parseInt(cns[i]) * peso--;
            return soma % 11 === 0;
        }

        function telefoneValido(telefone) {
            const numero = telefone.replace(/\D/g, '');
            if (numero.length !== 10 && numero.length !== 11) return false;
            return !/^(\d)\1+$/.test(numero);
        }

        function atualizarCampo() {
            const tipo = tipoLogin.value;
            identificador.value = '';
            identificador.classList.remove('is-valid', 'is-invalid');

            if (tipo === 'email') {
                identificador.placeholder = 'seuemail@exemplo.com';
                identificador.type = 'email';
                identificador.inputMode = 'email';
                identificador.removeAttribute('pattern');
                identificador.maxLength = 120;
            } else if (tipo === 'cartao_sus') {
                identificador.placeholder = '000 0000 0000 0000';
                identificador.type = 'text';
                identificador.inputMode = 'numeric';
                identificador.pattern = '[0-9\\s]*';
                identificador.maxLength = 18;
            } else {
                identificador.placeholder = '(00) 00000-0000';
                identificador.type = 'tel';
                identificador.inputMode = 'numeric';
                identificador.pattern = '[0-9()\\-\\s]*';
                identificador.maxLength = 15;
            }
        }

        identificador.addEventListener('input', function() {
            if (tipoLogin.value === 'telefone') {
                identificador.value = formatarTelefone(identificador.value);
            } else if (tipoLogin.value === 'cartao_sus') {
                const numero = identificador.value.replace(/\D/g, '').slice(0, 15);
                identificador.value = numero.replace(/^(\d{3})(\d{4})(\d{4})(\d{4})$/, '$1 $2 $3 $4');
            }
        });

        formulario.addEventListener('submit', function(event) {
            const tipo = tipoLogin.value;
            const valor = identificador.value;
            const valido = tipo === 'telefone'
                ? telefoneValido(valor)
                : tipo === 'email'
                    ? identificador.checkValidity()
                    : validarCNS(valor);

            identificador.classList.toggle('is-valid', valido);
            identificador.classList.toggle('is-invalid', !valido);

            if (!valido) {
                event.preventDefault();
                identificador.reportValidity();
            }
        });

        tipoLogin.addEventListener('change', atualizarCampo);
        atualizarCampo();
    </script>
</body>

</html>
