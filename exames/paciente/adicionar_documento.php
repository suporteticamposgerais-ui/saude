<?php
declare(strict_types=1);
require_once '../conexao.php';
require_once '../helpers/auth.php';
require_once '../helpers/seguranca.php';
require_once '../helpers/pedidos.php';

$paciente = exigirLoginPaciente();
$pedidoId = (int)($_POST['pedido_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrfToken($_POST['csrf_token'] ?? '')) die('Requisição inválida ou sessão expirada.');

$stmt = $pdo->prepare("SELECT * FROM pedidos_exames WHERE id = ? AND paciente_id = ?");
$stmt->execute([$pedidoId, $paciente['id']]);
$pedido = $stmt->fetch();
if (!$pedido) die('Pedido não encontrado.');
if ($pedido['status'] !== 'em_espera') die('Este pedido não está aguardando documentos.');
if (empty($_FILES['documentos']['tmp_name'][0])) die('Selecione pelo menos um arquivo.');

$titulos = $_POST['titulo_documento'] ?? [];
$arquivos = $_FILES['documentos'];
if (count($arquivos['name']) > 10) die('Você pode enviar no máximo 10 arquivos por resposta.');

$permitidos = ['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$pasta = __DIR__ . "/../uploads/exames/{$pedidoId}";
$arquivosSalvos = [];

try {
    $pdo->beginTransaction();
    if (!is_dir($pasta) && !mkdir($pasta, 0775, true) && !is_dir($pasta)) throw new RuntimeException('Nao foi possivel criar a pasta dos documentos.');
    foreach ($arquivos['tmp_name'] as $i => $tmp) {
        if ($arquivos['error'][$i] !== UPLOAD_ERR_OK) throw new RuntimeException('Um dos arquivos não pôde ser enviado.');
        if ((int)$arquivos['size'][$i] > 10 * 1024 * 1024) throw new RuntimeException('Cada arquivo deve ter no máximo 10 MB.');
        $titulo = trim((string)($titulos[$i] ?? ''));
        if ($titulo === '' || mb_strlen($titulo) > 150) throw new RuntimeException('Informe um título válido para cada documento.');
        $orig = (string)$arquivos['name'][$i];
        $mime = $finfo->file($tmp);
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!isset($permitidos[$ext]) || $permitidos[$ext] !== $mime) throw new RuntimeException("Arquivo inválido: {$orig}. Envie PDF, JPG ou PNG.");
        $novo = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($tmp, $pasta . '/' . $novo)) throw new RuntimeException('Não foi possível salvar um dos arquivos.');
        $arquivosSalvos[] = $pasta . '/' . $novo;
        $ins = $pdo->prepare("INSERT INTO documentos_exames (pedido_id,paciente_id,titulo_documento,arquivo_fisico,arquivo_original,mime_type,tamanho_bytes,enviado_por) VALUES (?,?,?,?,?,?,?,'paciente')");
        $ins->execute([$pedidoId,$paciente['id'],$titulo,$novo,$orig,$mime,(int)$arquivos['size'][$i]]);
    }
    $pdo->prepare("UPDATE pedidos_exames SET status='aguardando_nova_analise', ultima_resposta_paciente=NOW() WHERE id=?")->execute([$pedidoId]);
    $descricao = 'Paciente enviou novos documentos em resposta à pendência.';
    registrarHistorico($pdo,$pedidoId,'em_espera','aguardando_nova_analise','Nova resposta do paciente',$descricao,'paciente',$paciente['id'],$paciente['nome']);
    criarNotificacaoAdmin($pdo,$pedidoId,'nova_resposta',"{$paciente['nome']} enviou novo(s) documento(s) para o pedido {$pedido['protocolo']}.");
    $pdo->commit();
    header('Location: painel.php?documento_enviado=1'); exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach ($arquivosSalvos as $arquivoSalvo) if (is_file($arquivoSalvo)) unlink($arquivoSalvo);
    if (is_dir($pasta) && count(scandir($pasta) ?: []) === 2) rmdir($pasta);
    die('Falha ao enviar documentos: ' . htmlspecialchars($e->getMessage()));
}
