-- ============================================================
-- SERVICOS - BANCO NOVO COMPLETO
-- Instalação limpa para o sistema atual
-- NÃO é uma migração. Pode importar diretamente no phpMyAdmin.
-- O script apaga e recria o banco `servicos`.
-- ============================================================

DROP DATABASE IF EXISTS servicos;
CREATE DATABASE servicos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE servicos;

-- 1. Usuários Administrativos
CREATE TABLE usuarios_admin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'gestor', 'atendente') NOT NULL DEFAULT 'atendente',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Pacientes
CREATE TABLE pacientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(11) NOT NULL UNIQUE,
    cns VARCHAR(15) NOT NULL UNIQUE,
    data_nascimento DATE NOT NULL,
    telefone VARCHAR(11) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    cep VARCHAR(8) NULL,
    rua VARCHAR(150) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    bairro VARCHAR(80) NOT NULL,
    cidade VARCHAR(80) NOT NULL DEFAULT 'Campos Gerais',
    estado CHAR(2) NOT NULL DEFAULT 'MG',
    complemento VARCHAR(100) NULL,
    senha VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_paciente_cpf (cpf),
    INDEX idx_paciente_cns (cns),
    INDEX idx_paciente_tel (telefone)
) ENGINE=InnoDB;

-- 3. Recuperação de Senhas
CREATE TABLE recuperacao_senhas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_usuario ENUM('paciente', 'admin') NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recup_token (token, expira_em, usado)
) ENGINE=InnoDB;

-- 4. Unidades de Atendimento
CREATE TABLE unidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL UNIQUE,
    chave VARCHAR(50) NOT NULL UNIQUE,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO unidades (nome, chave) VALUES
('Secretaria de Saúde', 'secretaria'),
('Policlínica', 'policlinica');

-- 5. Especialidades
CREATE TABLE especialidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    tipo ENUM('Consulta', 'Exame', 'Ambos') NOT NULL DEFAULT 'Ambos',
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- 6. Catálogo de Procedimentos / Exames
CREATE TABLE exames (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    especialidade_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('Consulta', 'Exame') NOT NULL DEFAULT 'Exame',
    complexidade ENUM('baixa', 'media', 'alta') NOT NULL DEFAULT 'baixa',
    unidade_id INT UNSIGNED NOT NULL,
    preparo TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (especialidade_id) REFERENCES especialidades(id) ON UPDATE CASCADE,
    FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON UPDATE CASCADE,
    INDEX idx_exame_unidade (unidade_id),
    INDEX idx_exame_busca (nome, tipo, complexidade)
) ENGINE=InnoDB;

-- 7. Tabela de Dependências Futuras entre Exames
CREATE TABLE dependencias_exames (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exame_id INT UNSIGNED NOT NULL,
    exame_requisito_id INT UNSIGNED NOT NULL,
    obrigatorio TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (exame_id) REFERENCES exames(id) ON DELETE CASCADE,
    FOREIGN KEY (exame_requisito_id) REFERENCES exames(id) ON DELETE CASCADE,
    UNIQUE KEY uk_dep_exames (exame_id, exame_requisito_id)
) ENGINE=InnoDB;

