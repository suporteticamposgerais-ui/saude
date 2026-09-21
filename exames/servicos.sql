-- =====================================================
-- Banco principal do projeto SAÚDE / PREFEITURA
-- Importação limpa do zero
-- =====================================================

CREATE DATABASE IF NOT EXISTS servicos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE servicos;

-- =====================================================
-- Tabela principal de pedidos
-- =====================================================
CREATE TABLE IF NOT EXISTS pedidos_exames (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome_paciente VARCHAR(255) NOT NULL,
    cartao_sus VARCHAR(20) NOT NULL,
    telefone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    rua VARCHAR(255) NULL,
    numero VARCHAR(30) NULL,
    bairro VARCHAR(150) NULL,
    exame_solicitado TEXT NOT NULL,
    especialidade VARCHAR(255) NULL,
    observacoes TEXT NULL,
    status VARCHAR(80) NOT NULL DEFAULT 'Solicitação recebida',
    complexidade ENUM('baixa', 'media', 'alta') NOT NULL DEFAULT 'baixa',
    protocolo VARCHAR(30) NOT NULL,
    emergencia TINYINT(1) NOT NULL DEFAULT 0,
    prioridade INT NOT NULL DEFAULT 2,
    robo TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_envio_robo DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_pedidos_protocolo (protocolo),
    KEY idx_pedidos_status (status),
    KEY idx_pedidos_complexidade (complexidade),
    KEY idx_pedidos_telefone (telefone),
    KEY idx_pedidos_email (email),
    KEY idx_pedidos_cartao_sus (cartao_sus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Documentos anexados
-- =====================================================
CREATE TABLE IF NOT EXISTS documentos_exames (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NOT NULL,
    titulo_documento VARCHAR(255) NOT NULL DEFAULT 'Documento',
    arquivo VARCHAR(255) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_documentos_pedido (pedido_id),
    CONSTRAINT fk_documentos_exames_pedido
        FOREIGN KEY (pedido_id)
        REFERENCES pedidos_exames (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Mensagens dos pedidos
-- =====================================================
CREATE TABLE IF NOT EXISTS mensagens_exames (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NOT NULL,
    mensagem TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pendente',
    tipo VARCHAR(30) NOT NULL DEFAULT 'sms',
    robo TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mensagens_pedido (pedido_id),
    KEY idx_mensagens_status (status),
    CONSTRAINT fk_mensagens_exames_pedido
        FOREIGN KEY (pedido_id)
        REFERENCES pedidos_exames (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Controle de sequência do protocolo SAU por ano
-- =====================================================
CREATE TABLE IF NOT EXISTS protocolo_controle (
    ano YEAR NOT NULL,
    ultimo_numero BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (ano)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Exemplo de uso da sequência:
-- SAU-2026-000001
-- SAU-2026-000002
-- SAU-2027-000001
-- =====================================================

INSERT INTO protocolo_controle (ano, ultimo_numero)
SELECT YEAR(CURDATE()), 0
WHERE NOT EXISTS (
    SELECT 1 FROM protocolo_controle WHERE ano = YEAR(CURDATE())
);

-- =====================================================
-- Fim do banco principal
-- =====================================================