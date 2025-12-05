<?php
/**
 * Endpoint: Validar Licencia del Chatbot
 *
 * Verifica la validez de una licencia y dominio sin consumir tokens
 * Útil para verificación inicial al cargar el plugin
 *
 * @version 1.0
 */

defined('API_ACCESS') or die('Direct access not permitted');

require_once API_BASE_DIR . '/core/Response.php';
require_once API_BASE_DIR . '/bot/services/BotLicenseValidator.php';

class BotValidateEndpoint {

    public function handle() {
        // Obtener parámetros (soporta GET y POST)
        $input = Response::getJsonInput();

        $licenseKey = $input['license_key'] ?? $_GET['license_key'] ?? null;
        $domain = $input['domain'] ?? $_GET['domain'] ?? null;

        if (!$licenseKey) {
            Response::error('license_key is required', 400);
        }

        if (!$domain) {
            Response::error('domain is required', 400);
        }

        // Validar licencia
        $validator = new BotLicenseValidator();
        $validation = $validator->validate($licenseKey, $domain);

        if (!$validation['valid']) {
            Response::error($validation['reason'] ?? 'Invalid license', 401, [
                'valid' => false,
                'reason' => $validation['reason'] ?? 'Invalid license'
            ]);
        }

        $license = $validation['license'];

        // Calcular tokens disponibles
        $tokensUsed = $license['tokens_used_this_period'] ?? 0;
        $tokensLimit = $license['tokens_limit'] ?? 0;
        $tokensAvailable = max(0, $tokensLimit - $tokensUsed);

        // Respuesta exitosa
        Response::success([
            'valid' => true,
            'license' => [
                'key' => $license['license_key'],
                'status' => $license['status'],
                'plan_id' => $license['plan_id'],
                'plan_name' => $license['plan_name'] ?? 'Unknown Plan',
                'domain' => $license['domain'],
                'expires_at' => $license['period_ends_at'],
                'tokens_available' => $tokensAvailable,
                'tokens_limit' => $tokensLimit,
                'tokens_used' => $tokensUsed
            ]
        ]);
    }
}
