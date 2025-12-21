<?php

namespace Analytics\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;
use GaiaAlpha\Response;
use Analytics\Service\AnalyticsService;

class AnalyticsController extends BaseController
{
    public function registerRoutes()
    {
        Router::add('GET', '/@/analytics/stats', [$this, 'getStats']);
    }

    public function getStats()
    {
        $this->requireAdmin();
        $service = AnalyticsService::getInstance();
        $stats = $service->getStats();
        Response::json($stats);
    }
}
