<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Status extends NatWest_PayIt_API {

    public function get_status($company_id, $brand_id, $tpp_reference) {

        return $this->request(
            'GET',
            "/lp2nos-merchant/merchant-payments/{$company_id}/{$brand_id}/{$tpp_reference}"
        );
    }
}
