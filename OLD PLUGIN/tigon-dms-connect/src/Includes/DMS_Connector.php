<?php

namespace Tigon\DmsConnect\Includes;

final class DMS_Connector {
    /**
     * Sends an HTTP request to the DMS using the auth data from the database
     *
     * @param string $query
     * @param string $endpoint
     * @param string $method GET, PUT, POST, DELETE
     * @return string DMS response
     */
    public static function request(string $query, string $endpoint, string $method)
    {
        $query = stripcslashes($query);
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = $wpdb->prefix . 'tigon_dms_config';

        $url = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'dms_url'") . $endpoint;
        $user_token = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'user_token'");

        // Validate auth and reacquire if needed
        $auth_token = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'auth_token'");
        if (!$auth_token) {
            $auth_token = DMS_Connector::get_auth($user_token);
            $wpdb->update($table_name, ['option_value' => $auth_token], ['option_name' => 'auth_token']);
        }

        // Get data from DMS
        $options = [
            'http' => [
                'header' => ["x-auth-token:" . $auth_token, 'Content-type: application/json'],
                'method' => $method,
                'content' => $query
            ]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        // On failure, reobtain auth
        if (!$result) {
            $retry_auth_token = DMS_Connector::get_auth($user_token, $auth_token);
            $wpdb->update($table_name, ['option_value' => $retry_auth_token], ['option_name' => 'auth_token']);

            $options['http']['header'] = ["x-auth-token:" . $retry_auth_token, 'Content-type: application/json'];

            $retry_context = stream_context_create($options);
            return file_get_contents($url, false, $retry_context);
        } else
            return $result;
    }

    public static function get_auth(string $amplify_id, string $old_token = null)
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = $wpdb->prefix . 'tigon_dms_config';

        $url = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'dms_url'") . '/auth';
        //$data = json_encode(['token' => $token]);


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

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($body)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            throw new \BadFunctionCallException("Token was invalid");
        } else {
            $result = json_decode($result)->token;
            return $result;
        }
    }
}