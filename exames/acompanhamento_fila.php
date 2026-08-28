<?php
require 'conexao.php';

/************************************************
 * ACOMPANHAMENTO DE LISTA DE ESPERA
 * - Cidadão acessa com número de telefone
 * - Visualiza posição na fila por especialidade
 * - Nomes anonimizados (LGPD)
 ************************************************/

$telefone = '';
$pedidos = [];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['telefone'])) {
    $telefone = preg_replace('/\D/', '', $_POST['telefone']); // Remove caracteres não numéricos
    
    // Busca pedidos não finalizados do cidadão
    $sql = "SELECT id, tipo, especialidade, nome_paciente, criado_em, status, 
                   COALESCE(emergencia, 0) as emergencia, 
                   COALESCE(prioridade, 2) as prioridade
            FROM pedidos_exames 
            WHERE telefone LIKE ? 
            AND status != 'Finalizado'
            ORDER BY especialidade, prioridade, criado_em";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$telefone%"]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pedidos)) {
        $erro = 'Nenhum pedido em andamento encontrado para este telefone.';
    }
}

/**
 * Função para anonimizar nome conforme LGPD
 * Exemplo: "João Silva Santos" -> "J*** S*** S***"
 */
function anonimizarNome($nome) {
    $partes = explode(' ', trim($nome));
    $anonimizado = [];
    
    foreach ($partes as $parte) {
        if (strlen($parte) > 0) {
            $anonimizado[] = mb_substr($parte, 0, 1) . str_repeat('*', max(3, mb_strlen($parte) - 1));
        }
    }
    
    return implode(' ', $anonimizado);
}

/**
 * Busca posição na fila para uma especialidade específica
 * Prioriza: 1) Emergências, 2) Status, 3) Data de criação
 */
