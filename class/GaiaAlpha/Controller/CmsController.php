<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\Page;
use GaiaAlpha\Model\Template;

class CmsController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        $cat = isset($_GET['cat']) ? $_GET['cat'] : 'page';
        $pageModel = new Page($this->db);
        $this->jsonResponse($pageModel->findAllByUserId($_SESSION['user_id'], $cat));
    }

    public function create()
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (empty($data['title']) || empty($data['slug'])) {
            $this->jsonResponse(['error' => 'Missing title or slug'], 400);
        }

        // Default cat to 'page' if not provided
        if (empty($data['cat'])) {
            $data['cat'] = 'page';
        }

        $pageModel = new Page($this->db);
        try {
            $id = $pageModel->create($_SESSION['user_id'], $data);
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Slug already exists'], 400);
        }
    }

    public function update($id)
    {
        $this->requireAuth();
        $data = $this->getJsonInput();
        $pageModel = new Page($this->db);

        $pageModel->update($id, $_SESSION['user_id'], $data);
        $this->jsonResponse(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAuth();
        $pageModel = new Page($this->db);
        $pageModel->delete($id, $_SESSION['user_id']);
        $this->jsonResponse(['success' => true]);
    }

    public function upload()
    {
        $this->requireAuth();
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'No image uploaded or upload error'], 400);
        }

        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedTypes)) {
            $this->jsonResponse(['error' => 'Invalid file type. Allowed: JPG, PNG, WEBP'], 400);
        }

        $userDir = (defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : \GaiaAlpha\Env::get('root_dir') . '/my-data') . '/uploads/' . $_SESSION['user_id'];
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        $filename = time() . '_' . bin2hex(random_bytes(4)) . '.webp';

        switch ($mime) {
            case 'image/jpeg':
                $src = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $src = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/webp':
                $src = imagecreatefromwebp($file['tmp_name']);
                break;
            default:
                $src = false;
        }

        if (!$src) {
            $this->jsonResponse(['error' => 'Failed to process image'], 400);
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $maxWidth = 3840;
        $maxHeight = 2160;

        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);
            $dst = imagecreatetruecolor($newWidth, $newHeight);

            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $src = $dst;
        }

        $outputPath = $userDir . '/' . $filename;
        imagewebp($src, $outputPath, 80);

        $mediaUrl = '/media/' . $_SESSION['user_id'] . '/' . $filename;

        // Auto-create a CMS page entry for this image locally
        // cat="image", title=filename, slug=uniqid, image=url
        $pageModel = new Page($this->db);
        $imageSlug = 'img-' . pathinfo($filename, PATHINFO_FILENAME);
        // Ensure slug uniqueness simple logic or just try/catch
        try {
            $pageModel->create($_SESSION['user_id'], [
                'title' => $file['name'],
                'slug' => $imageSlug,
                'cat' => 'image',
                'image' => $mediaUrl,
                'content' => '',
                'tag' => 'upload'
            ]);
        } catch (\Exception $e) {
            // If slug exists, try appending something random?
            // For now, let's just ignore if insertion fails, but we should probably try to succeed.
            // But main purpose is tracking.
            try {
                $pageModel->create($_SESSION['user_id'], [
                    'title' => $file['name'],
                    'slug' => $imageSlug . '-' . rand(1000, 9999),
                    'cat' => 'image',
                    'image' => $mediaUrl,
                    'content' => '',
                    'tag' => 'upload'
                ]);
            } catch (\Exception $ex) {
                // Ignore
            }
        }

        $this->jsonResponse(['url' => $mediaUrl]);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/api/cms/pages', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/api/cms/pages', [$this, 'create']);
        \GaiaAlpha\Router::add('PATCH', '/api/cms/pages/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', '/api/cms/pages/(\d+)', [$this, 'delete']);
        \GaiaAlpha\Router::add('POST', '/api/cms/upload', [$this, 'upload']);

        // Template Routes
        \GaiaAlpha\Router::add('GET', '/api/cms/templates', [$this, 'getTemplates']);
        \GaiaAlpha\Router::add('POST', '/api/cms/templates', [$this, 'createTemplate']);
        \GaiaAlpha\Router::add('PATCH', '/api/cms/templates/(\d+)', [$this, 'updateTemplate']);
        \GaiaAlpha\Router::add('DELETE', '/api/cms/templates/(\d+)', [$this, 'deleteTemplate']);
    }

    public function getTemplates()
    {
        $this->requireAuth();
        $templateModel = new Template($this->db);
        $this->jsonResponse($templateModel->findAllByUserId($_SESSION['user_id']));
    }

    public function createTemplate()
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (empty($data['title']) || empty($data['slug'])) {
            $this->jsonResponse(['error' => 'Missing title or slug'], 400);
        }

        $templateModel = new Template($this->db);
        try {
            $id = $templateModel->create($_SESSION['user_id'], $data);
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Slug already exists'], 400);
        }
    }

    public function updateTemplate($id)
    {
        $this->requireAuth();
        $data = $this->getJsonInput();
        $templateModel = new Template($this->db);

        $templateModel->update($id, $_SESSION['user_id'], $data);
        $this->jsonResponse(['success' => true]);
    }

    public function deleteTemplate($id)
    {
        $this->requireAuth();
        $templateModel = new Template($this->db);
        $templateModel->delete($id, $_SESSION['user_id']);
        $this->jsonResponse(['success' => true]);
    }
}
