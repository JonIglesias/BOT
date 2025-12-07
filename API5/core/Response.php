<?php
/**
 * Response Helper for API5
 * Handles JSON responses and input parsing
 */

defined('API_ACCESS') or die('Direct access not permitted');

class Response {

    /**
     * Send a successful JSON response
     */
    public static function success($data = [], $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send an error JSON response
     */
    public static function error($message, $statusCode = 400, $additionalData = []) {
        http_response_code($statusCode);

        // Convert string to array if needed (for backward compatibility)
        if (is_string($additionalData)) {
            $additionalData = ['error_code' => $additionalData];
        }

        echo json_encode([
            'success' => false,
            'error' => array_merge([
                'message' => $message,
                'code' => self::getErrorCode($statusCode)
            ], is_array($additionalData) ? $additionalData : [])
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Get JSON input from request body
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');

        if (empty($input)) {
            return [];
        }

        $decoded = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $decoded;
    }

    /**
     * Get error code string from HTTP status code
     */
    private static function getErrorCode($statusCode) {
        $codes = [
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            402 => 'PAYMENT_REQUIRED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            429 => 'TOO_MANY_REQUESTS',
            500 => 'INTERNAL_SERVER_ERROR',
            503 => 'SERVICE_UNAVAILABLE'
        ];

        return $codes[$statusCode] ?? 'UNKNOWN_ERROR';
    }
}
