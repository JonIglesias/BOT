<?php
/**
 * BaseEndpoint - Clase base para todos los endpoints de GeoWriter
 *
 * Proporciona funcionalidad común:
 * - Validación de licencia
 * - Tracking de uso con precios reales
 * - Carga de prompts
 * - Reemplazo de variables
 *
 * @version 2.0
 */

defined('API_ACCESS') or die('Direct access not permitted');

require_once API_BASE_DIR . '/core/Response.php';
require_once API_BASE_DIR . '/models/License.php';
require_once API_BASE_DIR . '/models/UsageTracking.php';
require_once API_BASE_DIR . '/services/OpenAIService.php';

abstract class BaseEndpoint {

    protected $params;
    protected $license;
    protected $openai;

    /**
     * Constructor
     */
    public function __construct($params = []) {
        $this->params = $params;
        $this->openai = new OpenAIService();
    }

    /**
     * Método abstracto que cada endpoint debe implementar
     */
    abstract public function handle();

    /**
     * Validar licencia
     */
    protected function validateLicense() {
        $licenseKey = $this->params['license_key'] ?? null;
        $domain = $this->params['domain'] ?? null;

        if (!$licenseKey) {
            Response::error('license_key is required', 400);
        }

        if (!$domain) {
            Response::error('domain is required', 400);
        }

        try {
            // Buscar licencia
            $licenseModel = new License();
            $license = $licenseModel->findByKey($licenseKey);

            if (!$license) {
                Response::error('Invalid license key', 401);
            }

            // Verificar estado
            if ($license['status'] !== 'active') {
                Response::error('License is not active', 401);
            }

            // Verificar dominio
            $licenseDomain = rtrim(str_replace(['http://', 'https://', 'www.'], '', $license['domain']), '/');
            $requestDomain = rtrim(str_replace(['http://', 'https://', 'www.'], '', $domain), '/');

            if ($licenseDomain !== $requestDomain) {
                Response::error('Domain mismatch', 401);
            }

            // Verificar que es licencia GEO
            $planId = $license['plan_id'] ?? '';
            if (!preg_match('/^GEO/i', $planId)) {
                Response::error('This license is not for GeoWriter product', 401);
            }

            // Verificar tokens disponibles
            $tokensUsed = $license['tokens_used_this_period'] ?? 0;
            $tokensLimit = $license['tokens_limit'] ?? 0;

            if ($tokensLimit > 0 && $tokensUsed >= $tokensLimit) {
                Response::error('Token limit exceeded for this period', 402, [
                    'tokens_used' => $tokensUsed,
                    'tokens_limit' => $tokensLimit,
                    'period_ends_at' => $license['period_ends_at'] ?? null
                ]);
            }

            $this->license = $license;
            return $license;
        } catch (PDOException $e) {
            // Log database error
            if (class_exists('Logger')) {
                Logger::error('Database error in validateLicense', [
                    'error' => $e->getMessage(),
                    'license_key' => substr($licenseKey, 0, 8) . '...'
                ]);
            } else {
                error_log('Database error in validateLicense: ' . $e->getMessage());
            }
            Response::error('Database error occurred', 500);
        } catch (Exception $e) {
            // Log general error
            error_log('Error in validateLicense: ' . $e->getMessage());
            Response::error('An error occurred while validating license', 500);
        }
    }

    /**
     * Trackear uso de tokens con precios reales
     *
     * CRÍTICO: Debe guardar el modelo usado para calcular el precio correcto
     */
    protected function trackUsage($operationType, $openaiResult) {
        if (!$this->license) {
            return false;
        }

        try {
            // Obtener datos de uso de OpenAI
            $usage = $openaiResult['usage'] ?? [];
            $tokensInput = $usage['prompt_tokens'] ?? 0;
            $tokensOutput = $usage['completion_tokens'] ?? 0;
            $tokensTotal = $usage['total_tokens'] ?? ($tokensInput + $tokensOutput);

            // ⭐ CRÍTICO: Obtener el modelo REAL usado por OpenAI
            // OpenAI devuelve el modelo exacto usado en la respuesta
            $modelUsed = $openaiResult['model'] ?? OPENAI_MODEL;

            // Incrementar tokens en la licencia
            $licenseModel = new License();
            $licenseModel->incrementTokens($this->license['id'], $tokensTotal);

            // Registrar en usage_tracking con el modelo correcto
            // UsageTracking calculará el precio desde api_model_prices
            $trackingData = [
                'license_id' => $this->license['id'],
                'operation_type' => $operationType,
                'tokens_input' => $tokensInput,
                'tokens_output' => $tokensOutput,
                'tokens_total' => $tokensTotal,
                'model' => $modelUsed,  // ⭐ El modelo REAL usado
                'sync_status_at_time' => 'fresh'
            ];

            // Si hay campaign_id en params, incluirlo
            if (isset($this->params['campaign_id'])) {
                $trackingData['campaign_id'] = $this->params['campaign_id'];
            }

            if (isset($this->params['campaign_name'])) {
                $trackingData['campaign_name'] = $this->params['campaign_name'];
            }

            if (isset($this->params['batch_id'])) {
                $trackingData['batch_id'] = $this->params['batch_id'];
                $trackingData['batch_type'] = 'queue';
            }

            $usageTracking = new UsageTracking();
            return $usageTracking->track($trackingData);
        } catch (PDOException $e) {
            // Log database error but don't fail the request
            if (class_exists('Logger')) {
                Logger::error('Database error in trackUsage', [
                    'error' => $e->getMessage(),
                    'operation' => $operationType
                ]);
            } else {
                error_log('Database error in trackUsage: ' . $e->getMessage());
            }
            return false;
        } catch (Exception $e) {
            // Log general error but don't fail the request
            error_log('Error in trackUsage: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cargar prompt desde archivo .md
     */
    protected function loadPrompt($promptName) {
        $promptPath = API_BASE_DIR . '/prompts/' . $promptName . '.md';

        if (!file_exists($promptPath)) {
            error_log("Prompt file not found: {$promptPath}");
            return false;
        }

        return file_get_contents($promptPath);
    }

    /**
     * Reemplazar variables en template
     *
     * Formato: {{variable_name}}
     */
    protected function replaceVariables($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }

    /**
     * Añadir contexto de títulos previos (para evitar duplicados)
     * Usado en generar-titulo.php
     */
    protected function appendQueueContext($prompt, $campaignId, $limit = 15) {
        if (!$campaignId) {
            return $prompt;
        }

        require_once API_BASE_DIR . '/services/TitleQueueManager.php';
        $previousTitles = TitleQueueManager::getTitles($campaignId, $limit);

        if (empty($previousTitles)) {
            return $prompt;
        }

        $context = "\n\n---\nIMPORTANT: Avoid generating titles similar to these already generated:\n";
        $context .= implode("\n", array_map(function($title, $index) {
            return ($index + 1) . ". " . $title;
        }, $previousTitles, array_keys($previousTitles)));
        $context .= "\n---\n";

        return $prompt . $context;
    }
}
