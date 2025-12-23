<?php

namespace SocialNetworks\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use SocialNetworks\Service\SocialNetworksService;
use SocialNetworks\Model\SocialAccount;
use SocialNetworks\Model\SocialPost;

class SocialNetworksController extends BaseController
{
    private $service;

    public function __construct()
    {
        $this->service = new SocialNetworksService();
    }

    public function getAccounts()
    {
        if (!$this->requireAuth())
            return;
        $accounts = SocialAccount::findAll();
        Response::json($accounts);
    }

    public function publish()
    {
        if (!$this->requireAuth())
            return;
        $data = Request::input();

        if (empty($data['content']) || empty($data['account_ids'])) {
            Response::json(['error' => 'Content and account_ids are required'], 400);
            return;
        }

        $results = [];
        foreach ($data['account_ids'] as $accountId) {
            $results[] = $this->service->publishPost($accountId, $data['content'], $data['media_urls'] ?? []);
        }

        Response::json(['success' => true, 'results' => $results]);
    }

    public function getPosts()
    {
        if (!$this->requireAuth())
            return;
        $posts = SocialPost::findAll();
        Response::json($posts);
    }

    public function deleteAccount($id)
    {
        if (!$this->requireAdmin())
            return;
        SocialAccount::delete($id);
        Response::json(['success' => true]);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/social-networks/accounts', [$this, 'getAccounts']);
        \GaiaAlpha\Router::add('POST', '/@/social-networks/publish', [$this, 'publish']);
        \GaiaAlpha\Router::add('GET', '/@/social-networks/posts', [$this, 'getPosts']);
        \GaiaAlpha\Router::add('DELETE', '/@/social-networks/accounts/(\d+)', [$this, 'deleteAccount']);
    }
}
