<?php
/**
 * Endpoint: List OpenAI Models
 *
 * Lista modelos de OpenAI disponibles (usando la API key del servidor)
 * NO expone la API key al cliente
 *
 * @version 1.0
 */

defined('API_ACCESS') or die('Direct access not permitted');

require_once API_BASE_DIR . '/core/Response.php';
require_once API_BASE_DIR . '/bot/services/BotLicenseValidator.php';

class BotListModelsEndpoint {

    public function handle() {
        // Obtener parámetros
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

        // Obtener modelos desde OpenAI usando la API key del servidor
        $models = $this->fetchModelsFromOpenAI();

        Response::success([
            'models' => $models,
            'cached' => false
        ]);
    }

    /**
     * Obtener modelos desde OpenAI
     */
    private function fetchModelsFromOpenAI() {
        // Usar la API key del servidor (no la del cliente)
        $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : getenv('OPENAI_API_KEY');

        if (!$apiKey) {
            // Si no hay API key, retornar lista hardcoded
            return $this->getFallbackModels();
        }

        $ch = curl_init('https://api.openai.com/v1/models');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return $this->getFallbackModels();
        }

        $data = json_decode($response, true);
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $this->getFallbackModels();
        }

        // Filtrar solo modelos de chat relevantes
        $models = [];
        foreach ($data['data'] as $model) {
            $id = $model['id'] ?? '';

            // Solo GPT-4 y GPT-5
            if (!preg_match('/^gpt-(4|5)/i', $id)) {
                continue;
            }

            // Excluir modelos no relevantes
            if (preg_match('/(embed|embedding|whisper|tts|audio|realtime|vision-only|legacy|deprecated|ft:|fine|batch|vector)/i', $id)) {
                continue;
            }

            $models[] = [
                'id' => $id,
                'name' => $id
            ];
        }

        // Ordenar por relevancia
        usort($models, function($a, $b) {
            $order = ['gpt-5', 'gpt-4.1', 'gpt-4o', 'gpt-4o-mini', 'gpt-4'];
            $aPos = $bPos = 999;

            foreach ($order as $i => $prefix) {
                if (stripos($a['id'], $prefix) !== false) $aPos = $i;
                if (stripos($b['id'], $prefix) !== false) $bPos = $i;
            }

            return $aPos === $bPos ? strcmp($a['id'], $b['id']) : $aPos - $bPos;
        });

        return array_slice($models, 0, 20); // Limitar a 20 modelos
    }

    /**
     * Lista de modelos fallback si no se puede conectar a OpenAI
     */
    private function getFallbackModels() {
        return [
            ['id' => 'gpt-4o', 'name' => 'gpt-4o'],
            ['id' => 'gpt-4o-mini', 'name' => 'gpt-4o-mini'],
            ['id' => 'gpt-4-turbo', 'name' => 'gpt-4-turbo'],
            ['id' => 'gpt-4', 'name' => 'gpt-4']
        ];
    }
}
