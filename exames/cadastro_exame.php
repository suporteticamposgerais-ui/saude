<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

function validarCNS($cns) {
    $cns = preg_replace('/\D/', '', $cns);
    if (strlen($cns) != 15) return false;
    $soma = 0; $peso = 15;
    for ($i = 0; $i < 15; $i++) $soma += intval($cns[$i]) * $peso--;
    return $soma % 11 === 0;
}

function telefoneValido($tel) {
    $num = preg_replace('/\D/', '', $tel);
    if (!(strlen($num) === 10 || strlen($num) === 11)) return false;
    if (preg_match('/^(\d)\1+$/', $num)) return false;
    return true;
}

// Recebe dados
$nome   = trim($_POST['nome_paciente'] ?? '');
$cns    = trim($_POST['cartao_sus'] ?? '');
$rua    = trim($_POST['rua'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$email  = trim($_POST['email'] ?? '');
$exame  = trim($_POST['exame_solicitado'] ?? '');
$obs    = $_POST['observacoes'] ?? null;
// $tel    = trim($_POST['telefone'] ?? '');
$tel = preg_replace('/\D/', '', $_POST['telefone']);

$tipo   = $_POST['tipo'] ?? '';

// Validações
$erros = [];
if (!$nome) $erros[] = 'Nome do paciente é obrigatório.';
if (!validarCNS($cns)) $erros[] = 'Cartão SUS inválido.';
if (!$rua || !$numero || !$bairro) $erros[] = 'Rua, Número e Bairro são obrigatórios.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
if (!$exame) $erros[] = 'Exame solicitado é obrigatório.';
if (!telefoneValido($tel)) $erros[] = 'Telefone inválido.';
if (!in_array($tipo, ['baixa', 'alta'])) $erros[] = 'Tipo de pedido inválido.';

if (!empty($erros)) {
    $msg = json_encode(implode("\n", $erros));
    echo "<!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <title>Erro</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Erro ao cadastrar',
            text: $msg
        }).then(() => {
            window.location.href = 'index.php';
        });
    </script>
    </body>
    </html>";
    exit;
}

// Inserção no banco
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO pedidos_exames
        (nome_paciente, cartao_sus, telefone, email, rua, numero, bairro, exame_solicitado, observacoes, status, tipo, criado_em, robo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(),?)
    ");

    $stmt->execute([
        $nome, $cns, $tel, $email, $rua, $numero, $bairro, $exame, $obs, 'Solicitação recebida', $tipo, '0'
    ]);

    $pedidoId = $pdo->lastInsertId();
    $pasta = "uploads/exames/$pedidoId";
    if (!is_dir($pasta)) mkdir($pasta, 0775, true);

    foreach ($_FILES['documentos']['tmp_name'] as $i => $tmp) {
        if ($_FILES['documentos']['error'][$i] === 0) {
            $ext = strtolower(pathinfo($_FILES['documentos']['name'][$i], PATHINFO_EXTENSION));
            $permitidos = ['pdf', 'jpg', 'jpeg', 'png'];
            if (!in_array($ext, $permitidos)) throw new Exception('Arquivo não permitido: ' . $_FILES['documentos']['name'][$i]);

            $nomeArquivo = uniqid() . ".$ext";
            move_uploaded_file($tmp, "$pasta/$nomeArquivo");

            $stmtDoc = $pdo->prepare("INSERT INTO documentos_exames (pedido_id, titulo_documento, arquivo) VALUES (?, ?, ?)");
            $stmtDoc->execute([$pedidoId, $_POST['titulo_documento'][$i], $nomeArquivo]);
        }
    }

    $pdo->commit();

    // Sucesso
    echo "<!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <title>Sucesso</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Pedido cadastrado!',
            text: 'Seu pedido foi registrado com sucesso.'
        }).then(() => {
            window.location.href = 'acompanhamento.php';
        });
    </script>
    </body>
    </html>";

} catch (Exception $e) {
    $pdo->rollBack();
    $msg = addslashes($e->getMessage());
    echo "<!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <title>Erro</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: '$msg'
        }).then(() => {
            window.location.href = 'index.php';
        });
    </script>
    </body>
    </html>";
}
