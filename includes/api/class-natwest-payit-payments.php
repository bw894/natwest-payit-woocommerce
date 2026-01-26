<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Payments extends NatWest_PayIt_API {

    public function create_payment($order, $company_id, $brand_id) {

        /**
         * PayIt LP2 expects the request payload wrapped in a top-level "Data" object.
         * Without this wrapper, the API returns: "Json parse error, field: Data"
         */
        $payload = [
            'Data' => [
                'companyId' => (string) $company_id,
                'brandId'   => (string) $brand_id,
                'amount'    => [
                    'currency' => (string) $order->get_currency(),
                    // Keep value as a 2dp string (common requirement for payment APIs)
                    'value'    => number_format((float) $order->get_total(), 2, '.', '')
                ],
                'reference'   => (string) $order->get_order_number(),
                'journeyType' => 'HOSTED',
                'redirectUrls' => [
                    'success' => home_url('/payit/return/success'),
                    'failure' => home_url('/payit/return/failure')
                ]
            ]
        ];

        return $this->request(
            'POST',
            '/lp2nos-merchant/merchant-payments',
            $payload
        );
    }
}
