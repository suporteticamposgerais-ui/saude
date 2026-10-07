<?php
// index.php
declare(strict_types=1);
require_once 'helpers/seguranca.php';
iniciarSessaoSegura();
$pacienteLogado = !empty($_SESSION['paciente_id']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Exames e Consultas - Saúde Municipal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/exames.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .hero-box {
            text-align: center;
            padding: 30px 20px 20px;
        }
        .hero-title {
            color: #2a5298;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .hero-subtitle {
            color: #555;
            font-size: 15px;
            margin-bottom: 30px;
        }
        .btn-opcao {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            height: 60px;
            border-radius: 12px;
            font-size: 17px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-solicitar {
            background: #2a5298;
            color: #fff;
            border: none;
        }
        .btn-solicitar:hover {
            background: #1e3c72;
            color: #fff;
        }
        .btn-acompanhar {
            background: #f8fafc;
            color: #2a5298;
            border: 2px solid #2a5298;
        }
        .btn-acompanhar:hover {
            background: #eef5ff;
            color: #1e3c72;
        }
        .btn-fila-home {
            background: #e8f5e9;
            color: #166534;
            border: 2px solid #a7f3d0;
        }
        .btn-fila-home:hover {
            background: #d1fae5;
            color: #166534;
        }
    </style>
</head>
<body>
<div class="container container-exames">
    <div class="card-box shadow-sm">
        <div class="card-header-custom">
            Secretaria Municipal de Saúde
        </div>

        <div class="hero-box">
            <h1 class="hero-title">Central de Atendimento e Exames</h1>
            <p class="hero-subtitle">Solicite seus exames ou consultas especializadas e acompanhe cada etapa do seu pedido em tempo real.</p>

            <div class="row g-3 justify-content-center mb-4">
                <div class="col-md-5">
                    <a href="login.php" class="btn-opcao btn-solicitar shadow-sm">
                        <i class="fa-solid fa-file-medical fs-4"></i>
                        Acessar Minha Conta / Pedidos
                    </a>
                </div>
                <div class="col-md-5">
                    <a href="cadastro_paciente.php" class="btn-opcao btn-acompanhar shadow-sm">
                        <i class="fa-solid fa-user-plus fs-4"></i>
                        Primeiro Acesso / Cadastre-se
                    </a>
                </div>
                <?php if ($pacienteLogado): ?>
                    <div class="col-md-10">
                        <a href="acompanhamento_fila.php" class="btn-opcao btn-fila-home shadow-sm">
                            <i class="fa-solid fa-list-ol fs-4"></i>
                            Entrar para Consultar Posição na Fila
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pt-3 border-top d-flex justify-content-between align-items-center small text-muted px-3">
                <span>Atendimento ao Cidadão de Campos Gerais - MG</span>
                <a href="login_admin.php" class="text-decoration-none text-secondary">
                    <i class="fa-solid fa-lock me-1"></i> Acesso Administrativo
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
