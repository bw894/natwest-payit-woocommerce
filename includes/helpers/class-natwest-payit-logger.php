<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Logger {

    public static function log($message, $level = 'info') {

        // WooCommerce logger not available
        if (!function_exists('wc_get_logger')) {
            return;
        }

        // Optional: only log if enabled in settings
        if (class_exists('NatWest_PayIt_Admin')) {
            $options = NatWest_PayIt_Admin::get();
            if (!empty($options) && isset($options['logging']) && empty($options['logging'])) {
                return;
            }
        }

        wc_get_logger()->log(
            $level,
            is_string($message) ? $message : print_r($message, true),
            ['source' => 'natwest-payit']
        );
    }
}
