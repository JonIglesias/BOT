-- Migración: Agregar columnas de modelo y pricing a usage_tracking
-- Esto permite rastrear el modelo usado y calcular costos reales

ALTER TABLE `api_usage_tracking`
  -- Modelo de IA usado (gpt-4o, gpt-4o-mini, etc)
  ADD COLUMN `model` VARCHAR(50) NULL COMMENT 'Modelo de IA usado' AFTER `operation_type`,

  -- Tokens separados por tipo (para cálculo de costos preciso)
  ADD COLUMN `tokens_input` INT UNSIGNED DEFAULT 0 COMMENT 'Tokens del prompt' AFTER `tokens_total`,
  ADD COLUMN `tokens_output` INT UNSIGNED DEFAULT 0 COMMENT 'Tokens de la respuesta' AFTER `tokens_input`,

  -- Costos calculados desde api_model_prices
  ADD COLUMN `cost_input` DECIMAL(10,6) DEFAULT 0 COMMENT 'Costo de tokens input en USD' AFTER `tokens_output`,
  ADD COLUMN `cost_output` DECIMAL(10,6) DEFAULT 0 COMMENT 'Costo de tokens output en USD' AFTER `cost_input`,
  ADD COLUMN `cost_total` DECIMAL(10,6) DEFAULT 0 COMMENT 'Costo total en USD' AFTER `cost_output`,

  -- Tracking de campañas y batches
  ADD COLUMN `campaign_id` VARCHAR(100) NULL COMMENT 'ID de campaña' AFTER `cost_total`,
  ADD COLUMN `campaign_name` VARCHAR(255) NULL COMMENT 'Nombre de campaña' AFTER `campaign_id`,
  ADD COLUMN `batch_id` VARCHAR(100) NULL COMMENT 'ID de batch/cola' AFTER `campaign_name`,
  ADD COLUMN `batch_type` VARCHAR(50) NULL COMMENT 'Tipo: queue, bulk, etc' AFTER `batch_id`,

  -- Índices para consultas de costos
  ADD KEY `model` (`model`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `batch_id` (`batch_id`);

-- Actualizar registros existentes para tener valores por defecto
UPDATE `api_usage_tracking`
SET
  `model` = 'gpt-4o-mini',
  `tokens_input` = FLOOR(`tokens_total` / 2),
  `tokens_output` = CEIL(`tokens_total` / 2),
  `cost_input` = (FLOOR(`tokens_total` / 2) / 1000) * 0.00015,
  `cost_output` = (CEIL(`tokens_total` / 2) / 1000) * 0.0006,
  `cost_total` = ((FLOOR(`tokens_total` / 2) / 1000) * 0.00015) + ((CEIL(`tokens_total` / 2) / 1000) * 0.0006)
WHERE `model` IS NULL;
