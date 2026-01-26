<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_API {

    protected $token;

    public function __construct($client_id, $client_secret) {
        $this->token = NatWest_PayIt_Auth::get_token($client_id, $client_secret);
    }

    protected function request($method, $endpoint, $body = null) {

        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json'
            ],
            'timeout' => 30
        ];

        if ($body) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request(
            NatWest_PayIt_Constants::API_BASE . $endpoint,
            $args
        );

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
