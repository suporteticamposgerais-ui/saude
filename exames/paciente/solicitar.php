<?php
// paciente/solicitar.php
declare(strict_types=1);

require_once '../conexao.php';
require_once '../helpers/auth.php';
require_once '../helpers/seguranca.php';
require_once '../helpers/pedidos.php';

$pacienteLogado = exigirLoginPaciente();
$pacienteId = (int)$pacienteLogado['id'];

$unidades = $pdo->query("SELECT id, nome, chave FROM unidades WHERE ativo = 1 ORDER BY id")->fetchAll();
$unidadesPorChave = [];
foreach ($unidades as $unidade) {
    $unidadesPorChave[$unidade['chave']] = $unidade;
}
$unidadeInicialId = (string)($unidadesPorChave['secretaria']['id'] ?? ($unidades[0]['id'] ?? ''));

$exames = $pdo->query("
    SELECT e.id, e.especialidade_id, e.nome, e.tipo, e.complexidade, e.unidade_id
    FROM exames e
    INNER JOIN unidades u ON u.id = e.unidade_id AND u.ativo = 1
    WHERE e.ativo = 1
    ORDER BY e.nome
")->fetchAll();

$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {

        $erro = 'Sessão expirada. Tente novamente.';
    } else {

        $exameId = (int)($_POST['exame_id'] ?? 0);
        $observacoes = trim($_POST['observacoes'] ?? '');


        // Validar se o exame existe e está ativo
        $stmtChk = $pdo->prepare("
            SELECT e.*, u.nome AS unidade_nome
            FROM exames e
                        INNER JOIN unidades u ON u.id = e.unidade_id AND u.ativo = 1
            WHERE e.id = ?
              AND e.ativo = 1
        ");

        $stmtChk->execute([$exameId]);
        $exameValido = $stmtChk->fetch();

        if (!$exameValido) {

            $erro = 'Selecione um exame ou consulta válido.';
        } elseif (empty($_FILES['documentos']['tmp_name'][0])) {

            $erro = 'É obrigatório anexar o pedido médico ou encaminhamento.';
        } elseif (count($_FILES['documentos']['name'] ?? []) > 10) {

            $erro = 'Você pode enviar no máximo 10 documentos por solicitação.';
        } else {
            $arquivosSalvos = [];

            try {

                /*
                 * Inicia uma transação.
                 *
                 * A colocação será calculada dentro da mesma transação
                 * antes de inserir o pedido.
                 */
                $pdo->beginTransaction();

                // Gera protocolo
                $protocolo = gerarProtocolo($pdo);

                /*
                 * Gera a colocação específica deste exame.
                 *
                 * Cada exame possui sua própria fila.
                 *
                 * Exemplo:
                 *
                 * exame_id 38 -> 1, 2, 3, 4...
                 * exame_id 1  -> 1, 2, 3...
                 */
                $colocacao = gerarColocacao($pdo, $exameId);

                /*
                 * Insere o pedido.
                 */
                $stmtIns = $pdo->prepare("
                    INSERT INTO pedidos_exames
                    (
                        protocolo,
                        colocacao,
                        paciente_id,
                        exame_id,
                        unidade_id,
                        complexidade,
                        status,
                        prioridade,
                        observacoes,
                        criado_em
                    )
                    VALUES (?, ?, ?, ?, ?, ?, 'aguardando_analise', 2, ?, NOW())
                ");

                $stmtIns->execute([
                    $protocolo,
                    $colocacao,
                    $pacienteId,
                    $exameId,
                    $exameValido['unidade_id'],
                    $exameValido['complexidade'],
                    $observacoes
                ]);

                $pedidoId = (int)$pdo->lastInsertId();
                $pastaUpload = __DIR__ . "/../uploads/exames/{$pedidoId}";

                /*
                 * Cria a pasta dos documentos.
                 */
                if (!is_dir($pastaUpload) && !mkdir($pastaUpload, 0775, true) && !is_dir($pastaUpload)) {
                    throw new Exception('Nao foi possivel criar a pasta dos documentos.');
                }

                /*
                 * Configuração dos arquivos permitidos.
                 */
                $finfo = new finfo(FILEINFO_MIME_TYPE);

                $permitidos = [
                    'pdf'  => 'application/pdf',
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png'
                ];

                /*
                 * Processa os documentos enviados.
                 */
                foreach ($_FILES['documentos']['tmp_name'] as $i => $tmp) {

                    if ($_FILES['documentos']['error'][$i] !== UPLOAD_ERR_OK) {
                        throw new Exception('Um dos documentos nao foi enviado corretamente.');
                    }

                    if ((int)$_FILES['documentos']['size'][$i] > 10 * 1024 * 1024) {
                        throw new Exception('Cada documento deve ter no máximo 10 MB.');
                    }

                    $titulo = trim((string)($_POST['titulo_documento'][$i] ?? ''));
                    if ($titulo === '' || mb_strlen($titulo) > 150) {
                        throw new Exception('Informe um título válido para cada documento.');
                    }

                    $origName = $_FILES['documentos']['name'][$i];
                    $mime = $finfo->file($tmp);
                    $ext = strtolower(
                        pathinfo($origName, PATHINFO_EXTENSION)
                    );

                    /*
                     * Valida extensão e MIME.
                     */
                    if (
                        !isset($permitidos[$ext]) ||
                        $permitidos[$ext] !== $mime
                    ) {
                        throw new Exception(
                            "Arquivo inválido ({$origName}). " .
                                "Envie apenas PDF, JPG ou PNG."
                        );
                    }

                    /*
                     * Nome físico aleatório.
                     */
                    $novoNome = bin2hex(random_bytes(16)) . '.' . $ext;

                    if (!move_uploaded_file(
                        $tmp,
                        "{$pastaUpload}/{$novoNome}"
                    )) {
                        throw new Exception(
                            "Não foi possível salvar o arquivo {$origName}."
                        );
                    }
                    $arquivosSalvos[] = "{$pastaUpload}/{$novoNome}";

                    /*
                     * Registra o documento no banco.
                     */
                    $stmtDoc = $pdo->prepare("
                            INSERT INTO documentos_exames
                            (
                                pedido_id,
                                paciente_id,
                                titulo_documento,
                                arquivo_fisico,
                                arquivo_original,
                                mime_type,
                                tamanho_bytes,
                                enviado_por
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'paciente')
                        ");

                    $stmtDoc->execute([
                        $pedidoId,
                        $pacienteId,
                        $titulo,
                        $novoNome,
                        $origName,
                        $mime,
                        $_FILES['documentos']['size'][$i]
                    ]);
                }

                /*
                 * Registra o histórico.
                 */
                registrarHistorico(
                    $pdo,
                    $pedidoId,
                    null,
                    'aguardando_analise',
                    'Solicitação cadastrada pelo paciente',
                    $observacoes,
                    'paciente',
                    $pacienteId,
                    $pacienteLogado['nome']
                );

                /*
                 * Confirma tudo.
                 */
                $pdo->commit();

                $sucesso = true;
            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                foreach ($arquivosSalvos as $arquivoSalvo) {
                    if (is_file($arquivoSalvo)) {
                        unlink($arquivoSalvo);
                    }
                }
                if (is_dir($pastaUpload) && count(scandir($pastaUpload) ?: []) === 2) {
                    rmdir($pastaUpload);
                }

                $erro = 'Falha ao salvar: ' . $e->getMessage();
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

    <title>Solicitar Exame / Consulta</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link href="../css/exames.css" rel="stylesheet">

    <style>
        .container-solicitar {
            max-width: 860px;
            margin: 30px auto;
        }

        .btn-filtro {
            border: 1px solid #cfcfcf;
            padding: 12px 18px;
            border-radius: 12px;
            background: #f8f9fa;
            cursor: pointer;
            font-size: 16px;
            width: 48%;
            font-weight: 500;
            transition: .2s;
        }

        .btn-filtro.ativo {
            background: #2a5298;
            color: #fff;
            border-color: #2a5298;
            font-weight: bold;
        }
        .btn-filtro:focus-visible {
            outline: 3px solid rgba(42, 82, 152, .3);
            outline-offset: 2px;
        }
    </style>
</head>

<body>

    <div class="container container-solicitar">

        <div class="card-box">

            <div class="card-header-custom">
                Solicitação de Exame ou Consulta
            </div>

            <div class="p-4 bg-light">

                <?php if ($erro): ?>

                    <div class="alert alert-danger py-2">
                        <?= htmlspecialchars($erro) ?>
                    </div>

                <?php endif; ?>


                <?php if ($sucesso): ?>

                    <div class="alert alert-success text-center py-4">

                        <h4>
                            Solicitação enviada com sucesso!
                        </h4>

                        <p>
                            O pedido foi encaminhado para a equipe de triagem e regulação.
                        </p>

                        <a
                            href="painel.php"
                            class="btn btn-primary fw-bold">
                            Voltar para Meu Painel
                        </a>

                    </div>

                <?php else: ?>

                    <form
                        method="POST"
                        enctype="multipart/form-data">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= gerarCsrfToken() ?>">

                        <input
                            type="hidden"
                            name="unidade_id"
                            id="unidade_id"
                            value="<?= htmlspecialchars($unidadeInicialId) ?>">


                        <label class="form-label fw-bold mb-2">
                            Unidade de Atendimento
                        </label>


                        <div class="d-flex justify-content-between mb-3">
                            <?php foreach ($unidades as $unidadeOpcao): ?>
                                <button
                                    type="button"
                                    class="btn-filtro<?= (string)$unidadeOpcao['id'] === $unidadeInicialId ? ' ativo' : '' ?>"
                                    data-unidade-id="<?= htmlspecialchars((string)$unidadeOpcao['id']) ?>"
                                    aria-pressed="<?= (string)$unidadeOpcao['id'] === $unidadeInicialId ? 'true' : 'false' ?>">
                                    <?= htmlspecialchars($unidadeOpcao['nome']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Procedimento Solicitado
                            </label>

                            <select
                                name="exame_id"
                                id="exame_id"
                                class="form-select"
                                required>

                                <option value="">
                                    Selecione o exame ou consulta...
                                </option>

                                <?php foreach ($exames as $ex): ?>

                                    <option
                                        value="<?= $ex['id'] ?>"
                                        data-unidade="<?= $ex['unidade_id'] ?>">
                                        <?= htmlspecialchars($ex['nome']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Observações / Motivo da Solicitação
                            </label>

                            <textarea
                                name="observacoes"
                                class="form-control"
                                rows="3"
                                placeholder="Descreva sintomas, justificativa médica ou observações"></textarea>

                        </div>


                        <h5 class="section-title">
                            Anexos Obrigatórios (Pedido Médico / Exames Prévios)
                        </h5>


                        <div id="docs-container">
                            <div class="doc-box mb-3 doc-row">
                                <label class="form-label">Identificação do Arquivo</label>
                                <input type="text" name="titulo_documento[]" class="form-control mb-2" maxlength="150" value="Pedido Médico" required>
                                <input type="file" name="documentos[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                        </div>
                        <div class="small text-muted mb-2">Você pode enviar até 10 documentos, com no máximo 10 MB cada.</div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAdicionarDocumento">+ Adicionar outro arquivo</button>


                        <div class="d-flex justify-content-between mt-4">

                            <a
                                href="painel.php"
                                class="btn btn-outline-secondary">
                                Voltar ao Painel
                            </a>

                            <button
                                type="submit"
                                class="btn btn-primary px-4 fw-bold">
                                Confirmar Envio
                            </button>

                        </div>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <script>
        const docsContainer = document.getElementById('docs-container');
        const btnAdicionarDocumento = document.getElementById('btnAdicionarDocumento');
        btnAdicionarDocumento?.addEventListener('click', function () {
            const quantidade = docsContainer.querySelectorAll('.doc-row').length;
            if (quantidade >= 10) { alert('O limite é de 10 documentos por solicitação.'); return; }
            const row = document.createElement('div');
            row.className = 'doc-box mb-3 doc-row';
            row.innerHTML = '<label class="form-label">Identificação do Arquivo</label><input type="text" name="titulo_documento[]" class="form-control mb-2" maxlength="150" placeholder="Ex.: Laudo anterior" required><div class="d-flex gap-2"><input type="file" name="documentos[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required><button type="button" class="btn btn-outline-danger" onclick="this.closest(\'.doc-row\').remove()">Remover</button></div>';
            docsContainer.appendChild(row);
        });

        const unidadeInput = document.getElementById('unidade_id');
        const exameSelect = document.getElementById('exame_id');
        const botoesUnidade = document.querySelectorAll('[data-unidade-id]');
        const placeholderExame = exameSelect?.querySelector('option[value=""]');
        const opcoesExame = exameSelect
            ? Array.from(exameSelect.options).filter(opcao => opcao.value !== '')
            : [];

        function filtrarUnidade(unidadeId) {
            if (unidadeInput) {
                unidadeInput.value = unidadeId;
            }

            botoesUnidade.forEach(botao => {
                const ativo = botao.dataset.unidadeId === unidadeId;
                botao.classList.toggle('ativo', ativo);
                botao.setAttribute('aria-pressed', String(ativo));
            });

            if (!exameSelect || !placeholderExame) {
                return;
            }

            const opcoesFiltradas = opcoesExame.filter(
                opcao => opcao.dataset.unidade === unidadeId
            );
            exameSelect.replaceChildren(
                placeholderExame.cloneNode(true),
                ...opcoesFiltradas
            );
        }

        botoesUnidade.forEach(botao => {
            botao.addEventListener('click', () => filtrarUnidade(botao.dataset.unidadeId));
        });

        if (unidadeInput?.value) {
            filtrarUnidade(unidadeInput.value);
        }
    </script>

</body>

</html>