<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\Page;

class PublicController extends BaseController
{
    public function index()
    {
        $this->jsonResponse(Page::getLatestPublic());
    }

    public function show($slug)
    {
        $page = Page::findBySlug($slug);

        if (!$page) {
            $this->jsonResponse(['error' => 'Page not found'], 404);
        }

        $this->jsonResponse($page);
    }

    public function render($slug)
    {
        $page = Page::findBySlug($slug);

        if (!$page) {
            http_response_code(404);
            echo "Page not found";
            return;
        }

        // Check if content is JSON
        $content = $page['content'];
        $structure = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($structure)) {
            // Render Structure
            $html = "<!DOCTYPE html><html><head><title>" . htmlspecialchars($page['title']) . "</title>";
            $html .= "<style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; margin: 0; padding: 0; line-height: 1.6; }
                .site-header { padding: 20px; background: #eee; }
                .site-main { padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
                .site-footer { padding: 20px; background: #333; color: white; text-align: center; }
                .col-container { display: flex; gap: 20px; }
                .col { flex: 1; min-width: 0; }
                img { max-width: 100%; height: auto; }
            </style>";
            $html .= "</head><body>";

            if (isset($structure['header'])) {
                $html .= "<header class='site-header'>";
                foreach ($structure['header'] as $node)
                    $html .= $this->renderNode($node);
                $html .= "</header>";
            }

            if (isset($structure['main'])) {
                $html .= "<main class='site-main'>";
                foreach ($structure['main'] as $node)
                    $html .= $this->renderNode($node);
                $html .= "</main>";
            }

            if (isset($structure['footer'])) {
                $html .= "<footer class='site-footer'>";
                foreach ($structure['footer'] as $node)
                    $html .= $this->renderNode($node);
                $html .= "</footer>";
            }

            $html .= "</body></html>";
            echo $html;
        } else {
            // Render Raw HTML/Text
            echo $content;
        }
    }

    private function renderNode($node)
    {
        if (!isset($node['type']))
            return '';

        $type = $node['type'];
        $children = isset($node['children']) ? $node['children'] : [];
        $content = isset($node['content']) ? htmlspecialchars($node['content']) : '';

        // Use src for images
        $src = isset($node['src']) ? htmlspecialchars($node['src']) : '';

        $html = '';

        switch ($type) {
            case 'section':
                $html .= "<section>";
                foreach ($children as $child)
                    $html .= $this->renderNode($child);
                $html .= "</section>";
                break;
            case 'columns':
                $html .= "<div class='col-container'>";
                foreach ($children as $child)
                    $html .= $this->renderNode($child);
                $html .= "</div>";
                break;
            case 'column':
                $html .= "<div class='col'>";
                foreach ($children as $child)
                    $html .= $this->renderNode($child);
                $html .= "</div>";
                break;
            case 'div':
                $html .= "<div>";
                foreach ($children as $child)
                    $html .= $this->renderNode($child);
                $html .= "</div>";
                break;
            case 'h1':
                $html .= "<h1>{$content}</h1>";
                break;
            case 'h2':
                $html .= "<h2>{$content}</h2>";
                break;
            case 'h3':
                $html .= "<h3>{$content}</h3>";
                break;
            case 'p':
                $html .= "<p>{$content}</p>";
                break;
            case 'image':
                if ($src)
                    $html .= "<img src='{$src}' loading='lazy' />";
                else
                    $html .= "<div style='background:#eee; padding:20px; text-align:center'>Image Placeholder</div>";
                break;
            default:
                break;
        }

        return $html;
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/api/public/pages', [$this, 'index']);
        \GaiaAlpha\Router::add('GET', '/api/public/pages/([\w-]+)', [$this, 'show']);

        // Public HTML Views
        \GaiaAlpha\Router::add('GET', '/page/([\w-]+)', [$this, 'render']);
    }
}
