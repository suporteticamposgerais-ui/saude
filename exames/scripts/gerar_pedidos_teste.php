<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este utilitario so pode ser executado pelo terminal.\n");
}

if (!in_array('--confirm', $argv, true)) {
    fwrite(STDERR, "Este script pode criar muitos pedidos e documentos de teste.\n" .
        "Execute novamente com --confirm para continuar.\n");
    exit(2);
}

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../helpers/pedidos.php';

const TOTAL_PACIENTES_TESTE = 50;
const OBSERVACAO_PEDIDO_TESTE = 'Pedido criado automaticamente para teste do sistema.';
const TITULO_DOCUMENTO_TESTE = 'Documento de teste - Brasao';
const NOME_ORIGINAL_DOCUMENTO_TESTE = 'brasao.jpg';

function protocoloTesteUnico(PDO $pdo, array &$protocolosUsados): string
{
    for ($tentativa = 0; $tentativa < 100; $tentativa++) {
        $protocolo = gerarProtocolo($pdo);
        if (isset($protocolosUsados[$protocolo])) {
            continue;
        }

        $stmt = $pdo->prepare('SELECT 1 FROM pedidos_exames WHERE protocolo = ? LIMIT 1');
        $stmt->execute([$protocolo]);
        if (!$stmt->fetchColumn()) {
            $protocolosUsados[$protocolo] = true;
            return $protocolo;
        }
        $protocolosUsados[$protocolo] = true;
    }

    throw new RuntimeException('Nao foi possivel gerar um protocolo unico.');
}

function emailTeste(int $numero): string
{
    return 'usuario.teste' . str_pad((string)$numero, 2, '0', STR_PAD_LEFT) . '@example.com';
}

