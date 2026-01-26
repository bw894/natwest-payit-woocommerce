<?php
/**
 * Plugin Name: NatWest PayIt for WooCommerce
 * Description: NatWest PayIt (Pay by Bank) Hosted Journey integration for WooCommerce
 * Version: 1.0.2
 * Author: Ben Wiser
 */

if (!defined('ABSPATH')) exit;

define('NATWEST_PAYIT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('NATWEST_PAYIT_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', function () {
    require_once NATWEST_PAYIT_PLUGIN_PATH . 'includes/class-natwest-payit-loader.php';
    NatWest_PayIt_Loader::init();
});
