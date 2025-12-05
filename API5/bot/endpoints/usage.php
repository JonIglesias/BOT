<?php
/**
 * Endpoint: Estadísticas de Uso del Chatbot
 *
 * Obtiene estadísticas detalladas de uso para una licencia
 * Incluye resumen de conversaciones, tokens usados, y datos diarios
 *
 * @version 1.0
 */

defined('API_ACCESS') or die('Direct access not permitted');

require_once API_BASE_DIR . '/core/Response.php';
require_once API_BASE_DIR . '/models/License.php';
require_once API_BASE_DIR . '/bot/services/BotLicenseValidator.php';
require_once API_BASE_DIR . '/bot/services/BotTokenManager.php';

class BotUsageEndpoint {

    public function handle() {
        // Obtener parámetros (soporta GET y POST)
        $input = Response::getJsonInput();

        $licenseKey = $input['license_key'] ?? $_GET['license_key'] ?? null;
        $days = $input['days'] ?? $_GET['days'] ?? 30;

        if (!$licenseKey) {
            Response::error('license_key is required', 400);
        }

        // Validar días (máximo 365)
        $days = max(1, min(365, (int)$days));

        // Validar licencia
        $validator = new BotLicenseValidator();
        $validation = $validator->validateLicenseOnly($licenseKey);

        if (!$validation['valid']) {
            Response::error($validation['reason'] ?? 'Invalid license', 401, [
                'valid' => false,
                'reason' => $validation['reason'] ?? 'Invalid license'
            ]);
        }

        $license = $validation['license'];

        // Obtener estadísticas de uso
        $tokenManager = new BotTokenManager();
        $stats = $tokenManager->getUsageStats($license['id'], $days);

        // Respuesta exitosa
        Response::success([
            'period' => $stats['period'],
            'summary' => $stats['summary'],
            'daily' => $stats['daily']
        ]);
    }
}
