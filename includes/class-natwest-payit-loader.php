<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Loader {

    public static function init() {

        // Admin/settings should always load (so you can configure even if WC isn't fully ready yet)
        require_once __DIR__ . '/admin/class-natwest-payit-admin.php';
        NatWest_PayIt_Admin::init();

        // Stop here if WooCommerce isn't available
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        require_once __DIR__ . '/class-natwest-payit-constants.php';

        require_once __DIR__ . '/helpers/class-natwest-payit-logger.php';
        require_once __DIR__ . '/helpers/class-natwest-payit-utils.php';

        require_once __DIR__ . '/auth/class-natwest-payit-auth.php';

        require_once __DIR__ . '/api/class-natwest-payit-api.php';
        require_once __DIR__ . '/api/class-natwest-payit-payments.php';
        require_once __DIR__ . '/api/class-natwest-payit-status.php';

        require_once __DIR__ . '/webhooks/class-natwest-payit-webhook.php';
        require_once __DIR__ . '/gateways/class-wc-gateway-natwest-payit.php';

        add_filter('woocommerce_payment_gateways', function ($methods) {
            $methods[] = 'WC_Gateway_NatWest_PayIt';
            return $methods;
        });
    }
}
