<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Payments extends NatWest_PayIt_API {

    public function create_payment($order, $company_id, $brand_id) {

        // E2E header value must be WooCommerce order ID
        $transaction_id = (string) $order->get_id();

        // Amount should be a decimal number like 20.45
        $amount = (float) number_format((float) $order->get_total(), 2, '.', '');

        $order_number = (string) $order->get_order_number();
        $billing_city = (string) $order->get_billing_city();
        if (empty($billing_city)) {
            $billing_city = 'London';
        }

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

                // Example payload uses redirectUrl (single URL)
                'redirectUrl'          => $order->get_checkout_order_received_url(),

                'paymentContext' => [
                    'deliveryAddress' => [
                        'countryCode' => 'GB',
                        'townName'    => $billing_city
                    ],
                    'merchantCategoryCode' => '1688',
                    'merchantCustomerIdentification' => (string) $order->get_customer_id(),
                    'paymentContextCode' => 'EcommerceGoods'
                ],
            ]
        ];

        return $this->request(
            'POST',
            '/lp2nos-merchant/merchant-payments',
            $payload,
            [
                // NatWest requirement: pass E2E value here
                'x-transaction-id' => $transaction_id
                'Accept'           => 'application/app.v3+json',
            ]
        );
    }
}
