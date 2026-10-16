<?php
/**
 * Development and Audit Logger for Project Core
 *
 * @package ProjectCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class Project_Core_Logger {
    /**
     * Log message to debug log if WP_DEBUG is enabled
     *
     * @param string $message
     * @param string $level 'info' | 'warning' | 'error' | 'webhook'
     * @param array $context
     */
    public static function log($message, $level = 'info', $context = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $formatted = sprintf(
            '[ProjectCore][%s][%s] %s %s',
            gmdate('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ''
        );

        error_log($formatted);
    }

    public static function info($message, $context = array()) {
        self::log($message, 'info', $context);
    }

    public static function warn($message, $context = array()) {
        self::log($message, 'warning', $context);
    }

    public static function error($message, $context = array()) {
        self::log($message, 'error', $context);
    }

    public static function webhook($message, $context = array()) {
        self::log($message, 'webhook', $context);
    }
}
