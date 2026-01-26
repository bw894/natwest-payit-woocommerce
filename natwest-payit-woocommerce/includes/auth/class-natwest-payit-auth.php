<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Auth {

    public static function get_token($client_id, $client_secret) {

        $response = wp_remote_post(
            NatWest_PayIt_Constants::TOKEN_URL,
            [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => http_build_query([
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'resource'      => 'https://natwestpayit.com'
                ]),
                'timeout' => 30
            ]
        );

        if (is_wp_error($response)) {
            NatWest_PayIt_Logger::log('Token request failed', 'error');
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $body['access_token'] ?? null;
    }
}
