<?php
if (!defined('ABSPATH')) exit;

add_action('woocommerce_api_payit_webhook', function () {

    $payload = json_decode(file_get_contents('php://input'), true);

    if (!is_array($payload)) {
        status_header(400);
        exit;
    }

    // Keep existing sandbox allowlist behaviour if SANDBOX_IPS is populated.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (class_exists('NatWest_PayIt_Constants') && is_array(NatWest_PayIt_Constants::SANDBOX_IPS) && !empty(NatWest_PayIt_Constants::SANDBOX_IPS)) {
        if (!in_array($ip, NatWest_PayIt_Constants::SANDBOX_IPS, true)) {
            status_header(403);
            exit;
        }
    }

    $reference = $payload['reference'] ?? ($payload['tppReference'] ?? ($payload['TPPReferenceID'] ?? ''));
    if (empty($reference)) {
        status_header(400);
        exit;
    }

    $order = null;

    // 1) If reference looks like an order ID, try direct.
    if (is_numeric($reference)) {
        $order = wc_get_order((int) $reference);
    }

    // 2) Otherwise try matching on stored PayIt TPP reference.
    if (!$order) {
        $orders = wc_get_orders([
            'limit'      => 1,
            'meta_key'   => '_natwest_payit_tpp_reference',
            'meta_value' => (string) $reference,
            'orderby'    => 'date',
            'order'      => 'DESC',
        ]);
        if (!empty($orders)) {
            $order = $orders[0];
        }
    }

    if (!$order) {
        status_header(404);
        exit;
    }

    $status = strtoupper((string) ($payload['status'] ?? ($payload['Status'] ?? '')));

    switch ($status) {
        case 'COMPLETED':
        case 'PAID':
        case 'SUCCESS':
        case 'SUCCESSFUL':
        case 'SETTLED':
            $order->payment_complete();
            break;

        case 'FAILED':
        case 'CANCELLED':
        case 'REJECTED':
            $order->update_status('failed', 'NatWest PayIt payment failed');
            break;
    }

    status_header(200);
    exit;
});
