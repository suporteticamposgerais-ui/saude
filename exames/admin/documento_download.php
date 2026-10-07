<?php
// admin/documento_download.php
declare(strict_types=1);
require_once '../conexao.php';
require_once '../helpers/auth.php';

exigirLoginAdmin();
$docId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM documentos_exames WHERE id = ?");
$stmt->execute([$docId]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    die('Documento não encontrado.');
}

$caminho = __DIR__ . "/../uploads/exames/{$doc['pedido_id']}/{$doc['arquivo_fisico']}";
if (!file_exists($caminho)) {
    http_response_code(404);
    die('Arquivo não localizado no disco.');
}

header('Content-Type: ' . $doc['mime_type']);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('Content-Disposition: inline; filename="' . basename($doc['arquivo_original']) . '"');
header('Content-Length: ' . filesize($caminho));
readfile($caminho);
exit;
