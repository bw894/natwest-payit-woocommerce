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

        // ✅ NEW: Handle PayIt return route safely (avoids /checkout/?s=... collisions)
        add_action('woocommerce_api_natwest_payit_return', [$this, 'handle_payit_return']);
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

            // ✅ PayIt returns the URL at Data.ResponseURL
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

    /**
     * ✅ NEW: PayIt return handler (called via redirectUrl we now send in the API payload)
     *
     * This prevents WordPress treating PayIt `s=` query param as search and breaking routing.
     * We do a best-effort status confirm, but we won't hard-fail if NatWest status fields differ.
     */
    public function handle_payit_return() {

        $order_id  = absint($_GET['order_id'] ?? 0);
        $order_key = sanitize_text_field($_GET['key'] ?? '');

        if (empty($order_id) || empty($order_key)) {
            wp_die('Invalid PayIt return.');
        }

        $order = wc_get_order($order_id);

        if (!$order || $order->get_order_key() !== $order_key) {
            wp_die('Order validation failed.');
        }

        // If already paid via webhook, just continue.
        if ($order->is_paid()) {
            wp_safe_redirect($order->get_checkout_order_received_url());
            exit;
        }

        // If PayIt returns a status flag suggesting rejection, mark failed (best-effort)
        $status_param = sanitize_text_field($_GET['s'] ?? '');
        if (!empty($status_param)) {
            $decoded = base64_decode($status_param, true);
            $status_clean = $decoded !== false ? $decoded : $status_param;

            if (stripos($status_clean, 'rejected') !== false || stripos($status_clean, 'failed') !== false || stripos($status_clean, 'cancel') !== false) {
                $order->update_status('failed', 'NatWest PayIt return indicated payment was not completed.');
                wp_safe_redirect(wc_get_checkout_url());
                exit;
            }
        }

        $tpp_ref = (string) $order->get_meta('_natwest_payit_tpp_reference');

        if (empty($tpp_ref)) {
            $order->add_order_note('PayIt return received but no TPPReferenceID was stored on the order.');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        $options = NatWest_PayIt_Admin::get();

        $client_id     = $options['client_id']     ?? '';
        $client_secret = $options['client_secret'] ?? '';
        $company_id    = $options['company_id']    ?? '';
        $brand_id      = $options['brand_id']      ?? '';

        if (empty($client_id) || empty($client_secret) || empty($company_id) || empty($brand_id)) {
            $order->add_order_note('PayIt return received but gateway credentials were missing; unable to confirm status.');
            wp_safe_redirect($order->get_checkout_order_received_url());
            exit;
        }

        try {
            require_once NATWEST_PAYIT_PLUGIN_PATH . 'includes/api/class-natwest-payit-api.php';
            require_once NATWEST_PAYIT_PLUGIN_PATH . 'includes/api/class-natwest-payit-status.php';

            $status_api = new NatWest_PayIt_Status($client_id, $client_secret);
            $status_res = $status_api->get_status($company_id, $brand_id, $tpp_ref);

            // Try a few likely fields for the payment status.
            $status = '';
            if (is_array($status_res)) {
                $status = $status_res['Data']['status'] ?? $status_res['Data']['Status'] ?? $status_res['status'] ?? $status_res['Status'] ?? '';
            }

            $status_norm = strtoupper((string) $status);

            if (in_array($status_norm, ['COMPLETED', 'PAID', 'SUCCESS', 'SUCCESSFUL', 'SETTLED'], true)) {
                $order->payment_complete();
                $order->add_order_note('Payment confirmed via PayIt return status check.');
            } else {
                $order->add_order_note('PayIt return received; payment not confirmed yet (status: ' . ($status ?: 'unknown') . ').');
            }

        } catch (Exception $e) {
            $order->add_order_note('PayIt return status check failed: ' . $e->getMessage());
        }

        wp_safe_redirect($order->get_checkout_order_received_url());
        exit;
    }
}
