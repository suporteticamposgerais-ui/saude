<?php
// cadastro_exame.php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: acompanhamento.php');
    exit;
}

try {
    $pdo->beginTransaction();

    /* ===============================
       1) INSERE O PEDIDO DE EXAME
       =============================== */
    $stmt = $pdo->prepare("
    INSERT INTO pedidos_exames
    (nome_paciente, cartao_sus, telefone, exame_solicitado, observacoes, status, tipo, criado_em)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

    $stmt->execute([
        $_POST['nome_paciente'],
        $_POST['cartao_sus'],
        $_POST['telefone'] ?? null,
        $_POST['exame_solicitado'],
        $_POST['observacoes'] ?? null,
        'Solicitação recebida',        // status inicial
        $_POST['tipo'] ?? 'baixa'      // baixa = Sec. Saúde | alta = Policlínica
    ]);




    $pedidoId = $pdo->lastInsertId();

    if (!$pedidoId) {
        throw new Exception('Erro ao criar pedido');
    }

    /* ===============================
       2) UPLOAD DOS DOCUMENTOS
       =============================== */

    // Pasta por pedido (organização profissional)
    $pastaPedido = "uploads/exames/" . $pedidoId;

    if (!is_dir($pastaPedido)) {
        mkdir($pastaPedido, 0775, true);
    }

    foreach ($_FILES['documentos']['tmp_name'] as $i => $tmp) {

        if ($_FILES['documentos']['error'][$i] === UPLOAD_ERR_OK) {

            $titulo = $_POST['titulo_documento'][$i] ?? 'Documento';
            $nomeOriginal = $_FILES['documentos']['name'][$i];
            $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

            // Segurança básica
            $permitidos = ['pdf', 'jpg', 'jpeg', 'png'];
            if (!in_array($ext, $permitidos)) {
                throw new Exception('Tipo de arquivo não permitido');
            }

            $novoNome = uniqid('doc_') . '.' . $ext;
            $destino = $pastaPedido . '/' . $novoNome;

            if (!move_uploaded_file($tmp, $destino)) {
                throw new Exception('Erro ao salvar arquivo');
            }

            $stmtDoc = $pdo->prepare("
                INSERT INTO documentos_exames
                (pedido_id, titulo_documento, arquivo)
                VALUES (?, ?, ?)
            ");

            $stmtDoc->execute([
                $pedidoId,
                $titulo,
                $novoNome
            ]);
        }
    }

    $pdo->commit();

    header('Location: acompanhamento.php');
    exit;


    // echo "<div style='padding:20px;font-family:Arial'>
    //         <h3 style='color:green'>✅ Pedido cadastrado com sucesso!</h3>
    //         <p>Número do pedido: <b>#{$pedidoId}</b></p>
    //       </div>";

} catch (Exception $e) {
    $pdo->rollBack();

    echo "<div style='padding:20px;font-family:Arial;color:red'>
            <h3>❌ Erro ao cadastrar pedido</h3>
            <p>{$e->getMessage()}</p>
          </div>";
}
