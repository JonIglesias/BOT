-- ============================================================================
-- Instalador de Planes del Chatbot
-- ============================================================================
--
-- Este script crea/actualiza los planes del chatbot en la base de datos
-- Ejecutar desde phpMyAdmin o línea de comandos MySQL
--
-- @version 1.0
-- ============================================================================

-- Plan Starter (50,000 tokens/mes - €29)
INSERT INTO api_plans (id, name, tokens_per_month, billing_cycle, price, currency, created_at, updated_at)
VALUES ('bot_starter', 'Chatbot Starter', 50000, 'monthly', 29.00, 'EUR', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = 'Chatbot Starter',
    tokens_per_month = 50000,
    billing_cycle = 'monthly',
    price = 29.00,
    currency = 'EUR',
    updated_at = NOW();

-- Plan Pro (150,000 tokens/mes - €79)
INSERT INTO api_plans (id, name, tokens_per_month, billing_cycle, price, currency, created_at, updated_at)
VALUES ('bot_pro', 'Chatbot Pro', 150000, 'monthly', 79.00, 'EUR', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = 'Chatbot Pro',
    tokens_per_month = 150000,
    billing_cycle = 'monthly',
    price = 79.00,
    currency = 'EUR',
    updated_at = NOW();

-- Plan Enterprise (500,000 tokens/mes - €199)
INSERT INTO api_plans (id, name, tokens_per_month, billing_cycle, price, currency, created_at, updated_at)
VALUES ('bot_enterprise', 'Chatbot Enterprise', 500000, 'monthly', 199.00, 'EUR', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = 'Chatbot Enterprise',
    tokens_per_month = 500000,
    billing_cycle = 'monthly',
    price = 199.00,
    currency = 'EUR',
    updated_at = NOW();

-- Verificar planes instalados
SELECT id, name, tokens_per_month, price, currency
FROM api_plans
WHERE id LIKE 'bot%'
ORDER BY tokens_per_month ASC;
