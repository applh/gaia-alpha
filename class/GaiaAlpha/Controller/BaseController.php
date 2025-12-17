<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Response;
use GaiaAlpha\Request;

abstract class BaseController
{



    protected function requireAuth()
    {
        if (!\GaiaAlpha\Session::isLoggedIn()) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
    }

    protected function requireAdmin()
    {
        $this->requireAuth();
        if (!\GaiaAlpha\Session::isAdmin()) {
            Response::json(['error' => 'Forbidden'], 403);
        }
    }

    public function registerRoutes()
    {
        // Override in subclasses
    }

    public function init()
    {
        // Override in subclasses
    }

    public function getRank()
    {
        return 10;
    }
}
