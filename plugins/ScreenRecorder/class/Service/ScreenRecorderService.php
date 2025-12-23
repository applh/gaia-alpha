<?php

namespace ScreenRecorder\Service;

use GaiaAlpha\Env;
use MediaLibrary\Service\MediaLibraryService;

class ScreenRecorderService
{
    /**
     * Save a recording to the media library
     */
    public function saveRecording(int $userId, array $fileInfo, string $filename): int
    {
        $uploadDir = Env::get('path_data') . '/uploads/' . $userId;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . '/' . $filename;

        // In a real PHP environment, we'd use move_uploaded_file
        // Since this is a specialized environment, we'll simulate the move if it's already in a temp path
        // or just copy it if it's provided as a path.
        if (isset($fileInfo['tmp_name']) && file_exists($fileInfo['tmp_name'])) {
            if (!rename($fileInfo['tmp_name'], $targetPath)) {
                throw new \Exception("Failed to move uploaded file to target directory.");
            }
        } else {
            // Fallback for manual uploads or simulated calls
            throw new \Exception("Invalid file data provided.");
        }

        // Register in Media Library
        $mediaData = [
            'user_id' => $userId,
            'filename' => $filename,
            'original_filename' => $filename,
            'mime_type' => $fileInfo['type'] ?? 'video/webm',
            'file_size' => filesize($targetPath),
            'caption' => 'Screen Recording ' . date('Y-m-d H:i')
        ];

        return MediaLibraryService::createMedia($mediaData);
    }
}
