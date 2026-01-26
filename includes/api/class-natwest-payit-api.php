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
            $args['body'] = wp_json_encode($body);
        }
        
        $url = NatWest_PayIt_Constants::API_BASE . $endpoint;
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            NatWest_PayIt_Logger::log('API request WP_Error: ' . $response->get_error_message(), 'error');
            NatWest_PayIt_Logger::log(['url' => $url, 'endpoint' => $endpoint], 'error');
            return [];
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        
        NatWest_PayIt_Logger::log([
                                  'url' => $url,
                                  'http_code' => $code,
                                  'raw_body' => $raw
                                  ],
                                  $code >= 400 ? 'error' : 'debug');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
