<?php
if (!defined('ABSPATH')) exit;

add_action('woocommerce_api_payit_webhook', function () {

    $ip = $_SERVER['REMOTE_ADDR'];

    if (!in_array($ip, NatWest_PayIt_Constants::SANDBOX_IPS)) {
        status_header(403);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);

    if (empty($payload['reference'])) {
        status_header(400);
        exit;
    }

    $order = wc_get_order($payload['reference']);

    if (!$order) {
        status_header(404);
        exit;
    }

    switch ($payload['status']) {
        case 'COMPLETED':
            $order->payment_complete();
            break;

        case 'FAILED':
        case 'CANCELLED':
            $order->update_status('failed', 'NatWest PayIt payment failed');
            break;
    }

    status_header(200);
    exit;
});
