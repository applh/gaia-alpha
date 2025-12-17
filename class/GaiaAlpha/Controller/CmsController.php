<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\Page;
use GaiaAlpha\Model\Template;

class CmsController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        $cat = \GaiaAlpha\Request::query('cat', 'page');
        $this->jsonResponse(Page::findAllByUserId(\GaiaAlpha\Session::id(), $cat));
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

        try {
            $id = Page::create(\GaiaAlpha\Session::id(), $data);
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Slug already exists'], 400);
        }
    }

    public function update($id)
    {
        $this->requireAuth();
        $data = $this->getJsonInput();
        Page::update($id, \GaiaAlpha\Session::id(), $data);
        $this->jsonResponse(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAuth();
        Page::delete($id, \GaiaAlpha\Session::id());
        $this->jsonResponse(['success' => true]);
    }

    public function upload()
    {
        $this->requireAuth();
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'No image uploaded or upload error'], 400);
        }

        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedTypes)) {
            $this->jsonResponse(['error' => 'Invalid file type. Allowed: JPG, PNG, WEBP, AVIF'], 400);
        }

        $userDir = (defined('GAIA_DATA_PATH') ? GAIA_DATA_PATH : \GaiaAlpha\Env::get('root_dir') . '/my-data') . '/uploads/' . \GaiaAlpha\Session::id();
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        $useAvif = function_exists('imageavif');
        $ext = $useAvif ? '.avif' : '.webp';
        $filename = time() . '_' . bin2hex(random_bytes(4)) . $ext;

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
            case 'image/avif':
                $src = imagecreatefromavif($file['tmp_name']);
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

        if ($useAvif) {
            imageavif($src, $outputPath, 80);
        } else {
            imagewebp($src, $outputPath, 80);
        }

        $mediaUrl = '/media/' . \GaiaAlpha\Session::id() . '/' . $filename;

        // Auto-create a CMS page entry for this image locally
        // cat="image", title=filename, slug=uniqid, image=url
        $imageSlug = 'img-' . pathinfo($filename, PATHINFO_FILENAME);
        // Ensure slug uniqueness simple logic or just try/catch
        try {
            Page::create(\GaiaAlpha\Session::id(), [
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
                Page::create(\GaiaAlpha\Session::id(), [
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
        \GaiaAlpha\Router::add('GET', '/@/cms/pages', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/cms/pages', [$this, 'create']);
        \GaiaAlpha\Router::add('PATCH', '/@/cms/pages/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', '/@/cms/pages/(\d+)', [$this, 'delete']);
        \GaiaAlpha\Router::add('POST', '/@/cms/upload', [$this, 'upload']);

        // Template Routes
        \GaiaAlpha\Router::add('GET', '/@/cms/templates', [$this, 'getTemplates']);
        \GaiaAlpha\Router::add('POST', '/@/cms/templates', [$this, 'createTemplate']);
        \GaiaAlpha\Router::add('PATCH', '/@/cms/templates/(\d+)', [$this, 'updateTemplate']);
        \GaiaAlpha\Router::add('DELETE', '/@/cms/templates/(\d+)', [$this, 'deleteTemplate']);
    }

    public function getTemplates()
    {
        $this->requireAuth();
        $this->jsonResponse(Template::findAllByUserId(\GaiaAlpha\Session::id()));
    }

    public function createTemplate()
    {
        $this->requireAuth();
        $data = $this->getJsonInput();

        if (empty($data['title']) || empty($data['slug'])) {
            $this->jsonResponse(['error' => 'Missing title or slug'], 400);
        }

        try {
            $id = Template::create(\GaiaAlpha\Session::id(), $data);
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Slug already exists'], 400);
        }
    }

    public function updateTemplate($id)
    {
        $this->requireAuth();
        $data = $this->getJsonInput();
        Template::update($id, \GaiaAlpha\Session::id(), $data);
        $this->jsonResponse(['success' => true]);
    }

    public function deleteTemplate($id)
    {
        $this->requireAuth();
        Template::delete($id, \GaiaAlpha\Session::id());
        $this->jsonResponse(['success' => true]);
    }

}
