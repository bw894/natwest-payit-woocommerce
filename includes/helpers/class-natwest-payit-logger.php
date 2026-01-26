<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Logger {

    $options = NatWest_PayIt_Admin::get();

    if (empty($options['logging'])) {
        return;
}

    public static function log($message, $level = 'info') {
        if (!class_exists('WC_Logger')) return;

        wc_get_logger()->log(
            $level,
            is_string($message) ? $message : print_r($message, true),
            ['source' => 'natwest-payit']
        );
    }
}
