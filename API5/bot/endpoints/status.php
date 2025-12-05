<?php
/**
 * Endpoint: Estado de Licencia del Chatbot
 *
 * Obtiene información detallada del estado de una licencia
 * Incluye uso de tokens, días restantes, porcentaje de consumo, etc.
 *
 * @version 1.0
 */

defined('API_ACCESS') or die('Direct access not permitted');

require_once API_BASE_DIR . '/core/Response.php';
require_once API_BASE_DIR . '/models/License.php';
require_once API_BASE_DIR . '/bot/services/BotLicenseValidator.php';

class BotStatusEndpoint {

    public function handle() {
        // Obtener parámetros (soporta GET y POST)
        $input = Response::getJsonInput();

        $licenseKey = $input['license_key'] ?? $_GET['license_key'] ?? null;

        if (!$licenseKey) {
            Response::error('license_key is required', 400);
        }

        // Validar formato de licencia (debe empezar con BOT-)
        $validator = new BotLicenseValidator();
        $validation = $validator->validateLicenseOnly($licenseKey);

        if (!$validation['valid']) {
            Response::error($validation['reason'] ?? 'Invalid license', 401, [
                'valid' => false,
                'reason' => $validation['reason'] ?? 'Invalid license'
            ]);
        }

        $license = $validation['license'];

        // Calcular métricas
        $tokensUsed = $license['tokens_used_this_period'] ?? 0;
        $tokensLimit = $license['tokens_limit'] ?? 0;
        $tokensRemaining = max(0, $tokensLimit - $tokensUsed);
        $usagePercentage = $tokensLimit > 0 ? round(($tokensUsed / $tokensLimit) * 100, 2) : 0;

        // Calcular días restantes
        $daysRemaining = 0;
        if ($license['period_ends_at']) {
            $endTime = strtotime($license['period_ends_at']);
            $now = time();
            $daysRemaining = max(0, ceil(($endTime - $now) / 86400));
        }

        // Respuesta exitosa
        Response::success([
            'license' => [
                'key' => $license['license_key'],
                'status' => $license['status'],
                'plan_id' => $license['plan_id'],
                'plan_name' => $license['plan_name'] ?? 'Unknown Plan',
                'domain' => $license['domain'] ?? null,
                'tokens_limit' => $tokensLimit,
                'tokens_used' => $tokensUsed,
                'tokens_remaining' => $tokensRemaining,
                'usage_percentage' => $usagePercentage,
                'period_starts_at' => $license['period_starts_at'],
                'period_ends_at' => $license['period_ends_at'],
                'days_remaining' => $daysRemaining,
                'expires_at' => $license['period_ends_at']
            ]
        ]);
    }
}
