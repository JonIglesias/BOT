<?php
/**
 * Chatbot API - Configuración específica
 *
 * @version 1.0
 */

// Prevenir acceso directo
defined('API_ACCESS') or die('Direct access not permitted');

// ============================================================================
// CONFIGURACIÓN DEL CHATBOT
// ============================================================================

// Prefijo para licencias del chatbot
define('BOT_LICENSE_PREFIX', 'BOT');

// Modelo por defecto para el chatbot
define('BOT_DEFAULT_MODEL', 'gpt-4o');

// Límite de tokens por defecto para respuestas
define('BOT_MAX_TOKENS', 1000);

// Temperatura por defecto (creatividad)
define('BOT_TEMPERATURE', 0.7);

// Máximo de mensajes de historial a incluir en el contexto
define('BOT_MAX_HISTORY_MESSAGES', 10);

// Timeout para llamadas a OpenAI (segundos)
define('BOT_OPENAI_TIMEOUT', 30);

// ============================================================================
// LÍMITES Y RESTRICCIONES
// ============================================================================

// Longitud máxima de mensaje del usuario (caracteres)
define('BOT_MAX_MESSAGE_LENGTH', 2000);

// Tokens mínimos requeridos para procesar una petición
define('BOT_MIN_TOKENS_REQUIRED', 100);

// ============================================================================
// TRACKING Y ANALYTICS
// ============================================================================

// Tipo de operación para registros de uso
define('BOT_OPERATION_TYPE', 'bot_chat');

// Endpoint identificador para logs
define('BOT_ENDPOINT_NAME', '/api/bot/v1/chat');
