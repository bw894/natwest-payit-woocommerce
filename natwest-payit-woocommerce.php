<?php
/**
 * Plugin Name: NatWest PayIt for WooCommerce
 * Description: NatWest PayIt (Pay by Bank) Hosted Journey integration for WooCommerce
 * Version: 1.0.9
 * Author: Ben Wiser
 */

if (!defined('ABSPATH')) exit;

define('NATWEST_PAYIT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('NATWEST_PAYIT_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', function () {
    require_once NATWEST_PAYIT_PLUGIN_PATH . 'includes/class-natwest-payit-loader.php';
    NatWest_PayIt_Loader::init();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_url = admin_url('admin.php?page=natwest-payit');
    array_unshift($links, '<a href="' . esc_url($settings_url) . '">Settings</a>');
    return $links;
});
