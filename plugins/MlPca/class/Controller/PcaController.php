<?php

namespace MlPca\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Router;
use MlPca\Service\PcaService;
use GaiaAlpha\Response;

class PcaController extends BaseController
{
    /**
     * Register routes for this controller.
     */
    public function registerRoutes()
    {
        // Route for performing the analysis
        Router::add('POST', '/@/ml-pca/analyze', [$this, 'analyze']);
    }

    /**
     * Handle PCA Analysis request
     * Input: JSON { "data": [[1,2], [3,4]...], "components": 2 }
     */
    public function analyze()
    {
        $this->requireAuth();

        // $input = $this->getJsonInput(); // BaseController does not have this helper
        // $input = $this->getJsonInput(); // BaseController does not have this helper
        $input = \GaiaAlpha\Request::input();
        $rawData = $input['data'] ?? [];
        $nComponents = (int) ($input['components'] ?? 2);

        if (empty($rawData)) {
            Response::json(['error' => 'No data provided'], 400);
        }

        // Validate structure - assume 2D array or parse CSV string? 
        // For simplicity, let's assume the frontend parses CSV to JSON array.
        if (!is_array($rawData)) {
            Response::json(['error' => 'Data must be an array'], 400);
        }

        try {
            $service = new PcaService();
            $result = $service->calculate($rawData, $nComponents);

            Response::json([
                'success' => true,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
