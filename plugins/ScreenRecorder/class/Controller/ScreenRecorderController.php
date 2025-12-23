<?php

namespace ScreenRecorder\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Router;
use GaiaAlpha\Session;
use ScreenRecorder\Service\ScreenRecorderService;

class ScreenRecorderController extends BaseController
{
    private $service;

    public function __construct()
    {
        $this->service = new ScreenRecorderService();
    }

    public function registerRoutes()
    {
        Router::add('POST', '/@/screen-recorder/upload', [$this, 'upload']);
        Router::add('GET', '/@/screen-recorder/status', [$this, 'status']);
    }

    public function upload()
    {
        $this->requireAuth();

        $userId = Session::get('user_id');
        $files = Request::file();

        if (empty($files['video'])) {
            Response::json(['error' => 'No video file provided'], 400);
            return;
        }

        $videoFile = $files['video'];
        $filename = Request::input('filename') ?? 'recording_' . date('Ymd_His') . '.webm';

        try {
            $mediaId = $this->service->saveRecording($userId, $videoFile, $filename);
            Response::json([
                'success' => true,
                'media_id' => $mediaId,
                'message' => 'Recording saved to Media Library'
            ]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function status()
    {
        Response::json([
            'active' => true,
            'max_duration' => 1800, // 30 mins
            'supported_formats' => ['video/webm', 'video/mp4']
        ]);
    }
}
