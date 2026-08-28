<?php
session_start();
include 'conexao.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $telefone = preg_replace('/\D/', '', $_POST['telefone']);

    $stmt = $pdo->prepare("
        INSERT INTO servicos
        (titulo, descricao, responsavel, endereco, telefone, status, tipo)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['titulo'],
        $_POST['descricao'],
        $_POST['responsavel'],
        $_POST['endereco'],
        $telefone,
        'Solicitação recebida',
        'Eletrica',
    ]);

    $_SESSION['telefone'] = $telefone;
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Solicitação de Serviço de Elétrica</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        /* RESET BÁSICO (evita bugs de layout) */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Segoe UI, Arial, sans-serif;
            background: linear-gradient(135deg, #f7971e, #ffd200);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
        }

        h1 {
            text-align: center;
            color: #b45309;
        }

        p {
            text-align: center;
            color: #555;
        }

        /* =======================
           FORM – MOBILE FIRST
        ======================= */
        form {
            display: grid;
            grid-template-columns: 1fr;
            /* MOBILE: 1 coluna */
            gap: 18px;
        }

        label {
            font-weight: 600;
            color: #333;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 15px;
        }

        textarea {
            min-height: 180px;
            resize: vertical;
        }

        .full {
            grid-column: auto;
        }

        button {
            padding: 16px;
            background: #f59e0b;
            color: #000;
            font-size: 17px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        button:hover {
            background: #d97706;
        }

        .alerta {
            background: #fff7ed;
            border-left: 6px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
        }

        /* =======================
           DESKTOP
        ======================= */
        @media (min-width: 768px) {
            form {
                grid-template-columns: 1fr 1fr;
                /* 2 colunas */
            }

            .full {
                grid-column: 1 / 3;
            }

            button {
                grid-column: 1 / 3;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>Solicitação de Serviço de Elétrica</h1>
        <p>Preencha com atenção. As informações ajudam a agilizar o atendimento.</p>

        <div class="alerta">
            ⚠️ <b>Atenção:</b> Os eletricistas <b>não fornecem materiais</b>.
            Quando necessário, a compra das peças é de responsabilidade do solicitante.
        </div>

        <form method="post">

            <div>
                <label>Tipo de solicitação</label>
                <select name="titulo" required>
                    <option value="">Selecione</option>
                    <option value="Orçamento elétrico">Orçamento elétrico</option>
                    <option value="Vistoria elétrica">Vistoria elétrica</option>
                    <option value="Instalação de tomada">Instalação de tomada</option>
                    <option value="Troca de lâmpada / luminária">Troca de lâmpada / luminária</option>
                    <option value="Manutenção elétrica">Manutenção elétrica</option>
                    <option value="Outro serviço elétrico">Outro serviço elétrico</option>
                </select>
            </div>

            <div>
                <label>Seu nome</label>
                <input type="text" name="responsavel" required>
            </div>

            <div>
                <label>Endereço do local</label>
                <input type="text" name="endereco" required>
            </div>

            <div>
                <label>Telefone para contato</label>
                <input type="tel" name="telefone" required>
            </div>

            <div class="full">
                <label>Descrição do serviço</label>
                <textarea name="descricao" required>
1️⃣ O que você está solicitando?


2️⃣ Em caso de execução (ex: tomada, lâmpada, disjuntor):
Você já possui as peças/materiais necessários?


3️⃣ Descreva o problema ou serviço desejado com o máximo de detalhes possíveis:

</textarea>
            </div>

            <button>Cadastrar Solicitação</button>

        </form>
    </div>

</body>

</html>