function obterFilaEspecialidade($pdo, $especialidade, $meuId) {
    $sql = "SELECT id, nome_paciente, criado_em, status,
                   COALESCE(emergencia, 0) as emergencia,
                   COALESCE(prioridade, 2) as prioridade
            FROM pedidos_exames 
            WHERE especialidade = ? 
            AND status != 'Finalizado'
            ORDER BY 
                prioridade ASC,
                CASE status
                    WHEN 'Em andamento' THEN 1
                    WHEN 'Visualizado' THEN 2
                    WHEN 'Solicitação recebida' THEN 3
                END,
                criado_em ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$especialidade]);
    $fila = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $posicao = 0;
    $total = count($fila);
    
    foreach ($fila as $index => $item) {
        if ($item['id'] == $meuId) {
            $posicao = $index + 1;
            break;
        }
    }
    
    return [
        'fila' => $fila,
        'posicao' => $posicao,
        'total' => $total
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/brasao.jpg" type="image/jpeg">
    <title>Acompanhamento de Lista de Espera</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            padding: 20px;
            color: #2d3748;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-top: 20px;
        }
        
        .header h1 {
            color: #1a202c;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #718096;
            font-size: 14px;
        }
        
        .form-box {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66,153,225,0.1);
        }
        
        .btn-consultar {
            width: 100%;
            padding: 11px;
            background: #2a5298;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 16px;
        }
        
        .btn-consultar:hover {
            background: #1e3a6d;
        }
        
        .erro {
            background: #fff5f5;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 3px solid #fc8181;
            font-size: 14px;
        }
        
        .pedido-card {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        
        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .pedido-titulo {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .badge-consulta {
            background: #f0fdf4;
            color: #166534;
        }
        
        .badge-exame {
            background: #eff6ff;
            color: #1e40af;
        }
        
        .info-linha {
            font-size: 14px;
            color: #4a5568;
            margin-bottom: 8px;
        }
        
        .info-linha strong {
            color: #2d3748;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 8px;
        }
        
        .status-em-andamento {
            background: #e0f2fe;
            color: #075985;
        }
        
        .status-visualizado {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-solicitacao-recebida {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .posicao-info {
            background: #f8fafc;
            border-left: 3px solid #2a5298;
            padding: 12px 16px;
            margin: 16px 0;
            font-size: 14px;
            color: #4a5568;
        }
        
        .posicao-info strong {
            color: #2a5298;
            font-size: 16px;
        }
        
        .fila-tabela {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 14px;
        }
        
        .fila-tabela thead {
            background: #f8fafc;
        }
        
        .fila-tabela th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .fila-tabela td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .fila-tabela tbody tr {
            transition: background 0.15s;
        }
        
        .fila-tabela tbody tr:hover {
            background: #f8fafc;
        }
        
        .fila-tabela tbody tr.minha-linha {
            background: #eff6ff;
            font-weight: 500;
        }
        
        .fila-tabela tbody tr.minha-linha:hover {
            background: #dbeafe;
        }
        
        .col-posicao {
            width: 80px;
            color: #64748b;
            font-weight: 500;
        }
        
        .col-nome {
            color: #334155;
        }
        
        .col-status {
            width: 180px;
            text-align: right;
        }
        
        .status-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pill.status-em-andamento {
            background: #e0f2fe;
            color: #075985;
        }
        
        .status-pill.status-visualizado {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-pill.status-solicitacao-recebida {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-emergencia {
            background: #fecaca;
            color: #991b1b;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-left: 8px;
        }
        
        .info-lgpd {
            background: #fefce8;
            border-left: 3px solid #eab308;
            padding: 12px 14px;
            border-radius: 6px;
            margin-top: 16px;
            font-size: 12px;
            color: #713f12;
            line-height: 1.5;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 12px;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .pedido-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .fila-tabela {
                font-size: 13px;
            }
            
            .fila-tabela th,
            .fila-tabela td {
                padding: 10px 12px;
            }
            
            .col-status {
                width: auto;
            }
            
            .status-pill {
                font-size: 11px;
                padding: 3px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Acompanhamento de Lista de Espera</h1>
            <p>Consulte sua posição na fila de atendimento</p>
        </div>
        
        <div class="form-box">
            <form method="POST">
                <div class="form-group">
                    <label for="telefone">Digite seu número de telefone:</label>
                    <input 
                        type="tel" 
                        id="telefone" 
                        name="telefone" 
                        placeholder="(00) 00000-0000"
                        value="<?= htmlspecialchars($telefone) ?>"
                        required
                    >
                </div>
                <button type="submit" class="btn-consultar">Consultar Posição na Fila</button>
            </form>
        </div>
        
        <?php if ($erro): ?>
            <div class="erro">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>
        
        <?php foreach ($pedidos as $pedido): ?>
            <?php 
                $dados = obterFilaEspecialidade($pdo, $pedido['especialidade'], $pedido['id']);
            ?>
            
            <div class="pedido-card">
                <div class="pedido-header">
                    <div class="pedido-titulo">
                        <?= htmlspecialchars($pedido['especialidade']) ?>
                    </div>
                    <span class="badge badge-<?= strtolower($pedido['tipo']) ?>">
                        <?= htmlspecialchars($pedido['tipo']) ?>
                    </span>
                </div>
                
                <div class="info-linha">
                    <strong>Paciente:</strong> <?= htmlspecialchars($pedido['nome_paciente']) ?>
                </div>
                
                <div class="info-linha">
                    <strong>Solicitado em:</strong> 
                    <?= date('d/m/Y', strtotime($pedido['criado_em'])) ?>
                </div>
                
                <div class="status-badge status-<?= strtolower(str_replace(' ', '-', $pedido['status'])) ?>">
                    <?= htmlspecialchars($pedido['status']) ?>
                </div>
                
                <div class="posicao-info">
                    Você está na posição <strong><?= $dados['posicao'] ?>º</strong> de <?= $dados['total'] ?> na fila
                </div>
                
                <table class="fila-tabela">
                    <thead>
                        <tr>
                            <th>Posição</th>
                            <th>Paciente</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dados['fila'] as $index => $item): ?>
                            <tr class="<?= $item['id'] == $pedido['id'] ? 'minha-linha' : '' ?>">
                                <td class="col-posicao"><?= $index + 1 ?>º</td>
                                <td class="col-nome">
                                    <?php 
                                        if ($item['id'] == $pedido['id']) {
                                            echo htmlspecialchars($item['nome_paciente']);
                                        } else {
                                            echo anonimizarNome($item['nome_paciente']);
                                        }
                                        
                                        // Mostra badge de emergência
                                        if ($item['emergencia'] == 1) {
                                            echo ' <span class="badge-emergencia">Emergência</span>';
                                        }
                                    ?>
                                </td>
                                <td class="col-status">
                                    <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $item['status'])) ?>">
                                        <?= htmlspecialchars($item['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="info-lgpd">
                    Os nomes dos demais pacientes foram anonimizados conforme LGPD.
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        // Máscara de telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>