$banco = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
$arquivoAnexo = __DIR__ . '/../img/brasao.jpg';
if (!is_file($arquivoAnexo)) {
    throw new RuntimeException('Arquivo de teste nao encontrado: img/brasao.jpg.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeAnexo = (string)$finfo->file($arquivoAnexo);
$tamanhoAnexo = filesize($arquivoAnexo);
if ($mimeAnexo !== 'image/jpeg' || $tamanhoAnexo === false || $tamanhoAnexo > 10 * 1024 * 1024) {
    throw new RuntimeException('img/brasao.jpg precisa ser um JPEG valido de ate 10 MB.');
}

$catalogo = $pdo->query(<<<'SQL'
    SELECT e.id, e.nome, e.unidade_id, e.complexidade
    FROM exames e
    INNER JOIN unidades u ON u.id = e.unidade_id AND u.ativo = 1
    WHERE e.ativo = 1
    ORDER BY e.id
SQL)->fetchAll(PDO::FETCH_ASSOC);
if (!$catalogo) {
    throw new RuntimeException('Nenhum procedimento ativo foi encontrado.');
}

$emailsTeste = [];
for ($pacienteNumero = 1; $pacienteNumero <= TOTAL_PACIENTES_TESTE; $pacienteNumero++) {
    $emailsTeste[] = emailTeste($pacienteNumero);
}
$placeholdersEmail = implode(',', array_fill(0, count($emailsTeste), '?'));
$stmtPacientes = $pdo->prepare("SELECT id, nome, email, ativo FROM pacientes WHERE email IN ({$placeholdersEmail})");
$stmtPacientes->execute($emailsTeste);
$pacientesEncontrados = [];
while ($paciente = $stmtPacientes->fetch(PDO::FETCH_ASSOC)) {
    $pacientesEncontrados[(string)$paciente['email']] = $paciente;
}

$pacientesAtivos = array_filter($pacientesEncontrados, static fn(array $paciente): bool => (int)$paciente['ativo'] === 1);
$idsPacientes = array_map(static fn(array $paciente): int => (int)$paciente['id'], array_values($pacientesAtivos));
$pedidosExistentes = [];
if ($idsPacientes) {
    $placeholdersPaciente = implode(',', array_fill(0, count($idsPacientes), '?'));
    $stmtExistentes = $pdo->prepare("SELECT paciente_id, exame_id FROM pedidos_exames WHERE paciente_id IN ({$placeholdersPaciente})");
    $stmtExistentes->execute($idsPacientes);
    while ($pedido = $stmtExistentes->fetch(PDO::FETCH_ASSOC)) {
        $pedidosExistentes[(int)$pedido['paciente_id'] . ':' . (int)$pedido['exame_id']] = true;
    }
}

$ultimaColocacao = [];
$stmtColocacao = $pdo->query('SELECT exame_id, COALESCE(MAX(colocacao), 0) AS ultima FROM pedidos_exames GROUP BY exame_id');
while ($registro = $stmtColocacao->fetch(PDO::FETCH_ASSOC)) {
    $ultimaColocacao[(int)$registro['exame_id']] = (int)$registro['ultima'];
}

$protocolosUsados = [];
foreach ($pdo->query('SELECT protocolo FROM pedidos_exames')->fetchAll(PDO::FETCH_COLUMN) as $protocoloExistente) {
    $protocolosUsados[(string)$protocoloExistente] = true;
}

$stmtInserirPedido = $pdo->prepare(<<<'SQL'
    INSERT INTO pedidos_exames
        (protocolo, colocacao, paciente_id, exame_id, unidade_id, complexidade, status, prioridade, observacoes, criado_em)
    VALUES (?, ?, ?, ?, ?, ?, 'aguardando_analise', 2, ?, NOW())
SQL);
$stmtInserirDocumento = $pdo->prepare(<<<'SQL'
    INSERT INTO documentos_exames
        (pedido_id, paciente_id, titulo_documento, arquivo_fisico, arquivo_original, mime_type, tamanho_bytes, enviado_por)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'paciente')
SQL);
$stmtHistorico = $pdo->prepare(<<<'SQL'
    INSERT INTO historico_pedidos
        (pedido_id, status_anterior, status_novo, acao, observacao, autor_tipo, autor_id, autor_nome)
    VALUES (?, NULL, 'aguardando_analise', 'Solicitacao cadastrada pelo paciente', ?, 'paciente', ?, ?)
SQL);

$pedidosCriados = 0;
$documentosCriados = 0;
$pedidosIgnorados = 0;
$pacientesInativos = 0;
$pacientesAusentes = 0;
$erros = [];

echo "Banco conectado: {$banco}\n";
echo 'Pacientes de teste encontrados: ' . count($pacientesEncontrados) . " de " . TOTAL_PACIENTES_TESTE . "\n";
echo 'Procedimentos ativos (unidades ativas): ' . count($catalogo) . "\n";
echo 'Maximo potencial, se nao houver pedidos existentes: ' . (count($pacientesAtivos) * count($catalogo)) . " pedidos/documentos\n\n";

foreach ($emailsTeste as $email) {
    if (!isset($pacientesEncontrados[$email])) {
        $pacientesAusentes++;
        echo "{$email}: paciente ausente; ignorado.\n";
        continue;
    }

    $paciente = $pacientesEncontrados[$email];
    if ((int)$paciente['ativo'] !== 1) {
        $pacientesInativos++;
        echo "{$email}: paciente inativo; ignorado.\n";
        continue;
    }

    $pacienteId = (int)$paciente['id'];
    $pedidosCriadosPaciente = 0;
    $pedidosIgnoradosPaciente = 0;
    $arquivosCriadosPaciente = [];
    $pastasCriadasPaciente = [];
    $chavesAdicionadas = [];
    $protocolosAdicionados = [];
    $colocacoesPaciente = $ultimaColocacao;

    try {
        $pdo->beginTransaction();

        foreach ($catalogo as $exame) {
            $exameId = (int)$exame['id'];
            $chavePedido = $pacienteId . ':' . $exameId;
            if (isset($pedidosExistentes[$chavePedido])) {
                $pedidosIgnoradosPaciente++;
                continue;
            }

            $protocolo = protocoloTesteUnico($pdo, $protocolosUsados);
            $protocolosAdicionados[] = $protocolo;
            $colocacoesPaciente[$exameId] = ($colocacoesPaciente[$exameId] ?? 0) + 1;

            $stmtInserirPedido->execute([
                $protocolo,
                $colocacoesPaciente[$exameId],
                $pacienteId,
                $exameId,
                (int)$exame['unidade_id'],
                $exame['complexidade'],
                OBSERVACAO_PEDIDO_TESTE
            ]);
            $pedidoId = (int)$pdo->lastInsertId();

            $pastaPedido = __DIR__ . '/../uploads/exames/' . $pedidoId;
            if (!is_dir($pastaPedido)) {
                if (!mkdir($pastaPedido, 0775, true) && !is_dir($pastaPedido)) {
                    throw new RuntimeException("Nao foi possivel criar a pasta do pedido {$pedidoId}.");
                }
                $pastasCriadasPaciente[] = $pastaPedido;
            }

            $nomeFisico = bin2hex(random_bytes(16)) . '.jpg';
            $caminhoDocumento = $pastaPedido . DIRECTORY_SEPARATOR . $nomeFisico;
            if (!copy($arquivoAnexo, $caminhoDocumento)) {
                throw new RuntimeException("Nao foi possivel copiar o anexo para o pedido {$pedidoId}.");
            }
            $arquivosCriadosPaciente[] = $caminhoDocumento;

            $stmtInserirDocumento->execute([
                $pedidoId,
                $pacienteId,
                TITULO_DOCUMENTO_TESTE,
                $nomeFisico,
                NOME_ORIGINAL_DOCUMENTO_TESTE,
                $mimeAnexo,
                (int)$tamanhoAnexo
            ]);
            $stmtHistorico->execute([
                $pedidoId,
                OBSERVACAO_PEDIDO_TESTE,
                $pacienteId,
                (string)$paciente['nome']
            ]);

            $pedidosExistentes[$chavePedido] = true;
            $chavesAdicionadas[] = $chavePedido;
            $pedidosCriadosPaciente++;
        }

        $pdo->commit();
        $ultimaColocacao = $colocacoesPaciente;
        $pedidosCriados += $pedidosCriadosPaciente;
        $documentosCriados += $pedidosCriadosPaciente;
        $pedidosIgnorados += $pedidosIgnoradosPaciente;
        echo "{$email}: {$pedidosCriadosPaciente} novos; {$pedidosIgnoradosPaciente} ja existiam.\n";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        foreach ($arquivosCriadosPaciente as $arquivoCriado) {
            if (is_file($arquivoCriado)) {
                unlink($arquivoCriado);
            }
        }
        foreach (array_reverse($pastasCriadasPaciente) as $pastaCriada) {
            if (is_dir($pastaCriada) && count(scandir($pastaCriada) ?: []) === 2) {
                rmdir($pastaCriada);
            }
        }
        foreach ($chavesAdicionadas as $chaveAdicionada) {
            unset($pedidosExistentes[$chaveAdicionada]);
        }
        foreach ($protocolosAdicionados as $protocoloAdicionado) {
            unset($protocolosUsados[$protocoloAdicionado]);
        }
        $erros[] = "{$email}: {$e->getMessage()}";
        fwrite(STDERR, "ERRO {$email}: {$e->getMessage()}\n");
    }
}

echo "\nResumo: {$pedidosCriados} pedidos criados; {$documentosCriados} documentos; {$pedidosIgnorados} pedidos existentes mantidos; {$pacientesAusentes} pacientes ausentes; {$pacientesInativos} inativos; " . count($erros) . " erros.\n";
if ($erros) {
    exit(1);
}
