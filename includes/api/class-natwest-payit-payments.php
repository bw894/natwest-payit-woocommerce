<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Payments extends NatWest_PayIt_API {

    public function create_payment($order, $company_id, $brand_id) {

        // NatWest E2E requirement
        $transaction_id = (string) $order->get_id();

        $amount = (float) number_format((float) $order->get_total(), 2, '.', '');

        $order_number = (string) $order->get_order_number();
        $billing_city = (string) $order->get_billing_city() ?: 'London';

        /**
         * IMPORTANT:
         * Do NOT send users back to /checkout/ because PayIt adds query params like `s=...`
         * which WordPress interprets as a search request (breaking routing).
         *
         * Use a wc-api endpoint instead.
         */
        $redirect_url = add_query_arg([
            'wc-api'   => 'natwest_payit_return',
            'order_id' => $order->get_id(),
            'key'      => $order->get_order_key(),
        ], home_url('/'));

        $payload = [
            'Data' => [
                'companyId' => (string) $company_id,
                'brandId'   => (string) $brand_id,
                'amount'    => $amount,
                'currency'  => (string) $order->get_currency(),
                'additionalMessaging' => 'Payment for WooCommerce order ' . $order_number,
                'country'             => 'UK',
                'fpReference'          => 'WC-' . $order_number,
                'description'          => 'Payment for order ' . $order_number,
                'redirectUrl'          => $redirect_url,
                'paymentContext' => [
                    'deliveryAddress' => [
                        'countryCode' => 'GB',
                        'townName'    => $billing_city
                    ],
                    'merchantCategoryCode' => '1688',
                    'merchantCustomerIdentification' => (string) $order->get_customer_id(),
                    'paymentContextCode' => 'EcommerceGoods'
                ]
            ]
        ];

        return $this->request(
            'POST',
            '/lp2nos-merchant/merchant-payments',
            $payload,
            [
                // REQUIRED BY NATWEST FOR THIS ENDPOINT
                'Accept'            => 'application/app.v3+json',
                'x-transaction-id'  => $transaction_id
            ]
        );
    }
}
