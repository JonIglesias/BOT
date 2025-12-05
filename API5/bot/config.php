<?php
/**
 * Chatbot API - Configuración específica
 *
 * @version 3.0 - Ahora lee configuración desde base de datos
 */

// Prevenir acceso directo
defined('API_ACCESS') or die('Direct access not permitted');

// ============================================================================
// CONFIGURACIÓN DEL CHATBOT (desde base de datos)
// ============================================================================

// Función para cargar settings desde base de datos
function bot_load_settings() {
    $defaults = [
        'model' => 'gpt-4o',
        'temperature' => 0.7,
        'max_tokens' => 1000,
        'tone' => 'profesional',
        'max_history' => 10
    ];

    try {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance();

        $stmt = $db->prepare("SELECT setting_key, setting_value FROM " . DB_PREFIX . "settings WHERE setting_key IN (
            'bot_ai_model', 'bot_ai_temperature', 'bot_ai_max_tokens', 'bot_ai_tone', 'bot_ai_max_history'
        )");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($results as $row) {
            // Convertir key de BD a formato interno
            $key = str_replace('bot_ai_', '', $row['setting_key']);
            $settings[$key] = $row['setting_value'];
        }

        // Convertir tipos de datos
        if (isset($settings['temperature'])) $settings['temperature'] = floatval($settings['temperature']);
        if (isset($settings['max_tokens'])) $settings['max_tokens'] = intval($settings['max_tokens']);
        if (isset($settings['max_history'])) $settings['max_history'] = intval($settings['max_history']);

        // Merge con defaults
        return array_merge($defaults, $settings);

    } catch (Exception $e) {
        // Si falla la BD, usar defaults
        return $defaults;
    }
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
