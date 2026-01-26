<?php
if (!defined('ABSPATH')) exit;

class NatWest_PayIt_Auth {

    /**
     * Fetch an OAuth access token using client_credentials
     *
     * Returns: string access_token on success, or '' on failure.
     */
    public static function get_token($client_id, $client_secret) {

        // Fail fast if settings are missing
        if (empty($client_id) || empty($client_secret)) {
            NatWest_PayIt_Logger::log('Token request aborted: client_id or client_secret missing.', 'error');
            return '';
        }

        // Build token request
        $args = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json'
            ],
            'body' => http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $client_id,
                'client_secret' => $client_secret,

                /**
                 * NOTE:
                 * This "resource" value is often environment/product specific.
                 * If you continue seeing 401 on merchant-payments even with a token,
                 * this is the first parameter to confirm with NatWest PayIt docs.
                 */
                'resource'      => defined('NATWEST_PAYIT_OAUTH_RESOURCE')
                    ? NATWEST_PAYIT_OAUTH_RESOURCE
                    : 'https://lp2api.natwestpayit.com'
            ]),
            'timeout' => 30
        ];

        $url = NatWest_PayIt_Constants::TOKEN_URL;
        $response = wp_remote_post($url, $args);

        // Transport-level failure
        if (is_wp_error($response)) {
            NatWest_PayIt_Logger::log('Token request WP_Error: ' . $response->get_error_message(), 'error');
            NatWest_PayIt_Logger::log(['token_url' => $url], 'error');
            return '';
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);

        // Log token responses (helpful during development; consider lowering to debug later)
        NatWest_PayIt_Logger::log(
            [
                'token_url'  => $url,
                'http_code'  => $code,
                'raw_body'   => $raw
            ],
            $code >= 400 ? 'error' : 'debug'
        );

        if ($code < 200 || $code >= 300) {
            // Non-success response
            return '';
        }

        $body = json_decode($raw, true);
        if (!is_array($body)) {
            NatWest_PayIt_Logger::log('Token response JSON decode failed.', 'error');
            return '';
        }

        $token = $body['access_token'] ?? '';
        if (empty($token) || !is_string($token)) {
            NatWest_PayIt_Logger::log('Token response missing access_token.', 'error');
            NatWest_PayIt_Logger::log(['decoded_body' => $body], 'error');
            return '';
        }

        return $token;
    }
}
