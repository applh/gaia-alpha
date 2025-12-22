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

class FileExplorerController extends BaseController
{
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
        $src = $data['src'];
        $dst = $data['dst'] ?? $src; // Overwrite if no dst provided

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
}
