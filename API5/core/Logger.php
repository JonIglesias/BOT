<?php
/**
 * Simple Logger for API5
 * Logs API activity to file
 */

defined('API_ACCESS') or die('Direct access not permitted');

class Logger {

    private static $logFile = null;

    /**
     * Initialize logger
     */
    private static function init() {
        if (self::$logFile === null) {
            $logDir = API_BASE_DIR . '/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            self::$logFile = $logDir . '/api.log';
        }
    }

    /**
     * Log an API event
     */
    public static function api($level, $message, $context = []) {
        self::init();

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";

        @file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Log an error
     */
    public static function error($message, $context = []) {
        self::api('ERROR', $message, $context);
    }

    /**
     * Log info
     */
    public static function info($message, $context = []) {
        self::api('INFO', $message, $context);
    }

    /**
     * Log warning
     */
    public static function warning($message, $context = []) {
        self::api('WARNING', $message, $context);
    }
}
