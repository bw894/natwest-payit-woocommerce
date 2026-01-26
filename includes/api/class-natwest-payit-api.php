<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_API {

    protected $client_id;
    protected $client_secret;
    protected $token;

    public function __construct($client_id, $client_secret) {
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->token         = '';
    }

    /**
     * Ensure we have a valid access token before making API calls
     */
    protected function ensure_token() {

        // Token already present and looks valid
        if (!empty($this->token) && is_string($this->token)) {
            return true;
        }

        // Attempt to fetch token
        if (!class_exists('NatWest_PayIt_Auth')) {
            require_once NATWEST_PAYIT_PLUGIN_PATH . 'includes/auth/class-natwest-payit-auth.php';
        }

        $token = NatWest_PayIt_Auth::get_token(
            $this->client_id,
            $this->client_secret
        );

        if (empty($token) || !is_string($token)) {
            NatWest_PayIt_Logger::log(
                'Failed to obtain valid PayIt access token.',
                'error'
            );
            NatWest_PayIt_Logger::log(
                ['token_returned' => $token],
                'error'
            );
            return false;
        }

        $this->token = $token;
        return true;
    }

    /**
     * Perform an authenticated API request
     */
    protected function request($method, $endpoint, $body = null) {

        // Ensure token exists
        if (!$this->ensure_token()) {
            return [];
        }

        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json'
            ],
            'timeout' => 30
        ];

        if (!empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        $url = NatWest_PayIt_Constants::API_BASE . $endpoint;
        $response = wp_remote_request($url, $args);

        // Transport-level failure
        if (is_wp_error($response)) {
            NatWest_PayIt_Logger::log(
                'API request WP_Error: ' . $response->get_error_message(),
                'error'
            );
            NatWest_PayIt_Logger::log(
                ['url' => $url, 'endpoint' => $endpoint],
                'error'
            );
            return [];
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);

        // Log full response for debugging
        NatWest_PayIt_Logger::log(
            [
                'url'       => $url,
                'endpoint'  => $endpoint,
                'http_code' => $code,
                'raw_body'  => $raw
            ],
            $code >= 400 ? 'error' : 'debug'
        );

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
