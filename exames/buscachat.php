<?php

/***************************************
 * 1) buscachat.php (VERSÃO FINAL)
 * - Atualiza enviado_robo = 1
 * - Atualiza data_envio_robo = NOW()
 ***************************************/

header('Content-Type: text/plain; charset=utf-8');
require 'conexao.php';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = "SELECT *
        FROM pedidos_exames
        WHERE status IN ('Solicitação recebida','Visualizado', 'Em andamento')
        AND robo = 0

        ORDER BY id ASC";



    $stmt = $pdo->query($sql);

    if ($stmt->rowCount() === 0) {
        echo "SEM-CHAMADOS";
        exit;
    }

    $ids = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $ids[] = $row['id']; // ✅ adiciona ao array
        $status      = trim($row['status']);
        $telefone    = preg_replace('/\D/', '', $row['telefone']);
        $complexidade = trim($row['complexidade'] ?? 'baixa');

        $nome  = trim($row['nome_paciente']);
        $exame = trim($row['exame_solicitado']);
        $obs   = trim($row['observacoes']);

        // Endereço completo
        $endereco = trim(
            $row['rua'] . ', ' .
                $row['numero'] . ' , ' .
                $row['bairro']
        );

        // Mensagem
        $msg = "Olá, meu nome é {$nome}. ";
        $msg .= "Solicitei o exame: {$exame}. ";

        if (!empty($obs)) {
            $msg .= "Observações: {$obs}. ";
        }

        $msg .= "Endereço: {$endereco}.";

        if (!empty($telefone)) {
            $msg .= " Telefone para contato: {$telefone}.";
        }

        $msg .= " Obrigado.";

        /*
          FORMATO FINAL
          status-complexidade-telefone-mensagem-
        */
        echo "{$status}-{$complexidade}-{$telefone}-{$msg}-";
    }


    // Atualiza todos os chamados enviados ao robô
    if (!empty($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $up = $pdo->prepare(
            "UPDATE pedidos_exames
             SET robo = 1,
                 data_envio_robo = NOW()
             WHERE id IN ($in)"
        );
        $up->execute($ids);
    }
} catch (Exception $e) {
    echo 'ERRO-' . str_replace('-', ' ', $e->getMessage());
}
