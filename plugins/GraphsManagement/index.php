<?php

use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use GaiaAlpha\DataStore;
use GraphsManagement\Controller\GraphController;

// Register controller
Hook::add('framework_load_controllers_after', function ($controllers) {
    $controllers = Env::get('controllers');

    if (class_exists(GraphController::class)) {
        $controller = new GraphController();
        if (method_exists($controller, 'init')) {
            $controller->init();
        }
        $controllers['graphs'] = $controller;
        Env::set('controllers', $controllers);
    }
});

// Register UI component
\GaiaAlpha\UiManager::registerComponent('graphs_management', 'plugins/GraphsManagement/GraphsManagement.js', true);

// Inject menu item
Hook::add('auth_session_data', function ($data) {
    if (isset($data['user'])) {
        $data['user']['menu_items'][] = [
            'id' => 'grp-content',
            'label' => 'Content',
            'icon' => 'layout',
            'children' => [
                [
                    'label' => 'Graphs',
                    'view' => 'graphs_management',
                    'icon' => 'bar-chart-2'
                ]
            ]
        ];
    }
    return $data;
});

// Add Chart.js to import map
Hook::add('html_head', function ($html) {
    $chartJsPath = '/min/js/vendor/chart.js';
    $importMap = '<script type="importmap-shim">
    {
        "imports": {
            "chartjs": "' . $chartJsPath . '"
        }
    }
    </script>';

    return $html . $importMap;
});

// Register shortcode for embedding graphs
Hook::add('content_render', function ($content) {
    // Match [graph id="123"] or [graph id="123" width="800" height="400"]
    $content = preg_replace_callback('/\[graph\s+id="(\d+)"(?:\s+width="(\d+)")?(?:\s+height="(\d+)")?\]/', function ($matches) {
        $graphId = $matches[1];
        $width = $matches[2] ?? '100%';
        $height = $matches[3] ?? '400';

        // Generate unique canvas ID
        $canvasId = 'graph-' . $graphId . '-' . uniqid();

        // Return HTML with inline script to load and render chart
        return '<div class="graph-embed" style="width: ' . $width . '; height: ' . $height . 'px;">
            <canvas id="' . $canvasId . '"></canvas>
            <script type="module">
                import "/min/js/vendor/chart.js";
                
                (async () => {
                    try {
                        const res = await fetch("/@/graphs/' . $graphId . '/embed");
                        if (!res.ok) {
                            console.error("Failed to load graph data");
                            return;
                        }
                        
                        const { graph, data } = await res.json();
                        const ctx = document.getElementById("' . $canvasId . '").getContext("2d");
                        
                        new window.Chart(ctx, {
                            type: graph.chart_type,
                            data: data,
                            options: graph.chart_config || {}
                        });
                    } catch (err) {
                        console.error("Error rendering graph:", err);
                    }
                })();
            </script>
        </div>';
    }, $content);

    // Match [collection id="5"]
    $content = preg_replace_callback('/\[collection\s+id="(\d+)"\]/', function ($matches) {
        $collectionId = $matches[1];
        $containerId = 'collection-' . $collectionId . '-' . uniqid();

        return '<div class="collection-embed" id="' . $containerId . '">
            <script type="module">
                import "/min/js/vendor/chart.js";
                
                (async () => {
                    try {
                        const res = await fetch("/@/graphs/collections/' . $collectionId . '");
                        if (!res.ok) {
                            console.error("Failed to load collection data");
                            return;
                        }
                        
                        const collection = await res.json();
                        const container = document.getElementById("' . $containerId . '");
                        
                        container.innerHTML = "<h2>" + collection.name + "</h2>";
                        if (collection.description) {
                            container.innerHTML += "<p>" + collection.description + "</p>";
                        }
                        
                        const gridDiv = document.createElement("div");
                        gridDiv.style.display = "grid";
                        gridDiv.style.gridTemplateColumns = "repeat(auto-fit, minmax(400px, 1fr))";
                        gridDiv.style.gap = "20px";
                        container.appendChild(gridDiv);
                        
                        for (const graph of collection.graphs) {
                            const graphDiv = document.createElement("div");
                            graphDiv.style.padding = "20px";
                            graphDiv.style.border = "1px solid #ddd";
                            graphDiv.style.borderRadius = "8px";
                            
                            const title = document.createElement("h3");
                            title.textContent = graph.title;
                            graphDiv.appendChild(title);
                            
                            const canvas = document.createElement("canvas");
                            canvas.id = "graph-" + graph.id + "-" + Math.random().toString(36).substr(2, 9);
                            graphDiv.appendChild(canvas);
                            gridDiv.appendChild(graphDiv);
                            
                            const dataRes = await fetch("/@/graphs/" + graph.id + "/data");
                            const { data } = await dataRes.json();
                            
                            new window.Chart(canvas.getContext("2d"), {
                                type: graph.chart_type,
                                data: data,
                                options: graph.chart_config || {}
                            });
                        }
                    } catch (err) {
                        console.error("Error rendering collection:", err);
                    }
                })();
            </script>
        </div>';
    }, $content);

    return $content;
});

// Initialize database tables
// Note: Tables are created via schema.sql during plugin installation
