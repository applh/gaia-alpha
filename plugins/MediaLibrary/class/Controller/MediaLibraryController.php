<?php

namespace MediaLibrary\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Media;
use GaiaAlpha\Env;
use MediaLibrary\Service\MediaLibraryService;

class MediaLibraryController extends BaseController
{
    private MediaLibraryService $service;
    private Media $media;

    public function init()
    {
        $this->service = new MediaLibraryService();
        $this->media = new Media(Env::get('path_data'));
    }

    /**
     * List all media files
     */
    public function listFiles()
    {
        $this->requireAuth();
        $userId = \GaiaAlpha\Session::id();

        $filters = [
            'tag' => Request::query('tag'),
            'search' => Request::query('search'),
            'limit' => Request::query('limit')
        ];

        $files = $this->service->getAllMedia($userId, array_filter($filters));
        Response::json($files);
    }

    /**
     * Get single file details
     */
    public function getFile($id)
    {
        $this->requireAuth();

        $file = $this->service->getMediaById((int) $id);
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
        $this->requireAuth();
        $userId = \GaiaAlpha\Session::id();

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
            $mediaId = $this->service->createMedia([
                'user_id' => $userId,
                'filename' => $result['filename'],
                'original_filename' => $file['name'],
                'mime_type' => $result['mime'],
                'file_size' => $file['size'],
                'width' => $width,
                'height' => $height
            ]);

            $media = $this->service->getMediaById($mediaId);
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
        $this->requireAuth();

        $data = Request::json();
        $success = $this->service->updateMedia((int) $id, $data);

        if ($success) {
            $file = $this->service->getMediaById((int) $id);
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
        $this->requireAuth();

        $success = $this->service->deleteMedia((int) $id);
        Response::json(['success' => $success]);
    }

    /**
     * List all tags
     */
    public function listTags()
    {
        $this->requireAuth();

        $tags = $this->service->getAllTags();
        Response::json($tags);
    }

    /**
     * Create new tag
     */
    public function createTag()
    {
        $this->requireAuth();

        $data = Request::json();
        if (empty($data['name'])) {
            Response::json(['error' => 'Tag name is required'], 400);
            return;
        }

        $tagId = $this->service->createTag(
            $data['name'],
            $data['color'] ?? '#6366f1'
        );

        $tags = $this->service->getAllTags();
        $tag = array_filter($tags, fn($t) => $t['id'] == $tagId);
        Response::json(reset($tag));
    }

    /**
     * Delete tag
     */
    public function deleteTag($id)
    {
        $this->requireAuth();

        $success = $this->service->deleteTag((int) $id);
        Response::json(['success' => $success]);
    }

    /**
     * Assign tags to file
     */
    public function assignTags($id)
    {
        $this->requireAuth();

        $data = Request::json();
        $tagIds = $data['tag_ids'] ?? [];

        $success = $this->service->assignTags((int) $id, $tagIds);

        if ($success) {
            $file = $this->service->getMediaById((int) $id);
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
        $this->requireAuth();
        $userId = \GaiaAlpha\Session::id();

        $query = Request::query('q');
        if (empty($query)) {
            Response::json(['error' => 'Search query is required'], 400);
            return;
        }

        $results = $this->service->searchMedia($query, $userId);
        Response::json($results);
    }

    /**
     * Get media library statistics
     */
    public function stats()
    {
        $this->requireAuth();
        $userId = \GaiaAlpha\Session::id();

        $stats = $this->service->getStats($userId);
        Response::json($stats);
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
    }
}
