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

function gerarProtocoloPedido(PDO $pdo): string {
    $ano = (int) date('Y');
    $prefixo = 'SAU';

    $stmt = $pdo->prepare("SELECT ultimo_numero FROM protocolo_controle WHERE ano = ? FOR UPDATE");
    $stmt->execute([$ano]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        $numero = (int) $registro['ultimo_numero'] + 1;
        $update = $pdo->prepare("UPDATE protocolo_controle SET ultimo_numero = ? WHERE ano = ?");
        $update->execute([$numero, $ano]);
    } else {
        $numero = 1;
        $insert = $pdo->prepare("INSERT INTO protocolo_controle (ano, ultimo_numero) VALUES (?, ?)");
        $insert->execute([$ano, $numero]);
    }

    return sprintf('%s-%d-%s', $prefixo, $ano, str_pad((string) $numero, 6, '0', STR_PAD_LEFT));
}

// Recebe dados
$nome   = trim($_POST['nome_paciente'] ?? '');
$cns    = trim($_POST['cartao_sus'] ?? '');
$rua    = trim($_POST['rua'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$email  = trim($_POST['email'] ?? '');
$exame_solicitado = trim($_POST['exame_solicitado'] ?? '');
$exame_solicitado = preg_replace('/\s+/', ' ', $exame_solicitado);

$tipoPedido = '';
$exame = $exame_solicitado;
$especialidade = '';

if (preg_match('/^(Consulta|Exame)\s*[-–]\s*(.+)$/i', $exame_solicitado, $m)) {
    $tipoPedido = ucfirst(strtolower($m[1]));
    $especialidade = trim($m[2]);
    $exame = $tipoPedido;
} else {
    if (stripos($exame_solicitado, 'Consulta') !== false) {
        $tipoPedido = 'Consulta';
    } elseif (stripos($exame_solicitado, 'Exame') !== false) {
        $tipoPedido = 'Exame';
    }
}

$obs    = $_POST['observacoes'] ?? null;
$tel = preg_replace('/\D/', '', $_POST['telefone'] ?? '');

$complexidade = $_POST['complexidade'] ?? '';

// Validações
$erros = [];
if (!$nome) $erros[] = 'Nome do paciente é obrigatório.';
if (!validarCNS($cns)) $erros[] = 'Cartão SUS inválido.';
if (!$rua || !$numero || !$bairro) $erros[] = 'Rua, Número e Bairro são obrigatórios.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
if (!$exame) $erros[] = 'Exame solicitado é obrigatório.';
if (!telefoneValido($tel)) $erros[] = 'Telefone inválido.';
if (!in_array($complexidade, ['baixa', 'media', 'alta'])) $erros[] = 'Complexidade do pedido inválida.';

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

    $protocolo = gerarProtocoloPedido($pdo);

    $stmt = $pdo->prepare("
    INSERT INTO pedidos_exames
    (nome_paciente, cartao_sus, telefone, email, rua, numero, bairro, exame_solicitado, especialidade, observacoes, status, complexidade, protocolo, criado_em, robo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");

    $stmt->execute([
        $nome,
        $cns,
        $tel,
        $email,
        $rua,
        $numero,
        $bairro,
        $exame_solicitado,
        $especialidade,
        $obs,
        'Solicitação recebida',
        $complexidade,
        $protocolo,
        '0'
    ]);

    $pedidoId = $pdo->lastInsertId();
    $pasta = "uploads/exames/$pedidoId";
    if (!is_dir($pasta)) mkdir($pasta, 0775, true);

    foreach ($_FILES['documentos']['tmp_name'] as $i => $tmp) {
        if ($_FILES['documentos']['error'][$i] === 0) {
            $ext = strtolower(pathinfo($_FILES['documentos']['name'][$i], PATHINFO_EXTENSION));
            $permitidos = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            $mimePermitidos = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            $mimeTipo = mime_content_type($tmp) ?: '';
            if (!in_array($ext, $permitidos) && !in_array($mimeTipo, $mimePermitidos)) {
                throw new Exception('Arquivo não permitido: ' . $_FILES['documentos']['name'][$i]);
            }

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
