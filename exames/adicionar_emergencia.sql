-- =====================================================
-- Adiciona os campos de emergência e prioridade ao fluxo
-- de pedidos, caso ainda não existam no banco atual
-- =====================================================

ALTER TABLE pedidos_exames
  ADD COLUMN IF NOT EXISTS emergencia TINYINT(1) NOT NULL DEFAULT 0 AFTER protocolo;

ALTER TABLE pedidos_exames
  ADD COLUMN IF NOT EXISTS prioridade INT NOT NULL DEFAULT 2 AFTER emergencia;

UPDATE pedidos_exames
SET emergencia = 0,
    prioridade = 2
WHERE emergencia IS NULL OR prioridade IS NULL;

-- Exemplo de uso:
-- UPDATE pedidos_exames SET emergencia = 1, prioridade = 1 WHERE id = 123;
