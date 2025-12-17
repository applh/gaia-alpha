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
    public static function send($content, int $status = 200, bool $exit = false)
    {
        if ($status !== 200) {
            http_response_code($status);
        }

        // Hook for global modification (e.g. Debug Headers, Compression)
        $context = ['content' => &$content, 'status' => $status];
        Hook::run('response_send', $context);

        echo $content;

        if ($exit) {
            exit;
        }
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

    /**
     * Clear all active output buffers.
     * Useful when serving binary files or non-HTML content where previous output should be discarded.
     */
    public static function clearBuffer()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Set a raw HTTP header
     */
    public static function header(string $string, bool $replace = true, int $response_code = 0)
    {
        if ($response_code) {
            header($string, $replace, $response_code);
        } else {
            header($string, $replace);
        }
    }


    /**
     * Send a file directly to output
     */
    public static function file(string $path, bool $exit = false)
    {
        readfile($path);

        if ($exit) {
            exit;
        }
    }
}
