<?php

namespace GaiaAlpha;

class Response
{
    /**
     * Send a JSON response
     * 
     * @param mixed $data Data to encode
     * @param int $status HTTP status code
     * @param bool $exit Whether to exit script execution
     */
    public static function json($data, int $status = 200, bool $exit = true)
    {
        // Allow plugins to modify response data or status
        // Pass by reference so they can be modified
        $context = [
            'data' => &$data,
            'status' => &$status
        ];

        // Hook to modify data and status before JSON encoding
        Hook::run('response_json_before', $context);

        // Notify that we are about to send response
        Hook::run('response_send_before', $data, $status);

        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);

        if ($exit) {
            exit;
        }
    }
}
