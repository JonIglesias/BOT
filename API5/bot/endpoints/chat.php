<?php
/**
 * Endpoint: Chat del Chatbot
 *
 * Procesa mensajes del usuario y genera respuestas con IA
 * Valida licencia, dominio, y tokens disponibles
 *
 * @version 1.0
 */

defined('API_ACCESS') or die('Direct access not permitted');

require_once API_BASE_DIR . '/core/Response.php';
require_once API_BASE_DIR . '/bot/services/BotLicenseValidator.php';
require_once API_BASE_DIR . '/bot/services/BotTokenManager.php';
require_once API_BASE_DIR . '/bot/services/BotOpenAIProxy.php';

class BotChatEndpoint {

    public function handle() {
        // Obtener input JSON
        $input = Response::getJsonInput();

        // Validar parámetros requeridos
        $licenseKey = $input['license_key'] ?? null;
        $domain = $input['domain'] ?? null;
        $message = $input['message'] ?? null;

        if (!$licenseKey) {
            Response::error('license_key is required', 400);
        }

        if (!$domain) {
            Response::error('domain is required', 400);
        }

        if (!$message || trim($message) === '') {
            Response::error('message is required and cannot be empty', 400);
        }

        // Validar longitud del mensaje
        if (strlen($message) > BOT_MAX_MESSAGE_LENGTH) {
            Response::error('Message exceeds maximum length of ' . BOT_MAX_MESSAGE_LENGTH . ' characters', 400);
        }

        // Validar licencia
        $validator = new BotLicenseValidator();
        $validation = $validator->validate($licenseKey, $domain);

        if (!$validation['valid']) {
            $errorData = [
                'code' => $this->getErrorCode($validation),
                'message' => $validation['reason'] ?? 'Invalid license'
            ];

            // Si es error de tokens, incluir información adicional
            if (isset($validation['tokens_used']) && isset($validation['tokens_limit'])) {
                $errorData['tokens_used'] = $validation['tokens_used'];
                $errorData['tokens_limit'] = $validation['tokens_limit'];
                $errorData['period_ends_at'] = $validation['period_ends_at'] ?? null;
                $errorData['upgrade_url'] = 'https://bocetosmarketing.com/upgrade';
            }

            Response::error($validation['reason'] ?? 'Invalid license', 401, $errorData);
        }

        $license = $validation['license'];

        // Verificar tokens mínimos disponibles
        $tokenManager = new BotTokenManager();
        if (!$tokenManager->hasTokensAvailable($license, BOT_MIN_TOKENS_REQUIRED)) {
            Response::error(
                'Insufficient tokens available. Minimum required: ' . BOT_MIN_TOKENS_REQUIRED,
                402,
                [
                    'code' => 'TOKEN_LIMIT_EXCEEDED',
                    'tokens_available' => $tokenManager->getAvailableTokens($license),
                    'tokens_limit' => $license['tokens_limit'],
                    'period_ends_at' => $license['period_ends_at'],
                    'upgrade_url' => 'https://bocetosmarketing.com/upgrade'
                ]
            );
        }

        // Obtener parámetros opcionales
        $conversationId = $input['conversation_id'] ?? null;
        $context = $input['context'] ?? [];
        $settings = $input['settings'] ?? [];

        // Generar respuesta con OpenAI
        $openAIProxy = new BotOpenAIProxy();
        $result = $openAIProxy->generateResponse([
            'message' => $message,
            'context' => $context,
            'settings' => $settings
        ]);

        if (!$result['success']) {
            Response::error(
                $result['error'] ?? 'Failed to generate response',
                500,
                [
                    'code' => $result['code'] ?? 'OPENAI_ERROR',
                    'message' => $result['error'] ?? 'Unknown error'
                ]
            );
        }

        // Obtener uso de tokens
        $usage = $result['usage'];
        $tokensInput = $usage['prompt_tokens'] ?? 0;
        $tokensOutput = $usage['completion_tokens'] ?? 0;
        $tokensTotal = $usage['total_tokens'] ?? 0;

        // Trackear uso de tokens
        $model = $result['model'] ?? BOT_DEFAULT_MODEL;
        $tokenManager->trackUsage(
            $license['id'],
            $tokensInput,
            $tokensOutput,
            $model,
            $conversationId,
            [
                'page_url' => $context['page_url'] ?? null,
                'page_title' => $context['page_title'] ?? null
            ]
        );

        // Calcular tokens restantes
        $tokensUsedAfter = $license['tokens_used_this_period'] + $tokensTotal;
        $tokensRemaining = max(0, $license['tokens_limit'] - $tokensUsedAfter);

        // Respuesta exitosa
        Response::success([
            'response' => $result['response'],
            'conversation_id' => $conversationId,
            'usage' => [
                'prompt_tokens' => $tokensInput,
                'completion_tokens' => $tokensOutput,
                'total_tokens' => $tokensTotal,
                'tokens_remaining' => $tokensRemaining
            ],
            'license' => [
                'tokens_used' => $tokensUsedAfter,
                'tokens_limit' => $license['tokens_limit'],
                'tokens_remaining' => $tokensRemaining,
                'period_ends_at' => $license['period_ends_at']
            ],
            'model' => $model
        ]);
    }

    /**
     * Obtener código de error basado en la validación
     */
    private function getErrorCode($validation) {
        $reason = $validation['reason'] ?? '';

        if (stripos($reason, 'token limit') !== false) {
            return 'TOKEN_LIMIT_EXCEEDED';
        }
        if (stripos($reason, 'expired') !== false) {
            return 'LICENSE_EXPIRED';
        }
        if (stripos($reason, 'domain') !== false) {
            return 'DOMAIN_MISMATCH';
        }
        if (stripos($reason, 'not found') !== false) {
            return 'LICENSE_NOT_FOUND';
        }
        if (stripos($reason, 'suspended') !== false || stripos($reason, 'cancelled') !== false) {
            return 'LICENSE_INACTIVE';
        }

        return 'INVALID_LICENSE';
    }
}
