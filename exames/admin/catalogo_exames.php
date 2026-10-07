<?php
// admin/catalogo_exames.php
declare(strict_types=1);
require_once '../conexao.php';
require_once '../helpers/auth.php';
require_once '../helpers/seguranca.php';

$admin = exigirLoginAdmin();
if (!in_array($admin['perfil'], ['admin', 'gestor'], true)) {
    http_response_code(403);
    exit('Seu perfil nao pode alterar o catalogo.');
}

$erro = '';
$mensagem = $_SESSION['flash_catalogo'] ?? '';
unset($_SESSION['flash_catalogo']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new RuntimeException('Sessao expirada. Atualize a pagina e tente novamente.');
        }

        $acao = (string)($_POST['acao'] ?? '');
        if ($acao === 'alternar_status') {
            $exameId = (int)($_POST['exame_id'] ?? 0);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id, ativo FROM exames WHERE id = ? FOR UPDATE');
            $stmt->execute([$exameId]);
            $exame = $stmt->fetch();
            if (!$exame) {
                throw new RuntimeException('Procedimento nao encontrado.');
            }

            $novoStatus = (int)$exame['ativo'] === 1 ? 0 : 1;
            $up = $pdo->prepare('UPDATE exames SET ativo = ? WHERE id = ?');
            $up->execute([$novoStatus, $exameId]);
            $pdo->commit();
            $_SESSION['flash_catalogo'] = $novoStatus === 1
                ? 'Procedimento reativado.'
                : 'Procedimento desativado para novas solicitacoes.';
            header('Location: catalogo_exames.php');
            exit;
        }

        if ($acao !== 'salvar') {
            throw new RuntimeException('Acao invalida.');
        }

        $exameId = (int)($_POST['exame_id'] ?? 0);
        $especialidadeId = (int)($_POST['especialidade_id'] ?? 0);
        $unidadeId = (int)($_POST['unidade_id'] ?? 0);
        $nome = trim((string)($_POST['nome'] ?? ''));
        $tipo = (string)($_POST['tipo'] ?? '');
        $complexidade = (string)($_POST['complexidade'] ?? '');
        $preparo = trim((string)($_POST['preparo'] ?? ''));

        if ($nome === '' || mb_strlen($nome) > 150) {
            throw new RuntimeException('Informe um nome com ate 150 caracteres.');
        }
        if (!in_array($tipo, ['Consulta', 'Exame'], true)) {
            throw new RuntimeException('Selecione um tipo valido.');
        }
        if (!in_array($complexidade, ['baixa', 'media', 'alta'], true)) {
            throw new RuntimeException('Selecione uma complexidade valida.');
        }

        $pdo->beginTransaction();
        $ref = $pdo->prepare('SELECT id FROM especialidades WHERE id = ? AND ativo = 1');
        $ref->execute([$especialidadeId]);
        if (!$ref->fetchColumn()) {
            throw new RuntimeException('Selecione uma especialidade ativa.');
        }
        $ref = $pdo->prepare('SELECT id FROM unidades WHERE id = ? AND ativo = 1');
        $ref->execute([$unidadeId]);
        if (!$ref->fetchColumn()) {
            throw new RuntimeException('Selecione uma unidade ativa.');
        }

        if ($exameId > 0) {
            $lock = $pdo->prepare('SELECT id FROM exames WHERE id = ? FOR UPDATE');
            $lock->execute([$exameId]);
            if (!$lock->fetchColumn()) {
                throw new RuntimeException('Procedimento nao encontrado.');
            }
            $usos = $pdo->prepare('SELECT COUNT(*) FROM pedidos_exames WHERE exame_id = ?');
            $usos->execute([$exameId]);
            if ((int)$usos->fetchColumn() > 0) {
                throw new RuntimeException('Este procedimento ja possui pedidos. Para preservar o historico, desative-o e cadastre outro em vez de editar seus dados.');
            }
        }

        $duplicado = $pdo->prepare(
            'SELECT id FROM exames WHERE nome = ? AND tipo = ? AND especialidade_id = ? AND unidade_id = ? AND id <> ? LIMIT 1'
        );
        $duplicado->execute([$nome, $tipo, $especialidadeId, $unidadeId, $exameId]);
        if ($duplicado->fetchColumn()) {
            throw new RuntimeException('Ja existe um procedimento igual para esta unidade e especialidade.');
        }

        if ($exameId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE exames SET especialidade_id = ?, nome = ?, tipo = ?, complexidade = ?, unidade_id = ?, preparo = ? WHERE id = ?'
            );
            $stmt->execute([$especialidadeId, $nome, $tipo, $complexidade, $unidadeId, $preparo ?: null, $exameId]);
            $mensagem = 'Procedimento atualizado.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO exames (especialidade_id, nome, tipo, complexidade, unidade_id, preparo) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$especialidadeId, $nome, $tipo, $complexidade, $unidadeId, $preparo ?: null]);
            $mensagem = 'Procedimento cadastrado.';
        }

        $pdo->commit();
        $_SESSION['flash_catalogo'] = $mensagem;
        header('Location: catalogo_exames.php');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erro = $e->getMessage();
    }
}

