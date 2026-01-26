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
            'client_id' => [
                'title' => 'Client ID',
                'type' => 'text'
            ],
            'client_secret' => [
                'title' => 'Client Secret',
                'type' => 'password'
            ],
            'company_id' => [
                'title' => 'Company ID',
                'type' => 'text'
            ],
            'brand_id' => [
                'title' => 'Brand ID',
                'type' => 'text'
            ],
        ];
    }

    public function process_payment($order_id) {

        $order = wc_get_order($order_id);

        $api = new NatWest_PayIt_Payments(
            $this->get_option('client_id'),
            $this->get_option('client_secret')
        );

        $response = $api->create_payment(
            $order,
            $this->get_option('company_id'),
            $this->get_option('brand_id')
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

