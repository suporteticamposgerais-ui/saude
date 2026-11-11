<?php
declare(strict_types=1);
require_once '../conexao.php';
require_once '../helpers/auth.php';
require_once '../helpers/seguranca.php';
require_once '../helpers/pedidos.php';

$admin = exigirLoginAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listagem_ordens.php');
    exit;
}

$pedidoId = (int)($_POST['pedido_id'] ?? 0);
$stmtPedido = $pdo->prepare('SELECT id, paciente_id, status FROM pedidos_exames WHERE id = ?');
$stmtPedido->execute([$pedidoId]);
$pedido = $stmtPedido->fetch();
if (!$pedido) {
    http_response_code(404);
    exit('Pedido não encontrado.');
}

$redirect = 'paciente_perfil.php?id=' . (int)$pedido['paciente_id'];
$filtrosPermitidos = ['busca', 'status', 'especialidade_id', 'exame_id', 'unidade_id'];
$filtrosRetorno = [];
$filtrosEnviados = $_POST['filtros_retorno'] ?? [];
if (is_array($filtrosEnviados)) {
    foreach ($filtrosPermitidos as $filtro) {
        $valor = $filtrosEnviados[$filtro] ?? null;
        if (is_scalar($valor) && trim((string)$valor) !== '') {
            $filtrosRetorno[$filtro] = trim((string)$valor);
        }
    }
}
if ($filtrosRetorno) {
    $redirect .= '&' . http_build_query($filtrosRetorno);
}

if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_swal'] = ['icon' => 'error', 'title' => 'Sessão expirada', 'text' => 'Atualize a página e tente novamente.'];
    header('Location: ' . $redirect);
    exit;
}

$arquivo = $_FILES['documento'] ?? null;
$arquivoSalvo = null;
$pastaUpload = __DIR__ . '/../uploads/exames/' . $pedidoId;

try {
    $titulo = trim((string)($_POST['titulo_documento'] ?? ''));
    if ($titulo === '' || mb_strlen($titulo) > 150) {
        throw new RuntimeException('Informe um nome válido para o documento.');
    }
    if (!$arquivo || !isset($arquivo['error']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Selecione um arquivo válido para enviar.');
    }
    if ((int)$arquivo['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('O arquivo deve ter no máximo 10 MB.');
    }

    $nomeOriginal = basename(str_replace('\\', '/', (string)$arquivo['name']));
    $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    $tiposPermitidos = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($arquivo['tmp_name']);
    if (!isset($tiposPermitidos[$extensao]) || $tiposPermitidos[$extensao] !== $mime) {
        throw new RuntimeException('Formato inválido. Envie um arquivo PDF, JPG ou PNG.');
    }

    if (!is_dir($pastaUpload) && !mkdir($pastaUpload, 0775, true) && !is_dir($pastaUpload)) {
        throw new RuntimeException('Não foi possível preparar a pasta do pedido.');
    }

    $nomeFisico = bin2hex(random_bytes(16)) . '.' . $extensao;
    $arquivoSalvo = $pastaUpload . '/' . $nomeFisico;
    if (!move_uploaded_file($arquivo['tmp_name'], $arquivoSalvo)) {
        throw new RuntimeException('Não foi possível salvar o arquivo enviado.');
    }

    $pdo->beginTransaction();
    $ins = $pdo->prepare("INSERT INTO documentos_exames (pedido_id, paciente_id, titulo_documento, arquivo_fisico, arquivo_original, mime_type, tamanho_bytes, enviado_por, status, validado_por, validado_em) VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', 'validado', ?, NOW())");
    $ins->execute([$pedidoId, $pedido['paciente_id'], $titulo, $nomeFisico, $nomeOriginal, $mime, (int)$arquivo['size'], $admin['id']]);

    registrarHistorico(
        $pdo,
        $pedidoId,
        $pedido['status'],
        $pedido['status'],
        'Documento adicionado pelo administrador',
        'Documento "' . $titulo . '" adicionado e validado pelo administrador.',
        'admin',
        (int)$admin['id'],
        $admin['nome']
    );
    $pdo->commit();
    $_SESSION['flash_swal'] = ['icon' => 'success', 'title' => 'Documento adicionado'];
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($arquivoSalvo && is_file($arquivoSalvo)) {
        unlink($arquivoSalvo);
    }
    $_SESSION['flash_swal'] = [
        'icon' => 'error',
        'title' => 'Não foi possível adicionar o documento',
        'text' => $e->getMessage(),
    ];
}

header('Location: ' . $redirect);
exit;