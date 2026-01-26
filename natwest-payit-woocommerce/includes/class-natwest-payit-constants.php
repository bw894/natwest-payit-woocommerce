<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Constants {

    const TOKEN_URL = 'https://login.microsoftonline.com/a67f64d9-57b9-4c56-b2a2-8bfb5f86966a/oauth2/token';

    const API_BASE = 'https://lp2api.natwestpayit.com';

    const SANDBOX_IPS = [
        '20.49.129.223',
        '40.81.114.141'
    ];
}
