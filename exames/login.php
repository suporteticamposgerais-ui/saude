<?php
/* =========================
   ARQUIVO: login.php
   ========================= */
session_start();
require 'conexao.php';



$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefone = preg_replace('/\D/', '', $_POST['telefone']);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos_exames WHERE telefone = ?");
    $stmt->execute([$telefone]);

    if ($stmt->fetchColumn() > 0) {
        $_SESSION['telefone'] = $telefone;
        header('Location: acompanhamento.php');
        exit;
    } else {
        $msg = 'Nenhuma solicitação encontrada para este número.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhar Solicitação</title>
    <style>
        body {
            font-family: Arial;
            background: #f0f4f8;
            margin: 0
        }

        .box {
            max-width: 420px;
            margin: 60px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .15)
        }

        h2 {
            text-align: center;
            color: #2a5298
        }

        input {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ccc
        }

        button {
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            border: none;
            border-radius: 10px;
            background: #2a5298;
            color: #fff;
            font-size: 16px
        }

        .msg {
            margin-top: 15px;
            color: #b91c1c;
            text-align: center
        }
    </style>
</head>

<body>
    <div class="box">
        <h2>Acompanhar Solicitação</h2>
        <form method="post">
            <label>Digite seu telefone</label>
            <input type="tel" name="telefone" placeholder="(xx) xxxxx-xxxx" required>
            <button>Entrar</button>
        </form>
        <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
    </div>
</body>

</html>
