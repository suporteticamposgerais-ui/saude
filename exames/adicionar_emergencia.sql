-- Script para adicionar suporte a pacientes de emergência
-- Execute este script no banco de dados

-- Adiciona coluna para marcar pacientes de emergência
ALTER TABLE pedidos_exames 
ADD COLUMN emergencia TINYINT(1) DEFAULT 0 AFTER status;

-- Adiciona coluna para prioridade (quanto menor, maior a prioridade)
-- 1 = Emergência, 2 = Normal
ALTER TABLE pedidos_exames 
ADD COLUMN prioridade INT DEFAULT 2 AFTER emergencia;

-- Atualiza registros existentes
UPDATE pedidos_exames 
SET prioridade = 2, emergencia = 0 
WHERE prioridade IS NULL;

-- Exemplo de como marcar um paciente como emergência:
-- UPDATE pedidos_exames SET emergencia = 1, prioridade = 1 WHERE id = 123;
