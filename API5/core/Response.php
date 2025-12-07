<?php
/**
 * Response Class
 * 
 * @version 4.0
 */

defined('API_ACCESS') or die('Direct access not permitted');

class Response {
    
    /**
     * Enviar respuesta exitosa
     */
    public static function success($data = null, $message = null, $code = 200) {
        // Log successful response
        Logger::info('Response success', [
            'code' => $code,
            'has_data' => $data !== null,
            'has_message' => $message !== null,
            'data_keys' => is_array($data) ? array_keys($data) : 'not-array'
        ]);

        http_response_code($code);
        header('Content-Type: application/json');

        $response = [
            'success' => true
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Enviar respuesta de error
     */
    public static function error($message, $code = 400, $details = null) {
        // Log error response
        Logger::error('Response error', [
            'code' => $code,
            'message' => $message,
            'has_details' => $details !== null
        ]);

        http_response_code($code);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => $message
        ];

        if ($details !== null) {
            $response['details'] = $details;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Validar campos requeridos en request
     */
    public static function validateRequired($data, $requiredFields) {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            self::error(
                'Missing required fields',
                400,
                ['missing_fields' => $missing]
            );
        }
    }
    
    /**
     * Obtener datos del request (POST JSON)
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return [];
        }
        
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::error('Invalid JSON: ' . json_last_error_msg(), 400);
        }
        
        return $data ?? [];
    }
    
    /**
     * Obtener parámetros GET
     */
    public static function getQueryParams() {
        return $_GET;
    }
}
