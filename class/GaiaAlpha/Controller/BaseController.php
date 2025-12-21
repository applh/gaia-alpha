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
            return false;
        }
        return true;
    }

    protected function requireAdmin()
    {
        if (!$this->requireAuth()) {
            return false;
        }
        if (!\GaiaAlpha\Session::isAdmin()) {
            Response::json(['error' => 'Forbidden'], 403);
            return false;
        }
        return true;
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
