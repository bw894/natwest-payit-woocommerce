<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {

    register_rest_route('natwest-payit/v1', '/merchant-payments-confirm', [
        'methods'  => 'POST',
        'callback' => 'natwest_payit_merchant_confirm',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('natwest-payit/v1', '/refund-confirm', [
        'methods'  => 'POST',
        'callback' => 'natwest_payit_refund_confirm',
        'permission_callback' => '__return_true'
    ]);
});

function natwest_payit_merchant_confirm($request) {

    $payload = $request->get_json_params();
    if (!is_array($payload)) {
        return new WP_REST_Response([], 400);
    }

    $reference = $payload['reference']
        ?? $payload['TPPReferenceID']
        ?? null;

    if (!$reference) {
        return new WP_REST_Response([], 400);
    }

    $orders = wc_get_orders([
        'limit'      => 1,
        'meta_key'   => '_natwest_payit_tpp_reference',
        'meta_value' => (string) $reference
    ]);

    if (!$orders) {
        return new WP_REST_Response([], 404);
    }

    $order = $orders[0];
    $status = strtoupper((string) ($payload['status'] ?? ''));

    if (in_array($status, ['PAID', 'COMPLETED', 'SETTLED'], true)) {
        $order->payment_complete();
    } elseif (in_array($status, ['FAILED', 'REJECTED'], true)) {
        $order->update_status('failed', 'NatWest PayIt webhook failure.');
    }

    return new WP_REST_Response(['ok' => true], 200);
}

function natwest_payit_refund_confirm($request) {
    return new WP_REST_Response(['ok' => true], 200);
}
