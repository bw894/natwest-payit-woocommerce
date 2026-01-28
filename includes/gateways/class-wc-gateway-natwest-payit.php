<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_NatWest_PayIt extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'natwest_payit';
        $this->method_title       = __( 'NatWest PayIt', 'natwest-payit' );
        $this->method_description = __( 'Pay by bank (NatWest PayIt)', 'natwest-payit' );
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        add_action(
            'woocommerce_api_natwest_payit_return',
            [ $this, 'handle_payit_return' ]
        );
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        $return_url = add_query_arg(
            [
                'wc-api'   => 'natwest_payit_return',
                'order_id' => $order->get_id(),
                'key'      => $order->get_order_key(),
            ],
            home_url( '/' )
        );

        $payit = new NatWest_PayIt_Payments();

        $response = $payit->create_payment(
            $order,
            $return_url
        );

        if ( empty( $response['redirectUrl'] ) ) {
            wc_add_notice( __( 'Unable to initiate PayIt payment.', 'natwest-payit' ), 'error' );
            return;
        }

        // Store PayIt reference safely
        if ( ! empty( $response['tppReference'] ) ) {
            $order->update_meta_data(
                '_natwest_payit_tpp_reference',
                sanitize_text_field( $response['tppReference'] )
            );
            $order->save();
        }

        return [
            'result'   => 'success',
            'redirect' => esc_url_raw( $response['redirectUrl'] ),
        ];
    }

    public function handle_payit_return() {

        $order_id  = absint( $_GET['order_id'] ?? 0 );
        $order_key = sanitize_text_field( $_GET['key'] ?? '' );

        if ( ! $order_id || ! $order_key ) {
            wp_die( 'Invalid PayIt return' );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order || $order->get_order_key() !== $order_key ) {
            wp_die( 'Order validation failed' );
        }

        if ( $order->is_paid() ) {
            wp_safe_redirect( $order->get_checkout_order_received_url() );
            exit;
        }

        $tpp_reference = $order->get_meta( '_natwest_payit_tpp_reference' );

        if ( ! $tpp_reference ) {
            wp_die( 'Missing PayIt reference' );
        }

        $status_api = new NatWest_PayIt_Status();
        $status     = $status_api->get_status( $tpp_reference );

        if ( isset( $status['status'] ) && $status['status'] === 'PAID' ) {
            $order->payment_complete();
            $order->add_order_note( 'Payment confirmed via PayIt return.' );
        }

        wp_safe_redirect( $order->get_checkout_order_received_url() );
        exit;
    }
}
