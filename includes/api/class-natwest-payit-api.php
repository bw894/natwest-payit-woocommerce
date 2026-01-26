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

        if (!empty($this->token) && is_string($this->token)) {
            return true;
        }

        if (!class_exists('NatWest_PayIt_Auth')) {
            require_once NATWEST_PAYIT_PLUGIN_PATH . 'includes/auth/class-natwest-payit-auth.php';
        }

        $token = NatWest_PayIt_Auth::get_token(
            $this->client_id,
            $this->client_secret
        );

        if (empty($token) || !is_string($token)) {
            NatWest_PayIt_Logger::log('Failed to obtain valid PayIt access token.', 'error');
            NatWest_PayIt_Logger::log(['token_returned' => $token], 'error');
            return false;
        }

        $this->token = $token;
        return true;
    }

    /**
     * Perform an authenticated API request
     *
     * @param string $method
     * @param string $endpoint
     * @param array|null $body
     * @param array $extra_headers  Additional headers to merge into the request headers
     * @return array
     */
    protected function request($method, $endpoint, $body = null, $extra_headers = []) {

        if (!$this->ensure_token()) {
            return [];
        }

        $base_headers = [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json'
        ];

        // Normalise header keys (some APIs are picky; WP will send them as provided)
        if (!is_array($extra_headers)) {
            $extra_headers = [];
        }

        $args = [
            'method'  => $method,
            'headers' => array_merge($base_headers, $extra_headers),
            'timeout' => 30
        ];

        if (!empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        $url = NatWest_PayIt_Constants::API_BASE . $endpoint;

        // Debug request payload and safe headers (avoid logging Authorization)
        $log_headers = $args['headers'];
        if (isset($log_headers['Authorization'])) {
            $log_headers['Authorization'] = '[REDACTED]';
        }

        NatWest_PayIt_Logger::log([
            'url'          => $url,
            'endpoint'     => $endpoint,
            'http_method'  => $method,
            'headers'      => $log_headers,
            'request_body' => isset($args['body']) ? $args['body'] : null
        ], 'debug');

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            NatWest_PayIt_Logger::log('API request WP_Error: ' . $response->get_error_message(), 'error');
            NatWest_PayIt_Logger::log(['url' => $url, 'endpoint' => $endpoint], 'error');
            return [];
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);

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