-- 8. Controle Anual de Protocolos
CREATE TABLE protocolo_controle (
    ano INT UNSIGNED NOT NULL PRIMARY KEY,
    ultimo_numero INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- 9. Pedidos de Exames
CREATE TABLE pedidos_exames (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    protocolo VARCHAR(25) NOT NULL UNIQUE,
    colocacao INT UNSIGNED NOT NULL DEFAULT 0,
    paciente_id INT UNSIGNED NOT NULL,
    exame_id INT UNSIGNED NOT NULL,
    unidade_id INT UNSIGNED NOT NULL,
    complexidade ENUM('baixa', 'media', 'alta') NOT NULL DEFAULT 'baixa',
    status ENUM(
        'aguardando_analise',
        'aguardando_nova_analise',
        'em_espera',
        'aguardando_vaga',
        'agendado',
        'em_andamento',
        'realizado',
        'finalizado',
        'cancelado'
    ) NOT NULL DEFAULT 'aguardando_analise',
    prioridade TINYINT UNSIGNED NOT NULL DEFAULT 2, -- 1=Emergência, 2=Normal
    emergencia TINYINT(1) NOT NULL DEFAULT 0,
    motivo_espera TEXT NULL,
    espera_desde DATETIME NULL,
    ultima_resposta_paciente DATETIME NULL,
    motivo_cancelamento TEXT NULL,
    observacoes TEXT NULL,
    data_entrada_fila DATETIME NULL,
    visualizado_em DATETIME NULL,
    robo TINYINT(1) NOT NULL DEFAULT 0,
    data_envio_robo DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON UPDATE CASCADE,
    FOREIGN KEY (exame_id) REFERENCES exames(id) ON UPDATE CASCADE,
    FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON UPDATE CASCADE,
    INDEX idx_pedido_unidade (unidade_id),
    INDEX idx_pedido_status (status),
    INDEX idx_pedido_complex (complexidade),
    INDEX idx_pedido_colocacao (exame_id, colocacao),
    INDEX idx_pedido_fila (status, prioridade, data_entrada_fila),
    INDEX idx_pedido_espera (status, espera_desde),
    INDEX idx_pedido_resposta (status, ultima_resposta_paciente)
) ENGINE=InnoDB;

-- 10. Agendamentos
CREATE TABLE agendamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    data_hora DATETIME NOT NULL,
    local_unidade VARCHAR(150) NOT NULL,
    profissional VARCHAR(120) NULL,
    status ENUM('agendado', 'confirmado', 'concluido', 'falta', 'cancelado') NOT NULL DEFAULT 'agendado',
    orientacoes TEXT NULL,
    criado_por INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos_exames(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios_admin(id) ON UPDATE CASCADE,
    INDEX idx_agendamento_data (data_hora, local_unidade)
) ENGINE=InnoDB;

-- 11. Documentos e Anexos
CREATE TABLE documentos_exames (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    paciente_id INT UNSIGNED NOT NULL,
    titulo_documento VARCHAR(150) NOT NULL,
    arquivo_fisico VARCHAR(255) NOT NULL,
    arquivo_original VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    tamanho_bytes INT UNSIGNED NOT NULL,
    enviado_por ENUM('paciente', 'admin') NOT NULL DEFAULT 'paciente',
    status ENUM('pendente','validado','rejeitado') NOT NULL DEFAULT 'pendente',
    observacao_admin TEXT NULL,
    validado_por INT UNSIGNED NULL,
    validado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos_exames(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (validado_por) REFERENCES usuarios_admin(id) ON UPDATE CASCADE,
    INDEX idx_documentos_status (status, criado_em),
    INDEX idx_documentos_paciente (paciente_id, criado_em),
    INDEX idx_documentos_pedido (pedido_id, criado_em)
) ENGINE=InnoDB;

-- 12. Histórico e Auditoria
CREATE TABLE historico_pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    status_anterior VARCHAR(50) NULL,
    status_novo VARCHAR(50) NOT NULL,
    acao VARCHAR(100) NOT NULL,
    observacao TEXT NULL,
    autor_tipo ENUM('paciente', 'admin', 'sistema') NOT NULL,
    autor_id INT UNSIGNED NULL,
    autor_nome VARCHAR(120) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos_exames(id) ON DELETE CASCADE,
    INDEX idx_hist_pedido (pedido_id, criado_em)
) ENGINE=InnoDB;

-- 13. Mensagens do Pedido
CREATE TABLE mensagens_exames (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    autor_tipo ENUM('admin', 'paciente') NOT NULL DEFAULT 'admin',
    autor_id INT UNSIGNED NULL,
    mensagem TEXT NOT NULL,
    status ENUM('Pendente', 'Enviado', 'Lido') NOT NULL DEFAULT 'Pendente',
    tipo ENUM('sms', 'whatsapp', 'sistema') NOT NULL DEFAULT 'sms',
    robo TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos_exames(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 14. Notificações internas do painel administrativo
CREATE TABLE notificacoes_admin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    mensagem VARCHAR(255) NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    lida_por INT UNSIGNED NULL,
    lida_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos_exames(id) ON DELETE CASCADE,
    FOREIGN KEY (lida_por) REFERENCES usuarios_admin(id) ON UPDATE CASCADE,
    INDEX idx_notif_admin (lida, criado_em),
    INDEX idx_notif_pedido (pedido_id, criado_em)
) ENGINE=InnoDB;

-- Carga inicial de Especialidades
INSERT INTO especialidades (nome, tipo) VALUES
('Clínica Geral', 'Consulta'),
('Pediatria', 'Consulta'),
('Ginecologia', 'Consulta'),
('Obstetrícia', 'Consulta'),
('Cardiologia', 'Ambos'),
('Dermatologia', 'Consulta'),
('Neurologia', 'Consulta'),
('Ortopedia', 'Consulta'),
('Oftalmologia', 'Consulta'),
('Otorrinolaringologia', 'Consulta'),
('Urologia', 'Consulta'),
('Endocrinologia', 'Consulta'),
('Gastroenterologia', 'Consulta'),
('Pneumologia', 'Consulta'),
('Reumatologia', 'Consulta'),
('Oncologia', 'Consulta'),
('Psiquiatria', 'Consulta'),
('Psicologia', 'Consulta'),
('Fonoaudiologia', 'Consulta'),
('Fisioterapia', 'Consulta'),
('Nutrição', 'Consulta'),
('Terapia Ocupacional', 'Consulta'),
('Assistência Social', 'Consulta'),
('Hematologia / Sangue', 'Exame'),
('Urinálise', 'Exame'),
('Parasitologia', 'Exame'),
('Diagnóstico por Imagem', 'Exame'),
('Preventivos', 'Exame'),
('Sorologias', 'Exame');

-- Resolve IDs pelo nome/chave, sem depender da ordem do AUTO_INCREMENT.
SET @unidade_secretaria = (SELECT id FROM unidades WHERE chave = 'secretaria');
SET @unidade_policlinica = (SELECT id FROM unidades WHERE chave = 'policlinica');
SET @esp_clinica_geral = (SELECT id FROM especialidades WHERE nome = 'Clínica Geral');
SET @esp_pediatria = (SELECT id FROM especialidades WHERE nome = 'Pediatria');
SET @esp_ginecologia = (SELECT id FROM especialidades WHERE nome = 'Ginecologia');
SET @esp_obstetricia = (SELECT id FROM especialidades WHERE nome = 'Obstetrícia');
SET @esp_cardiologia = (SELECT id FROM especialidades WHERE nome = 'Cardiologia');
SET @esp_dermatologia = (SELECT id FROM especialidades WHERE nome = 'Dermatologia');
SET @esp_neurologia = (SELECT id FROM especialidades WHERE nome = 'Neurologia');
SET @esp_ortopedia = (SELECT id FROM especialidades WHERE nome = 'Ortopedia');
SET @esp_oftalmologia = (SELECT id FROM especialidades WHERE nome = 'Oftalmologia');
SET @esp_otorrino = (SELECT id FROM especialidades WHERE nome = 'Otorrinolaringologia');
SET @esp_urologia = (SELECT id FROM especialidades WHERE nome = 'Urologia');
SET @esp_endocrinologia = (SELECT id FROM especialidades WHERE nome = 'Endocrinologia');
SET @esp_gastroenterologia = (SELECT id FROM especialidades WHERE nome = 'Gastroenterologia');
SET @esp_pneumologia = (SELECT id FROM especialidades WHERE nome = 'Pneumologia');
SET @esp_reumatologia = (SELECT id FROM especialidades WHERE nome = 'Reumatologia');
SET @esp_oncologia = (SELECT id FROM especialidades WHERE nome = 'Oncologia');
SET @esp_psiquiatria = (SELECT id FROM especialidades WHERE nome = 'Psiquiatria');
SET @esp_psicologia = (SELECT id FROM especialidades WHERE nome = 'Psicologia');
SET @esp_fonoaudiologia = (SELECT id FROM especialidades WHERE nome = 'Fonoaudiologia');
SET @esp_fisioterapia = (SELECT id FROM especialidades WHERE nome = 'Fisioterapia');
SET @esp_nutricao = (SELECT id FROM especialidades WHERE nome = 'Nutrição');
SET @esp_terapia_ocupacional = (SELECT id FROM especialidades WHERE nome = 'Terapia Ocupacional');
SET @esp_assistencia_social = (SELECT id FROM especialidades WHERE nome = 'Assistência Social');
SET @esp_hematologia = (SELECT id FROM especialidades WHERE nome = 'Hematologia / Sangue');
SET @esp_urinalise = (SELECT id FROM especialidades WHERE nome = 'Urinálise');
SET @esp_parasitologia = (SELECT id FROM especialidades WHERE nome = 'Parasitologia');
SET @esp_imagem = (SELECT id FROM especialidades WHERE nome = 'Diagnóstico por Imagem');
SET @esp_preventivos = (SELECT id FROM especialidades WHERE nome = 'Preventivos');
SET @esp_sorologias = (SELECT id FROM especialidades WHERE nome = 'Sorologias');

-- Carga de Exames e Consultas
INSERT INTO exames (especialidade_id, nome, tipo, complexidade, unidade_id) VALUES
-- Secretaria de Saúde (baixa)
(@esp_clinica_geral, 'Consulta - Clínica Geral', 'Consulta', 'baixa', @unidade_secretaria),
(@esp_pediatria, 'Consulta - Pediatria', 'Consulta', 'baixa', @unidade_secretaria),
(@esp_ginecologia, 'Consulta - Ginecologia', 'Consulta', 'baixa', @unidade_secretaria),
(@esp_obstetricia, 'Consulta - Obstetrícia', 'Consulta', 'baixa', @unidade_secretaria),
(@esp_psicologia, 'Consulta - Psicologia', 'Consulta', 'baixa', @unidade_secretaria),
(@esp_fisioterapia, 'Consulta - Fisioterapia', 'Consulta', 'baixa', @unidade_secretaria),
(@esp_nutricao, 'Consulta - Nutrição', 'Consulta', 'baixa', @unidade_secretaria),
(@esp_assistencia_social, 'Consulta - Assistência Social', 'Consulta', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - Hemograma completo', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - Glicemia de jejum', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - Hemoglobina glicada (HbA1c)', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - Colesterol total', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - HDL', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - LDL', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - Triglicerídeos', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - Ureia', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - Creatinina', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - TGO (AST)', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - TGP (ALT)', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - Ácido úrico', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - TSH', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - T4 Livre', 'Exame', 'baixa', @unidade_secretaria),
(@esp_hematologia, 'Exame - PCR', 'Exame', 'baixa', @unidade_secretaria),
(@esp_urinalise, 'Exame - EAS (Urina tipo 1)', 'Exame', 'baixa', @unidade_secretaria),
(@esp_urinalise, 'Exame - Urocultura', 'Exame', 'baixa', @unidade_secretaria),
(@esp_urinalise, 'Exame - Microalbuminúria', 'Exame', 'baixa', @unidade_secretaria),
(@esp_parasitologia, 'Exame - Parasitológico de fezes', 'Exame', 'baixa', @unidade_secretaria),
(@esp_parasitologia, 'Exame - Sangue oculto nas fezes', 'Exame', 'baixa', @unidade_secretaria),
(@esp_preventivos, 'Exame - Papanicolau (Citologia oncótica)', 'Exame', 'baixa', @unidade_secretaria),
(@esp_preventivos, 'Exame - PSA total', 'Exame', 'baixa', @unidade_secretaria),
(@esp_preventivos, 'Exame - PSA livre', 'Exame', 'baixa', @unidade_secretaria),
(@esp_sorologias, 'Exame - HIV', 'Exame', 'baixa', @unidade_secretaria),
(@esp_sorologias, 'Exame - Hepatite B', 'Exame', 'baixa', @unidade_secretaria),
(@esp_sorologias, 'Exame - Hepatite C', 'Exame', 'baixa', @unidade_secretaria),
(@esp_sorologias, 'Exame - VDRL (Sífilis)', 'Exame', 'baixa', @unidade_secretaria),
(@esp_sorologias, 'Exame - Dengue', 'Exame', 'baixa', @unidade_secretaria),
(@esp_sorologias, 'Exame - Toxoplasmose', 'Exame', 'baixa', @unidade_secretaria),
-- Policlínica (media)
(@esp_cardiologia, 'Consulta - Cardiologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_dermatologia, 'Consulta - Dermatologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_neurologia, 'Consulta - Neurologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_ortopedia, 'Consulta - Ortopedia', 'Consulta', 'media', @unidade_policlinica),
(@esp_oftalmologia, 'Consulta - Oftalmologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_otorrino, 'Consulta - Otorrinolaringologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_urologia, 'Consulta - Urologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_endocrinologia, 'Consulta - Endocrinologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_gastroenterologia, 'Consulta - Gastroenterologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_pneumologia, 'Consulta - Pneumologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_reumatologia, 'Consulta - Reumatologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_oncologia, 'Consulta - Oncologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_psiquiatria, 'Consulta - Psiquiatria', 'Consulta', 'media', @unidade_policlinica),
(@esp_fonoaudiologia, 'Consulta - Fonoaudiologia', 'Consulta', 'media', @unidade_policlinica),
(@esp_terapia_ocupacional, 'Consulta - Terapia Ocupacional', 'Consulta', 'media', @unidade_policlinica),
(@esp_imagem, 'Exame - Raio-X', 'Exame', 'media', @unidade_policlinica),
(@esp_imagem, 'Exame - Ultrassonografia abdominal', 'Exame', 'media', @unidade_policlinica),
(@esp_imagem, 'Exame - Ultrassonografia pélvica', 'Exame', 'media', @unidade_policlinica),
(@esp_imagem, 'Exame - Ultrassonografia obstétrica', 'Exame', 'media', @unidade_policlinica),
(@esp_imagem, 'Exame - Ultrassonografia transvaginal', 'Exame', 'media', @unidade_policlinica),
(@esp_imagem, 'Exame - Mamografia', 'Exame', 'media', @unidade_policlinica),
(@esp_cardiologia, 'Exame - Eletrocardiograma (ECG)', 'Exame', 'media', @unidade_policlinica),
(@esp_cardiologia, 'Exame - Teste ergométrico', 'Exame', 'media', @unidade_policlinica),
(@esp_cardiologia, 'Exame - Holter 24h', 'Exame', 'media', @unidade_policlinica),
(@esp_cardiologia, 'Exame - MAPA 24h', 'Exame', 'media', @unidade_policlinica),
(@esp_cardiologia, 'Exame - Ecocardiograma', 'Exame', 'media', @unidade_policlinica);

-- Administrador padrão:
-- E-mail: admin@camposgerais.mg.gov.br
-- Senha:  Admin@2026!
INSERT INTO usuarios_admin (nome, email, senha, perfil, ativo)
VALUES (
    'Administrador Geral',
    'admin@camposgerais.mg.gov.br',
    '$2y$12$V3XrqCYhAtqtOKEugZTh/Oz3aMA8JlBldKZv.z9zyf4jB9efaj9M6',
    'admin',
    1
);