$edicaoId = (int)($_GET['editar'] ?? 0);
$edicao = null;
if ($edicaoId > 0) {
    $stmt = $pdo->prepare(
        'SELECT e.*, (SELECT COUNT(*) FROM pedidos_exames p WHERE p.exame_id = e.id) AS total_pedidos FROM exames e WHERE e.id = ?'
    );
    $stmt->execute([$edicaoId]);
    $edicao = $stmt->fetch();
    if (!$edicao) {
        $erro = 'Procedimento nao encontrado.';
    } elseif ((int)$edicao['total_pedidos'] > 0) {
        $erro = 'Este procedimento ja possui pedidos e nao pode ser editado. Desative-o e cadastre outro para preservar o historico.';
        $edicao = null;
    }
}

$especialidades = $pdo->query('SELECT id, nome FROM especialidades WHERE ativo = 1 ORDER BY nome')->fetchAll();
$unidades = $pdo->query('SELECT id, nome FROM unidades WHERE ativo = 1 ORDER BY nome')->fetchAll();
$procedimentos = $pdo->query(
    "SELECT e.id, e.nome, e.tipo, e.complexidade, e.ativo, esp.nome AS especialidade_nome, u.nome AS unidade_nome,
            (SELECT COUNT(*) FROM pedidos_exames p WHERE p.exame_id = e.id) AS total_pedidos
     FROM exames e
     INNER JOIN especialidades esp ON esp.id = e.especialidade_id
     INNER JOIN unidades u ON u.id = e.unidade_id
     ORDER BY e.ativo DESC, u.nome, e.nome"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo de Procedimentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/exames.css" rel="stylesheet">
    <style>
        body { background: #f4f6f8; padding: 20px; }
        .catalogo-wrap { max-width: 1180px; margin: 0 auto; }
        .catalogo-form { background: #fff; border: 1px solid #dfe4ea; border-radius: 8px; padding: 20px; }
        .catalogo-table td, .catalogo-table th { vertical-align: middle; }
    </style>
</head>
<body>
<div class="catalogo-wrap">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">Catalogo de Procedimentos</h1>
            <div class="small text-muted">Administracao: <?= htmlspecialchars($admin['nome']) ?></div>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary">Painel</a>
            <a href="listagem_ordens.php" class="btn btn-outline-primary">Pedidos</a>
        </div>
    </div>

    <?php if ($mensagem): ?><div class="alert alert-success py-2"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <section class="catalogo-form mb-4" aria-labelledby="titulo-formulario">
        <h2 class="h5 fw-bold" id="titulo-formulario"><?= $edicao ? 'Editar procedimento' : 'Cadastrar procedimento' ?></h2>
        <?php if (!$especialidades || !$unidades): ?>
            <div class="alert alert-warning mb-0">Cadastre ao menos uma especialidade e uma unidade ativa antes de incluir procedimentos.</div>
        <?php else: ?>
            <form method="POST" action="catalogo_exames.php" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>">
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="exame_id" value="<?= (int)($edicao['id'] ?? 0) ?>">
                <div class="col-md-5">
                    <label for="nome" class="form-label">Nome do procedimento</label>
                    <input id="nome" name="nome" class="form-control" maxlength="150" required value="<?= htmlspecialchars($edicao['nome'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="especialidade_id" class="form-label">Especialidade</label>
                    <select id="especialidade_id" name="especialidade_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($especialidades as $especialidade): ?>
                            <option value="<?= (int)$especialidade['id'] ?>" <?= (string)($edicao['especialidade_id'] ?? '') === (string)$especialidade['id'] ? 'selected' : '' ?>><?= htmlspecialchars($especialidade['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach (['Consulta', 'Exame'] as $tipo): ?>
                            <option value="<?= $tipo ?>" <?= ($edicao['tipo'] ?? '') === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="complexidade" class="form-label">Complexidade</label>
                    <select id="complexidade" name="complexidade" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach (['baixa' => 'Baixa', 'media' => 'Media', 'alta' => 'Alta'] as $valor => $rotulo): ?>
                            <option value="<?= $valor ?>" <?= ($edicao['complexidade'] ?? '') === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="unidade_id" class="form-label">Unidade</label>
                    <select id="unidade_id" name="unidade_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($unidades as $unidade): ?>
                            <option value="<?= (int)$unidade['id'] ?>" <?= (string)($edicao['unidade_id'] ?? '') === (string)$unidade['id'] ? 'selected' : '' ?>><?= htmlspecialchars($unidade['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label for="preparo" class="form-label">Preparo / orientacoes (opcional)</label>
                    <textarea id="preparo" name="preparo" class="form-control" rows="2"><?= htmlspecialchars($edicao['preparo'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold"><?= $edicao ? 'Salvar alteracoes' : 'Cadastrar procedimento' ?></button>
                    <?php if ($edicao): ?><a href="catalogo_exames.php" class="btn btn-outline-secondary">Cancelar edicao</a><?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="card-box" aria-labelledby="titulo-lista">
        <div class="card-header-custom" id="titulo-lista">Procedimentos cadastrados</div>
        <div class="table-responsive p-2">
            <table class="table table-hover catalogo-table mb-0">
                <thead class="table-light"><tr><th>Procedimento</th><th>Especialidade</th><th>Unidade</th><th>Complexidade</th><th>Situacao</th><th>Pedidos</th><th>Acoes</th></tr></thead>
                <tbody>
                <?php foreach ($procedimentos as $procedimento): ?>
                    <tr>
                        <td><?= htmlspecialchars($procedimento['nome']) ?></td>
                        <td><?= htmlspecialchars($procedimento['especialidade_nome']) ?></td>
                        <td><?= htmlspecialchars($procedimento['unidade_nome']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($procedimento['complexidade'])) ?></td>
                        <td><span class="badge <?= $procedimento['ativo'] ? 'bg-success' : 'bg-secondary' ?>"><?= $procedimento['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                        <td><?= (int)$procedimento['total_pedidos'] ?></td>
                        <td class="text-nowrap">
                            <?php if ((int)$procedimento['total_pedidos'] === 0): ?>
                                <a href="catalogo_exames.php?editar=<?= (int)$procedimento['id'] ?>" class="btn btn-outline-primary btn-sm">Editar</a>
                            <?php else: ?>
                                <span class="small text-muted">Edicao bloqueada para preservar historico</span>
                            <?php endif; ?>
                            <form method="POST" action="catalogo_exames.php" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= gerarCsrfToken() ?>">
                                <input type="hidden" name="acao" value="alternar_status">
                                <input type="hidden" name="exame_id" value="<?= (int)$procedimento['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $procedimento['ativo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                    <?= $procedimento['ativo'] ? 'Desativar' : 'Reativar' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$procedimentos): ?><tr><td colspan="7" class="text-center text-muted py-4">Nenhum procedimento cadastrado.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>
