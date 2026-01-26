<?php
if (!defined('ABSPATH')) exit;

class WC_Gateway_NatWest_PayIt extends WC_Payment_Gateway {

    public function __construct() {

        $this->id = 'natwest_payit';
        $this->method_title = 'NatWest PayIt';
        $this->method_description = 'Pay by Bank using NatWest PayIt';
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled     = $this->get_option('enabled');
        $this->title       = $this->get_option('title', 'Pay by Bank (NatWest PayIt)');
        $this->description = $this->get_option('description', '');

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']
        );
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Enable',
                'type' => 'checkbox',
                'default' => 'no'
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'default' => 'Pay by Bank (NatWest PayIt)'
            ],
            'description' => [
                'title' => 'Description',
                'type'  => 'textarea',
                'default' => 'Pay securely by bank transfer using NatWest PayIt.'
            ],
        ];
    }

    public function process_payment($order_id) {

        $order = wc_get_order($order_id);
        $settings = NatWest_PayIt_Admin::get();
        $api = new NatWest_PayIt_Payments(
            $settings['client_id'],
            $settings['client_secret']
        );
        $response = $api->create_payment(
            $order,
            $settings['company_id'],
            $settings['brand_id']
        );

        if (empty($response['redirectUrl'])) {
            wc_add_notice('Unable to start PayIt payment.', 'error');
            return;
        }

        $order->update_meta_data('_payit_tpp_reference', $response['tppReference']);
        $order->update_status('pending', 'Awaiting NatWest PayIt payment');
        $order->save();

        return [
            'result' => 'success',
            'redirect' => $response['redirectUrl']
        ];
    }
}

add_action('init', function () {

    if (strpos($_SERVER['REQUEST_URI'], '/payit/return/') === false) {
        return;
    }

    wc_add_notice(
        'Your payment is being confirmed. Please wait…',
        'notice'
    );

    wp_safe_redirect(wc_get_checkout_url());
    exit;
});

