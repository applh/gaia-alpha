<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Response;
use GaiaAlpha\Scheduler;
use GaiaAlpha\Env;

use GaiaAlpha\Router;

class CronController extends BaseController
{
    public function registerRoutes()
    {
        // Token is alphanumeric
        Router::get('/cron/([a-zA-Z0-9]+)', [$this, 'run']);
    }

    public function run(string $token)
    {
        $secret = Env::get('ga_cron_secret'); // We'll need to ensure this env var is documented/set

        if (!$secret || $token !== $secret) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        // Capture output buffer to return logs
        ob_start();
        try {
            Scheduler::simpleRun();
            $output = ob_get_clean();
            return Response::json(['status' => 'success', 'output' => explode("\n", $output)]);
        } catch (\Exception $e) {
            ob_end_clean();
            return Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
