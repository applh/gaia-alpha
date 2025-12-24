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
        if (isset($fileInfo['error']) && $fileInfo['error'] !== UPLOAD_ERR_OK) {
            switch ($fileInfo['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    throw new \Exception("File exceeds upload_max_filesize directive in php.ini");
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \Exception("File exceeds MAX_FILE_SIZE directive in HTML form");
                case UPLOAD_ERR_PARTIAL:
                    throw new \Exception("The file was only partially uploaded");
                case UPLOAD_ERR_NO_FILE:
                    throw new \Exception("No file was uploaded");
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new \Exception("Missing a temporary folder");
                case UPLOAD_ERR_CANT_WRITE:
                    throw new \Exception("Failed to write file to disk");
                case UPLOAD_ERR_EXTENSION:
                    throw new \Exception("A PHP extension stopped the file upload");
                default:
                    throw new \Exception("Unknown upload error: " . $fileInfo['error']);
            }
        }

        // In a real PHP environment, we'd use move_uploaded_file
        // Since this is a specialized environment, we'll simulate the move if it's already in a temp path
        // or just copy it if it's provided as a path.
        if (isset($fileInfo['tmp_name']) && is_uploaded_file($fileInfo['tmp_name'])) {
            if (!move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
                throw new \Exception("Failed to move uploaded file to target directory.");
            }
        } elseif (isset($fileInfo['tmp_name']) && file_exists($fileInfo['tmp_name'])) {
            // Fallback for non-standard environments or testing
            if (!rename($fileInfo['tmp_name'], $targetPath)) {
                throw new \Exception("Failed to move file from temp path to target directory.");
            }
        } else {
            // Fallback for manual uploads or simulated calls where tmp_name might be missing or invalid
            throw new \Exception("Invalid file data provided or file upload failed silently.");
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
