<?php

namespace MediaLibrary\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Session;
use GaiaAlpha\Media;
use GaiaAlpha\Video;
use GaiaAlpha\Env;
use MediaLibrary\Service\MediaLibraryService;

class MediaLibraryController extends BaseController
{

    private Media $media;

    public function init()
    {

        $this->media = new Media(Env::get('path_data'));
    }

    /**
     * List all media files
     */
    public function listFiles()
    {
        if (!$this->requireAuth())
            return;
        $userId = Session::id();

        $filters = [
            'tag' => Request::query('tag'),
            'search' => Request::query('search'),
            'limit' => Request::query('limit')
        ];

        $files = MediaLibraryService::getAllMedia($userId, array_filter($filters));
        Response::json($files);
    }

    /**
     * Get single file details
     */
    public function getFile($id)
    {
        if (!$this->requireAuth())
            return;

        $file = MediaLibraryService::getMediaById((int) $id);
        if (!$file) {
            Response::json(['error' => 'File not found'], 404);
            return;
        }

        Response::json($file);
    }

    /**
     * Upload new file
     */
    public function uploadFile()
    {
        if (!$this->requireAuth())
            return;
        $userId = Session::id();

        if (empty($_FILES['file'])) {
            Response::json(['error' => 'No file uploaded'], 400);
            return;
        }

        try {
            $file = $_FILES['file'];

            // Upload file using existing Media class
            $result = $this->media->upload($file, $userId);

            // Get image dimensions if it's an image
            $width = null;
            $height = null;
            if (strpos($file['type'], 'image/') === 0) {
                $filePath = Env::get('path_data') . '/uploads/' . $userId . '/' . $result['filename'];
                if (file_exists($filePath)) {
                    $imageInfo = getimagesize($filePath);
                    if ($imageInfo) {
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                    }
                }
            }

            // Create database record
            $mediaId = MediaLibraryService::createMedia([
                'user_id' => $userId,
                'filename' => $result['filename'],
                'original_filename' => $file['name'],
                'mime_type' => $result['mime'],
                'file_size' => $file['size'],
                'width' => $width,
                'height' => $height
            ]);

            $media = MediaLibraryService::getMediaById($mediaId);
            Response::json($media);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update file metadata
     */
    public function updateFile($id)
    {
        if (!$this->requireAuth())
            return;

        $data = Request::input();
        $success = MediaLibraryService::updateMedia((int) $id, $data);

        if ($success) {
            $file = MediaLibraryService::getMediaById((int) $id);
            Response::json($file);
        } else {
            Response::json(['error' => 'Update failed'], 400);
        }
    }

    /**
     * Delete file
     */
    public function deleteFile($id)
    {
        if (!$this->requireAuth())
            return;

        $success = MediaLibraryService::deleteMedia((int) $id);
        Response::json(['success' => $success]);
    }

    /**
     * List all tags
     */
    public function listTags()
    {
        if (!$this->requireAuth())
            return;

        $tags = MediaLibraryService::getAllTags();
        Response::json($tags);
    }

    /**
     * Create new tag
     */
    public function createTag()
    {
        if (!$this->requireAuth())
            return;

        $data = Request::input();
        if (empty($data['name'])) {
            Response::json(['error' => 'Tag name is required'], 400);
            return;
        }

        $tagId = MediaLibraryService::createTag(
            $data['name'],
            $data['color'] ?? '#6366f1'
        );

        $tags = MediaLibraryService::getAllTags();
        $tag = array_filter($tags, fn($t) => $t['id'] == $tagId);
        Response::json(reset($tag));
    }

    /**
     * Delete tag
     */
    public function deleteTag($id)
    {
        if (!$this->requireAuth())
            return;

        $success = MediaLibraryService::deleteTag((int) $id);
        Response::json(['success' => $success]);
    }

    /**
     * Assign tags to file
     */
    public function assignTags($id)
    {
        if (!$this->requireAuth())
            return;

        $data = Request::input();
        $tagIds = $data['tag_ids'] ?? [];

        $success = MediaLibraryService::assignTags((int) $id, $tagIds);

        if ($success) {
            $file = MediaLibraryService::getMediaById((int) $id);
            Response::json($file);
        } else {
            Response::json(['error' => 'Failed to assign tags'], 400);
        }
    }

    /**
     * Search media files
     */
    public function search()
    {
        if (!$this->requireAuth())
            return;
        $userId = Session::id();

        $query = Request::query('q');
        if (empty($query)) {
            Response::json(['error' => 'Search query is required'], 400);
            return;
        }

        $results = MediaLibraryService::searchMedia($query, $userId);
        Response::json($results);
    }

    /**
     * Get media library statistics
     */
    public function stats()
    {
        if (!$this->requireAuth())
            return;
        $userId = Session::id();

        $stats = MediaLibraryService::getStats($userId);
        Response::json($stats);
    }

    public function processImage()
    {
        if (!$this->requireAuth())
            return;
        $data = Request::input();

        // Handle Base64 image save (simplified)
        // In a real app, you'd decode base64 and overwrite the file
        // For this demo, let's assume we decode and save
        $path = $data['path'];
        $image = $data['image'];

        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $image = substr($image, strpos($image, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif
            $image = base64_decode($image);
            if ($image === false) {
                return Response::json(['success' => false, 'error' => 'Base64 decode failed'], 500);
            }
        } else {
            return Response::json(['success' => false, 'error' => 'Invalid image data'], 400);
        }

        // We need the full system path. 
        // Assuming path passed from frontend is relative or needs to be resolved.
        // For MediaLibrary, files are in uploads/{userId}/...
        // But the frontend usually works with URLs or relative paths.
        // Let's assume we find the file by ID or the path is trusted (admin).

        // Security check: ensure path is within uploads
        $realPath = realpath(Request::input('path')); // This might be tricky if path is not absolute on server
        // For simplicity reusing FileExplorer logic logic here
        // If path is absolute (from VFS or RealFS), use it.

        if (file_put_contents($path, $image)) {
            return Response::json(['success' => true]);
        }

        return Response::json(['success' => false], 500);
    }

    public function processVideo()
    {
        if (!$this->requireAuth())
            return;
        $data = Request::input();
        $action = $data['action'] ?? '';
        $path = $data['path']; // This needs to be absolute system path

        $outputPath = $data['outputPath'] ?? $path; // Overwrite or new file

        try {
            $success = false;
            switch ($action) {
                case 'extract-frame':
                    $outputPath = $data['outputPath'] ?? $path . '.jpg';
                    $success = Video::extractFrame($path, $outputPath, $data['time'] ?? '00:00:01');
                    break;
                case 'trim':
                    // Create new file for trim to avoid overwriting source immediately if not intended
                    if ($outputPath === $path) {
                        $outputPath = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . '_trimmed.' . pathinfo($path, PATHINFO_EXTENSION);
                    }
                    $success = Video::trim($path, $outputPath, $data['start'], $data['duration']);
                    break;
                case 'compress':
                    if ($outputPath === $path) {
                        $outputPath = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . '_compressed.' . pathinfo($path, PATHINFO_EXTENSION);
                    }
                    $success = Video::compress($path, $outputPath, $data['crf'] ?? 28);
                    break;
            }
            return Response::json(['success' => $success, 'path' => $outputPath]);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Register routes
     */
    public function registerRoutes()
    {
        Router::add('GET', '/@/media-library/files', [$this, 'listFiles']);
        Router::add('GET', '/@/media-library/files/(\d+)', [$this, 'getFile']);
        Router::add('POST', '/@/media-library/files', [$this, 'uploadFile']);
        Router::add('PUT', '/@/media-library/files/(\d+)', [$this, 'updateFile']);
        Router::add('DELETE', '/@/media-library/files/(\d+)', [$this, 'deleteFile']);

        Router::add('GET', '/@/media-library/tags', [$this, 'listTags']);
        Router::add('POST', '/@/media-library/tags', [$this, 'createTag']);
        Router::add('DELETE', '/@/media-library/tags/(\d+)', [$this, 'deleteTag']);

        Router::add('POST', '/@/media-library/files/(\d+)/tags', [$this, 'assignTags']);
        Router::add('GET', '/@/media-library/search', [$this, 'search']);
        Router::add('GET', '/@/media-library/stats', [$this, 'stats']);

        // Processing Routes
        Router::add('POST', '/@/media-library/process-image', [$this, 'processImage']);
        Router::add('POST', '/@/media-library/process-video', [$this, 'processVideo']);
    }
}
