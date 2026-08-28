<?php
// buscachat.php
// Retorna chamados ABERTOS em formato simples para consumo por sistema externo (C#)
// FORMATO FINAL:
// ID-TITULO-STATUS-ID-TITULO-STATUS-

header('Content-Type: text/plain; charset=utf-8');

require 'conexao.php';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Chamados considerados "abertos"
    $sql = "SELECT *
            FROM servicos
            WHERE status IN ('Solicitação recebida','Em andamento')
            ORDER BY id ASC";

    $stmt = $pdo->query($sql);

    if ($stmt->rowCount() === 0) {
        echo "SEM-CHAMADOS";
        exit;
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // Proteção contra hífen no texto (evita quebrar o Split do C#)
         $nome = str_replace('-', ' ', trim($row['responsavel']));
        $titulo = str_replace('-', ' ', trim($row['titulo']));
        $descricao = str_replace('-', ' ', trim($row['descricao']));
        $endereco = str_replace('-', ' ', trim($row['endereco']));
        $telefone = str_replace('-', ' ', trim($row['telefone']));



        // SEM QUEBRA DE LINHA
        echo 'Eu '.$nome.' solicito a obra '
            . $titulo . ' está com o seguinrte problema: '
            . $descricao . ', no endereço '
            . $endereco . ', meu telefone '
            . $telefone.'Obrigado. -';
    }
} catch (Exception $e) {
    echo 'ERRO-' . str_replace('-', ' ', $e->getMessage());
}
