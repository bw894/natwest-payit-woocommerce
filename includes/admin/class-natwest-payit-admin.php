<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Admin {

    const OPTION_KEY = 'natwest_payit_settings';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_menu() {
        add_submenu_page(
            'woocommerce',
            'NatWest PayIt',
            'NatWest PayIt',
            'manage_options',
            'natwest-payit',
            [__CLASS__, 'render_page']
        );
    }

    public static function register_settings() {
        register_setting(self::OPTION_KEY, self::OPTION_KEY);
    }

    public static function render_page() {
        require __DIR__ . '/views/settings.php';
    }

    public static function get($key = null) {
        $options = get_option(self::OPTION_KEY, []);
        return $key ? ($options[$key] ?? null) : $options;
    }
}
