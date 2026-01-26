<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Payments extends NatWest_PayIt_API {

    /**
     * Create a PayIt payment.
     *
     * Payload format updated to match the example structure you provided:
     * {
     *   "Data": {
     *     "companyId": "...",
     *     "brandId": "...",
     *     "amount": 20.45,
     *     "currency": "GBP",
     *     "additionalMessaging": "...",
     *     "country": "UK",
     *     "fpReference": "...",
     *     "description": "...",
     *     "redirectUrl": "https://payment.natwestpayit.com/status",
     *     "paymentContext": {...}
     *   }
     * }
     */
    public function create_payment($order, $company_id, $brand_id) {

        // Amount should be a decimal number (NOT minor units, not nested object)
        $amount = (float) $order->get_total();

        // Keep to 2dp as a float-like number (JSON encoder will output 2dp if we format then cast back)
        $amount = (float) number_format($amount, 2, '.', '');

        $currency = (string) $order->get_currency();

        // Useful identifiers/descriptions
        $order_number = (string) $order->get_order_number();
        $fp_reference = 'WC-' . $order_number;
        $description  = 'Payment for order ' . $order_number;

        // Example payload uses "redirectUrl" (single URL), not redirectUrls array
        // We'll send the customer back to the order received page
        $redirect_url = $order->get_checkout_order_received_url();

        // Build a minimal but compliant paymentContext from the example
        $billing_city = (string) $order->get_billing_city();
        if (empty($billing_city)) {
            $billing_city = 'London';
        }

        $payload = [
            'Data' => [
                'companyId' => (string) $company_id,
                'brandId'   => (string) $brand_id,

                'amount'    => $amount,
                'currency'  => $currency,

                // Optional but included in your example
                'additionalMessaging' => 'Payment for WooCommerce order ' . $order_number,
                'country'             => 'UK',
                'fpReference'          => $fp_reference,
                'description'          => $description,
                'redirectUrl'          => $redirect_url,

                'paymentContext' => [
                    'deliveryAddress' => [
                        'countryCode' => 'GB',
                        'townName'    => $billing_city
                    ],
                    // Using the example value; if NatWest provides your MCC, swap it here or make configurable
                    'merchantCategoryCode' => '1688',
                    'merchantCustomerIdentification' => (string) $order->get_customer_id(),
                    'paymentContextCode' => 'EcommerceGoods'
                ],

                /**
                 * instructedAccounts is optional in your example.
                 * Leaving it out by default avoids validation errors unless you have real values.
                 * If you later want to support it, add it back with real data from user selection.
                 */
            ]
        ];

        return $this->request(
            'POST',
            '/lp2nos-merchant/merchant-payments',
            $payload
        );
    }
}
