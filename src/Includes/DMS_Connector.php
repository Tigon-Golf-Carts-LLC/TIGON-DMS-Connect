<?php

namespace Tigon\DmsConnect\Includes;

final class DMS_Connector {
    /**
     * Sends an HTTP request to the DMS using the auth data from the database
     *
     * @param string $query
     * @param string $endpoint
     * @param string $method GET, PUT, POST, DELETE
     * @return string|false DMS response body, or false on failure
     */
    public static function request(string $query, string $endpoint, string $method)
    {
        $query = stripcslashes($query);
        global $wpdb;

        $table_name = $wpdb->prefix . 'tigon_dms_config';

        $dms_url = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'dms_url'");
        if (empty($dms_url)) {
            error_log('[DMS Connector] Error: dms_url is not configured');
            return false;
        }
        $url = $dms_url . $endpoint;
        $user_token = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'user_token'");

        // Validate auth and reacquire if needed
        $auth_token = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'auth_token'");
        if (!$auth_token) {
            $auth_token = DMS_Connector::get_auth($user_token);
            if (!$auth_token) {
                return false;
            }
            $wpdb->update($table_name, ['option_value' => $auth_token], ['option_name' => 'auth_token']);
        }

        // Make request using WordPress HTTP API
        $result = self::wp_request($url, $method, $query, $auth_token);

        // On failure, reobtain auth and retry
        if ($result === false) {
            $retry_auth_token = DMS_Connector::get_auth($user_token, $auth_token);
            if (!$retry_auth_token) {
                return false;
            }
            $wpdb->update($table_name, ['option_value' => $retry_auth_token], ['option_name' => 'auth_token']);

            return self::wp_request($url, $method, $query, $retry_auth_token);
        }

        return $result;
    }

    /**
     * Perform an HTTP request using the WordPress HTTP API
     *
     * @param string $url
     * @param string $method
     * @param string $body
     * @param string $auth_token
     * @return string|false Response body or false on failure
     */
    private static function wp_request(string $url, string $method, string $body, string $auth_token)
    {
        $response = wp_remote_request($url, [
            'method'  => $method,
            'headers' => [
                'x-auth-token' => $auth_token,
                'Content-Type' => 'application/json',
            ],
            'body'    => $body,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            error_log('[DMS Connector] HTTP error: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        $result = wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            error_log('[DMS Connector] HTTP ' . $code . ' from ' . $url . ': ' . substr($result, 0, 200));
            return false;
        }

        return $result;
    }

    public static function get_auth(string $amplify_id, string $old_token = null)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tigon_dms_config';

        $dms_url = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'dms_url'");
        if (empty($dms_url)) {
            error_log('[DMS Connector] Error: dms_url is not configured for auth');
            return false;
        }
        $url = $dms_url . '/auth';

        if($old_token !== null)
        {
            $url = $url . '/get-refresh-token';
            $body = [
                'amplifyId' => $amplify_id,
                'oldToken' => $old_token
            ];
        } else {
            $url = $url . '/get-token';
            $body = [
                'amplifyUserId' => $amplify_id
            ];
        }

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body'    => $body,
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('[DMS Connector] Auth error: ' . $response->get_error_message());
            return false;
        }

        $result = wp_remote_retrieve_body($response);
        $decoded = json_decode($result);

        if (empty($decoded->token)) {
            error_log('[DMS Connector] Auth failed: no token in response');
            return false;
        }

        return $decoded->token;
    }
}
