<?php
if (!defined('ABSPATH')) exit;

class WC_Gateway_NatWest_PayIt extends WC_Payment_Gateway {

    public function __construct() {

        $this->id                 = 'natwest_payit';
        $this->method_title       = 'NatWest PayIt';
        $this->method_description = 'Pay by bank transfer using NatWest PayIt (hosted journey).';
        $this->has_fields         = false;

        $this->supports = [
            'products'
        ];

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled     = $this->get_option('enabled');
        $this->title       = $this->get_option('title', 'Pay by Bank (NatWest PayIt)');
        $this->description = $this->get_option('description', '');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields() {

        $this->form_fields = [
            'enabled' => [
                'title'   => 'Enable/Disable',
                'type'    => 'checkbox',
                'label'   => 'Enable NatWest PayIt',
                'default' => 'no'
            ],
            'title' => [
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title the user sees during checkout.',
                'default'     => 'Pay by Bank (NatWest PayIt)',
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'Payment method description shown at checkout.',
                'default'     => 'Pay securely using NatWest PayIt.',
                'desc_tip'    => true,
            ],
        ];
    }

    public function payment_fields() {
        if (!empty($this->description)) {
            echo wpautop(wp_kses_post($this->description));
        }
    }

    public function process_payment($order_id) {

        $order = wc_get_order($order_id);

        if (!$order) {
            wc_add_notice('Unable to start PayIt payment (order not found).', 'error');
            return ['result' => 'fail'];
        }

        // Pull credentials/settings from your admin settings storage
        $options = NatWest_PayIt_Admin::get();

        $client_id     = $options['client_id']     ?? '';
        $client_secret = $options['client_secret'] ?? '';
        $company_id    = $options['company_id']    ?? '';
        $brand_id      = $options['brand_id']      ?? '';

        if (empty($client_id) || empty($client_secret) || empty($company_id) || empty($brand_id)) {
            wc_add_notice('NatWest PayIt is not configured correctly. Please contact the site administrator.', 'error');
            return ['result' => 'fail'];
        }

        try {

            require_once NATWEST_PAYIT_PLUGIN_PATH . 'includes/api/class-natwest-payit-api.php';
            require_once NATWEST_PAYIT_PLUGIN_PATH . 'includes/api/class-natwest-payit-payments.php';

            $payments = new NatWest_PayIt_Payments($client_id, $client_secret);
            $response = $payments->create_payment($order, $company_id, $brand_id);

            // ✅ NEW: PayIt returns the URL at Data.ResponseURL
            $redirect_url = $response['Data']['ResponseURL'] ?? '';

            if (empty($redirect_url)) {
                NatWest_PayIt_Logger::log(['create_payment_response' => $response], 'error');
                wc_add_notice('Unable to start PayIt payment.', 'error');
                return ['result' => 'fail'];
            }

            // Store PayIt reference for later status updates/webhooks
            $tpp_ref = $response['Data']['TPPReferenceID'] ?? '';
            if (!empty($tpp_ref)) {
                $order->update_meta_data('_natwest_payit_tpp_reference', $tpp_ref);
            }

            // Mark as pending payment until callback/webhook confirms payment
            $order->update_status('pending', 'Awaiting NatWest PayIt payment.');
            $order->save();

            // Return redirect response for WooCommerce checkout
            return [
                'result'   => 'success',
                'redirect' => $redirect_url
            ];

        } catch (Exception $e) {
            NatWest_PayIt_Logger::log('process_payment exception: ' . $e->getMessage(), 'error');
            wc_add_notice('Unable to start PayIt payment.', 'error');
            return ['result' => 'fail'];
        }
    }
}
