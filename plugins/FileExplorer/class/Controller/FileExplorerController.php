<?php

namespace FileExplorer\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Env;
use GaiaAlpha\Media;
use GaiaAlpha\Session;
use FileExplorer\Service\FileExplorerService;
use FileExplorer\Service\VirtualFsService;
use GaiaAlpha\Video;

class FileExplorerController extends BaseController
{
    public function registerRoutes()
    {
        $router = new \GaiaAlpha\Router(); // Or just use static Router if preferred, but BaseController typically uses static methods or $this->router if available. 
        // Framework::registerRoutes calls $controller->registerRoutes(). 
        // The standard pattern uses \GaiaAlpha\Router::add or simply Router::get/post.

        \GaiaAlpha\Router::add('GET', '/@/file-explorer/list', [$this, 'list']);
        \GaiaAlpha\Router::add('GET', '/@/file-explorer/read', [$this, 'read']);
        \GaiaAlpha\Router::add('POST', '/@/file-explorer/write', [$this, 'write']);
        \GaiaAlpha\Router::add('POST', '/@/file-explorer/create', [$this, 'create']);
        \GaiaAlpha\Router::add('POST', '/@/file-explorer/delete', [$this, 'delete']);
        \GaiaAlpha\Router::add('POST', '/@/file-explorer/rename', [$this, 'rename']);
        \GaiaAlpha\Router::add('POST', '/@/file-explorer/move', [$this, 'move']);
        \GaiaAlpha\Router::add('POST', '/@/file-explorer/image-process', [$this, 'imageProcess']);
        \GaiaAlpha\Router::add('GET', '/@/file-explorer/preview', [$this, 'preview']);
        \GaiaAlpha\Router::add('GET', '/@/file-explorer/video-info', [$this, 'videoInfo']);
        \GaiaAlpha\Router::add('POST', '/@/file-explorer/video-process', [$this, 'videoProcess']);
        \GaiaAlpha\Router::add('GET', '/@/file-explorer/vfs', [$this, 'vfsList']);
        \GaiaAlpha\Router::add('POST', '/@/file-explorer/vfs', [$this, 'vfsCreate']);
    }

    public function list()
    {
        if (!$this->requireAuth())
            return;

        $type = Request::query('type', 'real');
        $path = Request::query('path', Env::get('root_dir'));
        $parentId = (int) Request::query('parentId', 0);
        $vfsDb = Request::query('vfsDb');

        if ($type === 'vfs' && $vfsDb) {
            VirtualFsService::connect($vfsDb);
            return Response::json(VirtualFsService::listItems($parentId));
        }

        return Response::json(FileExplorerService::listDirectory($path));
    }

    public function read()
    {
        if (!$this->requireAuth())
            return;

        $type = Request::query('type', 'real');
        $path = Request::query('path');
        $id = (int) Request::query('id');
        $vfsDb = Request::query('vfsDb');

        if ($type === 'vfs' && $vfsDb) {
            VirtualFsService::connect($vfsDb);
            return Response::json(VirtualFsService::getItem($id));
        }

        return Response::json([
            'content' => FileExplorerService::getFileContent($path)
        ]);
    }

    public function write()
    {
        if (!$this->requireAuth())
            return;

        $data = Request::input();
        $type = $data['type'] ?? 'real';
        $vfsDb = $data['vfsDb'] ?? null;

        if ($type === 'vfs' && $vfsDb) {
            VirtualFsService::connect($vfsDb);
            $success = VirtualFsService::updateItem($data['id'], ['content' => $data['content']]);
            return Response::json(['success' => $success]);
        }

        $success = FileExplorerService::saveFile($data['path'], $data['content']);
        return Response::json(['success' => $success]);
    }

    public function create()
    {
        if (!$this->requireAuth())
            return;

        $data = Request::input();
        $type = $data['type'] ?? 'real';
        $vfsDb = $data['vfsDb'] ?? null;

        if ($type === 'vfs' && $vfsDb) {
            VirtualFsService::connect($vfsDb);
            $id = VirtualFsService::createItem($data);
            return Response::json(['success' => $id > 0, 'id' => $id]);
        }

        if ($data['itemType'] === 'folder') {
            $success = FileExplorerService::createDirectory($data['path']);
        } else {
            $success = FileExplorerService::saveFile($data['path'], '');
        }

        return Response::json(['success' => $success]);
    }

    public function delete()
    {
        if (!$this->requireAuth())
            return;

        $data = Request::input();
        $type = $data['type'] ?? 'real';
        $vfsDb = $data['vfsDb'] ?? null;

        if ($type === 'vfs' && $vfsDb) {
            VirtualFsService::connect($vfsDb);
            $success = VirtualFsService::deleteItem($data['id']);
            return Response::json(['success' => $success]);
        }

        $success = FileExplorerService::deleteItem($data['path']);
        return Response::json(['success' => $success]);
    }

