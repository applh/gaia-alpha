<?php

namespace GaiaAlpha;

class Framework
{
    public static function loadControllers()
    {
        $rootDir = Env::get('root_dir');
        // Dynamically Init Controllers
        $controllers = [];
        foreach (glob($rootDir . '/class/GaiaAlpha/Controller/*Controller.php') as $file) {
            $filename = basename($file, '.php');
            if ($filename === 'BaseController')
                continue;

            $key = strtolower(str_replace('Controller', '', $filename));
            $className = "GaiaAlpha\\Controller\\$filename";

            if (class_exists($className)) {
                $controller = new $className();
                if (method_exists($controller, 'init')) {
                    $controller->init();
                }
                $controllers[$key] = $controller;
            }
        }

        Env::set('controllers', $controllers);
    }

    public static function sortControllers()
    {
        $controllers = Env::get('controllers');

        uasort($controllers, function ($a, $b) {
            $rankA = method_exists($a, 'getRank') ? $a->getRank() : 10;
            $rankB = method_exists($b, 'getRank') ? $b->getRank() : 10;

            if ($rankA === $rankB) {
                return 0;
            }
            return ($rankA < $rankB) ? -1 : 1;
        });

        Env::set('controllers', $controllers);
    }

    public static function registerRoutes()
    {
        $controllers = Env::get('controllers');
        foreach ($controllers as $controller) {
            $controller->registerRoutes();
        }
    }
    public static function checkAuth($level = 0)
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        // Check if user is logged in (user_id exists) and has sufficient level
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['level']) || $_SESSION['level'] < $level) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        return true;
    }

    public static function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public static function decodeBody()
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
