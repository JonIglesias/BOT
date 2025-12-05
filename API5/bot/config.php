<?php
/**
 * Chatbot API - Configuración específica
 *
 * @version 2.0 - Ahora lee configuración desde archivo JSON
 */

// Prevenir acceso directo
defined('API_ACCESS') or die('Direct access not permitted');

// ============================================================================
// CONFIGURACIÓN DEL CHATBOT (desde archivo JSON)
// ============================================================================

// Función para cargar settings desde archivo JSON
function bot_load_settings() {
    $settingsFile = __DIR__ . '/bot-settings.json';
    $defaults = [
        'model' => 'gpt-4o',
        'temperature' => 0.7,
        'max_tokens' => 1000,
        'tone' => 'profesional',
        'max_history' => 10
    ];

    if (!file_exists($settingsFile)) {
        return $defaults;
    }

    $json = @file_get_contents($settingsFile);
    if ($json === false) {
        return $defaults;
    }

    $settings = @json_decode($json, true);
    if (!is_array($settings)) {
        return $defaults;
    }

    // Merge con defaults para campos faltantes
    return array_merge($defaults, $settings);
}

// Cargar settings
$BOT_SETTINGS = bot_load_settings();

// Definir constantes desde settings
define('BOT_LICENSE_PREFIX', 'BOT');
define('BOT_DEFAULT_MODEL', $BOT_SETTINGS['model']);
define('BOT_MAX_TOKENS', $BOT_SETTINGS['max_tokens']);
define('BOT_TEMPERATURE', $BOT_SETTINGS['temperature']);
define('BOT_MAX_HISTORY_MESSAGES', $BOT_SETTINGS['max_history']);
define('BOT_TONE', $BOT_SETTINGS['tone']);

// Timeout para llamadas a OpenAI (segundos) - fijo
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
