<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Utils {

    public static function get_order_by_reference($reference) {
        return wc_get_order($reference);
    }
}
