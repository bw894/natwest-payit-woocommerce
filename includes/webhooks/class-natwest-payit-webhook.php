<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NatWest_PayIt_Webhook {

    public function handle() {

        $payload = json_decode( file_get_contents( 'php://input' ), true );

        if ( empty( $payload['tppReference'] ) || empty( $payload['status'] ) ) {
            status_header( 400 );
            exit;
        }

        $orders = wc_get_orders( [
            'limit'      => 1,
            'meta_key'   => '_natwest_payit_tpp_reference',
            'meta_value' => sanitize_text_field( $payload['tppReference'] ),
        ] );

        if ( empty( $orders ) ) {
            status_header( 404 );
            exit;
        }

        $order = $orders[0];

        if ( $payload['status'] === 'PAID' && ! $order->is_paid() ) {
            $order->payment_complete();
            $order->add_order_note( 'Payment confirmed via PayIt webhook.' );
        }

        status_header( 200 );
        exit;
    }
}
