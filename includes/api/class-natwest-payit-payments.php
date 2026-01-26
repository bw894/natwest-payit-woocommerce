<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Payments extends NatWest_PayIt_API {

    public function create_payment($order, $company_id, $brand_id) {

        $payload = [
            'companyId' => $company_id,
            'brandId'   => $brand_id,
            'amount'    => [
                'currency' => $order->get_currency(),
                'value'    => number_format($order->get_total(), 2, '.', '')
            ],
            'reference' => $order->get_order_number(),
            'journeyType' => 'HOSTED',
            'redirectUrls' => [
                'success' => home_url('/payit/return/success'),
                'failure' => home_url('/payit/return/failure')
            ]
        ];

        return $this->request(
            'POST',
            '/lp2nos-merchant/merchant-payments',
            $payload
        );
    }
}