    public function rename()
    {
        if (!$this->requireAuth())
            return;

        $data = Request::input();
        $type = $data['type'] ?? 'real';
        $vfsDb = $data['vfsDb'] ?? null;

        if ($type === 'vfs' && $vfsDb) {
            VirtualFsService::connect($vfsDb);
            $success = VirtualFsService::updateItem($data['id'], ['name' => $data['newName']]);
            return Response::json(['success' => $success]);
        }

        $success = FileExplorerService::renameItem($data['oldPath'], $data['newPath']);
        return Response::json(['success' => $success]);
    }

    public function move()
    {
        if (!$this->requireAuth())
            return;

        $data = Request::input();
        $type = $data['type'] ?? 'real';
        $vfsDb = $data['vfsDb'] ?? null;

        if ($type === 'vfs' && $vfsDb) {
            VirtualFsService::connect($vfsDb);
            $success = VirtualFsService::moveItem($data['id'], $data['newParentId']);
            return Response::json(['success' => $success]);
        }

        $success = FileExplorerService::moveItem($data['source'], $data['target']);
        return Response::json(['success' => $success]);
    }

    public function imageProcess()
    {
        if (!$this->requireAuth())
            return;

        $data = Request::input();
        $path = $data['path'] ?? $data['src'] ?? null;
        $image = $data['image'] ?? null;

        if (!$path) {
            return Response::json(['error' => 'Missing path'], 400);
        }

        if ($image && strpos($image, 'data:image/') === 0) {
            // Handle base64 image data
            $parts = explode(',', $image);
            $content = base64_decode($parts[1]);
            FileExplorerService::saveFile($path, $content);
            return Response::json(['success' => true, 'path' => $path]);
        }

        $src = $data['src'] ?? $path;
        $dst = $data['dst'] ?? $src;

        $media = new Media(Env::get('path_data'));

        $media->processImage(
            $src,
            $dst,
            $data['width'] ?? 0,
            $data['height'] ?? 0,
            $data['quality'] ?? 80,
            $data['fit'] ?? 'contain',
            $data['rotate'] ?? 0,
            $data['flip'] ?? '',
            $data['filter'] ?? ''
        );

        return Response::json(['success' => true, 'path' => $dst]);
    }

    public function videoInfo()
    {
        if (!$this->requireAuth())
            return;
        $path = Request::query('path');
        try {
            return Response::json(Video::getInfo($path));
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function videoProcess()
    {
        if (!$this->requireAuth())
            return;
        $data = Request::input();
        $action = $data['action'] ?? '';
        $path = $data['path'];
        $outputPath = $data['outputPath'] ?? $path . '.processed.' . pathinfo($path, PATHINFO_EXTENSION);

        try {
            $success = false;
            switch ($action) {
                case 'extract-frame':
                    $outputPath = $data['outputPath'] ?? $path . '.jpg';
                    $success = Video::extractFrame($path, $outputPath, $data['time'] ?? '00:00:01');
                    break;
                case 'trim':
                    $success = Video::trim($path, $outputPath, $data['start'], $data['duration']);
                    break;
                case 'compress':
                    $success = Video::compress($path, $outputPath, $data['crf'] ?? 28);
                    break;
            }
            return Response::json(['success' => $success, 'path' => $outputPath]);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function vfsList()
    {
        if (!$this->requireAuth())
            return;
        return Response::json(VirtualFsService::getVfsList());
    }

    public function vfsCreate()
    {
        if (!$this->requireAuth())
            return;
        $data = Request::input();
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $data['name']);
        $path = Env::get('path_data') . '/vfs/' . $name . '.sqlite';

        if (file_exists($path)) {
            return Response::json(['success' => false, 'error' => 'VFS already exists']);
        }

        VirtualFsService::connect($path); // This creates it and schema
        return Response::json(['success' => true, 'name' => $name, 'path' => $path]);
    }

    public function preview()
    {
        if (!$this->requireAuth())
            return;

        $path = Request::query('path');
        $rootDir = Env::get('root_dir');

        // Security check: prevent directory traversal
        if (strpos($path, '..') !== false) {
            Response::send("Forbidden", 403);
            return;
        }

        // Handle relative paths from root
        $fullPath = $path;
        if (!str_starts_with($path, '/') && !preg_match('/^[a-zA-Z]:/', $path)) {
            $fullPath = $rootDir . '/' . $path;
        }

        if (!file_exists($fullPath)) {
            Response::send("File not found", 404);
            return;
        }

        $contentType = \GaiaAlpha\File::mimeType($fullPath);

        Response::clearBuffer();
        Response::header("Content-Type: $contentType", true);
        Response::header("Cache-Control: public, max-age=3600");
        Response::file($fullPath, true);
    }
}
