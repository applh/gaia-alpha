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
    /**
     * Send a JSON response
     * 
     * @param mixed $data Data to encode
     * @param int $status HTTP status code
     * @param bool $exit Deprecated: Whether to exit (ignored, framework handles lifecycle)
     */
    public static function json($data, int $status = 200, bool $exit = false)
    {
        // Allow plugins to modify response data or status
        $context = [
            'data' => &$data,
            'status' => &$status
        ];

        // Hook to modify data vs status
        Hook::run('response_json_before', $context);

        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);

        // We do NOT exit here anymore. We return control to framework.
    }

    /**
     * Send final response content
     * Called by App::run at the end of lifecycle
     */
    public static function send($content)
    {
        // Hook for global modification (e.g. Debug Headers, Compression)
        $context = ['content' => &$content];
        Hook::run('response_send', $context);

        echo $content;
    }

    /**
     * Start Output Buffering
     * Used as a Framework Task (step01)
     */
    public static function startBuffer()
    {
        // Always start our own buffer to capture framework output reliably
        ob_start();
    }

    /**
     * Flush output buffer and send response
     * Used as a Framework Task (step99)
     */
    public static function flush()
    {
        $content = ob_get_clean();

        if ($content === false) {
            $content = '';
        }
        self::send($content);
    }
}
