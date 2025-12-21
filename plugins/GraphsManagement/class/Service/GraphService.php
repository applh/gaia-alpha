<?php

namespace GraphsManagement\Service;

use GaiaAlpha\Model\DB;
use GaiaAlpha\Session;

class GraphService
{
    /**
     * Create a new graph
     */
    public static function createGraph(array $data): array
    {
        $userId = Session::id();
        $now = date('Y-m-d H:i:s');

        // Validate required fields
        if (empty($data['title']) || empty($data['chart_type']) || empty($data['data_source_type'])) {
            throw new \Exception('Missing required fields: title, chart_type, data_source_type');
        }

        // Validate chart type
        $validChartTypes = ['line', 'bar', 'pie', 'area', 'scatter', 'doughnut', 'radar', 'polarArea'];
        if (!in_array($data['chart_type'], $validChartTypes)) {
            throw new \Exception('Invalid chart type');
        }

        // Validate data source type
        $validDataSourceTypes = ['manual', 'database', 'api'];
        if (!in_array($data['data_source_type'], $validDataSourceTypes)) {
            throw new \Exception('Invalid data source type');
        }

        // Validate data source config
        $dataSourceConfig = $data['data_source_config'] ?? '{}';
        if (is_array($dataSourceConfig)) {
            $dataSourceConfig = json_encode($dataSourceConfig);
        }

        if (!self::validateDataSourceConfig($data['data_source_type'], json_decode($dataSourceConfig, true))) {
            throw new \Exception('Invalid data source configuration');
        }

        $chartConfig = $data['chart_config'] ?? '{}';
        if (is_array($chartConfig)) {
            $chartConfig = json_encode($chartConfig);
        }

        DB::execute('
            INSERT INTO cms_graphs (user_id, title, description, chart_type, data_source_type, 
                                   data_source_config, chart_config, refresh_interval, is_public, 
                                   created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            $userId,
            $data['title'],
            $data['description'] ?? '',
            $data['chart_type'],
            $data['data_source_type'],
            $dataSourceConfig,
            $chartConfig,
            $data['refresh_interval'] ?? 0,
            $data['is_public'] ?? 0,
            $now,
            $now
        ]);

        $id = DB::lastInsertId();

        return self::getGraph($id);
    }

    /**
     * Update an existing graph
     */
    public static function updateGraph(int $id, array $data): bool
    {
        $userId = Session::id();
        $graph = self::getGraph($id);

        if (!$graph || $graph['user_id'] != $userId) {
            throw new \Exception('Graph not found or access denied');
        }

        $updates = [];
        $params = [];

        if (isset($data['title'])) {
            $updates[] = 'title = ?';
            $params[] = $data['title'];
        }

        if (isset($data['description'])) {
            $updates[] = 'description = ?';
            $params[] = $data['description'];
        }

        if (isset($data['chart_type'])) {
            $validChartTypes = ['line', 'bar', 'pie', 'area', 'scatter', 'doughnut', 'radar', 'polarArea'];
            if (!in_array($data['chart_type'], $validChartTypes)) {
                throw new \Exception('Invalid chart type');
            }
            $updates[] = 'chart_type = ?';
            $params[] = $data['chart_type'];
        }

        if (isset($data['data_source_config'])) {
            $config = is_array($data['data_source_config'])
                ? json_encode($data['data_source_config'])
                : $data['data_source_config'];
            $updates[] = 'data_source_config = ?';
            $params[] = $config;
        }

        if (isset($data['chart_config'])) {
            $config = is_array($data['chart_config'])
                ? json_encode($data['chart_config'])
                : $data['chart_config'];
            $updates[] = 'chart_config = ?';
            $params[] = $config;
        }

        if (isset($data['refresh_interval'])) {
            $updates[] = 'refresh_interval = ?';
            $params[] = (int) $data['refresh_interval'];
        }

        if (isset($data['is_public'])) {
            $updates[] = 'is_public = ?';
            $params[] = (int) $data['is_public'];
        }

        if (empty($updates)) {
            return true;
        }

        $updates[] = 'updated_at = ?';
        $params[] = date('Y-m-d H:i:s');
        $params[] = $id;

        DB::execute('UPDATE cms_graphs SET ' . implode(', ', $updates) . ' WHERE id = ?', $params);

        return true;
    }

    /**
     * Delete a graph
     */
    public static function deleteGraph(int $id): bool
    {
        $userId = Session::id();
        $graph = self::getGraph($id);

        if (!$graph || $graph['user_id'] != $userId) {
            throw new \Exception('Graph not found or access denied');
        }

        // Remove from collections
        $collections = DB::fetchAll('SELECT id, graph_ids FROM cms_graph_collections WHERE user_id = ?', [$userId]);

        foreach ($collections as $collection) {
            $graphIds = json_decode($collection['graph_ids'], true);
            if (in_array($id, $graphIds)) {
                $graphIds = array_values(array_filter($graphIds, fn($gid) => $gid != $id));
                DB::execute('UPDATE cms_graph_collections SET graph_ids = ? WHERE id = ?', [
                    json_encode($graphIds),
                    $collection['id']
                ]);
            }
        }

        // Delete graph
        DB::execute('DELETE FROM cms_graphs WHERE id = ?', [$id]);
        return true;
    }

    /**
     * Get a graph by ID
     */
    public static function getGraph(int $id): ?array
    {
        $graph = DB::fetch('SELECT * FROM cms_graphs WHERE id = ?', [$id]);

        if (!$graph) {
            return null;
        }

        // Parse JSON fields
        $graph['data_source_config'] = json_decode($graph['data_source_config'], true);
        $graph['chart_config'] = json_decode($graph['chart_config'], true);

        return $graph;
    }

    /**
     * List graphs for a user
     */
    public static function listGraphs(int $userId, array $filters = []): array
    {
        $where = ['user_id = ?'];
        $params = [$userId];

        if (!empty($filters['chart_type'])) {
            $where[] = 'chart_type = ?';
            $params[] = $filters['chart_type'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(title LIKE ? OR description LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql = 'SELECT * FROM cms_graphs WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';

        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        $graphs = DB::fetchAll($sql, $params);

        // Parse JSON fields
        foreach ($graphs as &$graph) {
            $graph['data_source_config'] = json_decode($graph['data_source_config'], true);
            $graph['chart_config'] = json_decode($graph['chart_config'], true);
        }

        return $graphs;
    }

    /**
     * Fetch graph data based on data source type
     */
    public static function fetchGraphData(int $graphId): array
    {
        $graph = self::getGraph($graphId);

        if (!$graph) {
            throw new \Exception('Graph not found');
        }

        $config = $graph['data_source_config'];

        switch ($graph['data_source_type']) {
            case 'manual':
                return self::executeManualDataSource($config);
            case 'database':
                return self::executeDatabaseQuery($config);
            case 'api':
                return self::executeApiRequest($config);
            default:
                throw new \Exception('Invalid data source type');
        }
    }

    /**
     * Execute manual data source
     */
    private static function executeManualDataSource(array $config): array
    {
        return $config;
    }

    /**
     * Execute database query
     */
    private static function executeDatabaseQuery(array $config): array
    {
        if (empty($config['query'])) {
            throw new \Exception('Database query is required');
        }

        $query = self::sanitizeSqlQuery($config['query']);

        $results = DB::fetchAll($query);

        // Convert database results to Chart.js format
        if (empty($results)) {
            return ['labels' => [], 'datasets' => []];
        }

        $labelColumn = $config['label_column'] ?? array_keys($results[0])[0];
        $valueColumn = $config['value_column'] ?? array_keys($results[0])[1] ?? array_keys($results[0])[0];

        $labels = [];
        $data = [];

        foreach ($results as $row) {
            $labels[] = $row[$labelColumn] ?? '';
            $data[] = $row[$valueColumn] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $config['dataset_label'] ?? 'Data',
                    'data' => $data
                ]
            ]
        ];
    }

    /**
     * Execute API request
     */
    private static function executeApiRequest(array $config): array
    {
        if (empty($config['url'])) {
            throw new \Exception('API URL is required');
        }

        $headers = [];
        if (!empty($config['headers'])) {
            foreach ($config['headers'] as $key => $value) {
                $headers[] = "$key: $value";
            }
        }

        $ch = curl_init($config['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("API request failed with status $httpCode");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from API');
        }

        // If data path is specified, navigate to it
        if (!empty($config['data_path'])) {
            $path = explode('.', $config['data_path']);
            foreach ($path as $key) {
                $data = $data[$key] ?? null;
                if ($data === null) {
                    throw new \Exception("Data path not found: {$config['data_path']}");
                }
            }
        }

        return $data;
    }

    /**
     * Validate data source configuration
     */
    private static function validateDataSourceConfig(string $type, ?array $config): bool
    {
        if ($config === null) {
            return false;
        }

        switch ($type) {
            case 'manual':
                return isset($config['labels']) || isset($config['datasets']);
            case 'database':
                return !empty($config['query']);
            case 'api':
                return !empty($config['url']);
            default:
                return false;
        }
    }

    /**
     * Sanitize SQL query to ensure it's SELECT only
     */
    private static function sanitizeSqlQuery(string $query): string
    {
        $query = trim($query);

        // Remove comments
        $query = preg_replace('/--.*$/m', '', $query);
        $query = preg_replace('/\/\*.*?\*\//s', '', $query);

        // Check if query starts with SELECT
        if (!preg_match('/^\s*SELECT\s+/i', $query)) {
            throw new \Exception('Only SELECT queries are allowed');
        }

        // Check for dangerous keywords
        $dangerous = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER', 'TRUNCATE', 'EXEC', 'EXECUTE'];
        foreach ($dangerous as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/i', $query)) {
                throw new \Exception("Keyword '$keyword' is not allowed in queries");
            }
        }

        return $query;
    }

    /**
     * Create a collection
     */
    public static function createCollection(array $data): array
    {
        $userId = Session::id();
        $now = date('Y-m-d H:i:s');

        if (empty($data['name'])) {
            throw new \Exception('Collection name is required');
        }

        $graphIds = $data['graph_ids'] ?? [];
        if (is_array($graphIds)) {
            $graphIds = json_encode($graphIds);
        }

        $layoutConfig = $data['layout_config'] ?? '{}';
        if (is_array($layoutConfig)) {
            $layoutConfig = json_encode($layoutConfig);
        }

        DB::execute('
            INSERT INTO cms_graph_collections (user_id, name, description, graph_ids, layout_config, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ', [
            $userId,
            $data['name'],
            $data['description'] ?? '',
            $graphIds,
            $layoutConfig,
            $now,
            $now
        ]);

        $id = DB::lastInsertId();

        return self::getCollection($id);
    }

    /**
     * Update a collection
     */
    public static function updateCollection(int $id, array $data): bool
    {
        $userId = Session::id();
        $collection = self::getCollection($id);

        if (!$collection || $collection['user_id'] != $userId) {
            throw new \Exception('Collection not found or access denied');
        }

        $updates = [];
        $params = [];

        if (isset($data['name'])) {
            $updates[] = 'name = ?';
            $params[] = $data['name'];
        }

        if (isset($data['description'])) {
            $updates[] = 'description = ?';
            $params[] = $data['description'];
        }

        if (isset($data['graph_ids'])) {
            $graphIds = is_array($data['graph_ids'])
                ? json_encode($data['graph_ids'])
                : $data['graph_ids'];
            $updates[] = 'graph_ids = ?';
            $params[] = $graphIds;
        }

        if (isset($data['layout_config'])) {
            $config = is_array($data['layout_config'])
                ? json_encode($data['layout_config'])
                : $data['layout_config'];
            $updates[] = 'layout_config = ?';
            $params[] = $config;
        }

        if (empty($updates)) {
            return true;
        }

        $updates[] = 'updated_at = ?';
        $params[] = date('Y-m-d H:i:s');
        $params[] = $id;

        DB::execute('UPDATE cms_graph_collections SET ' . implode(', ', $updates) . ' WHERE id = ?', $params);

        return true;
    }

    /**
     * Delete a collection
     */
    public static function deleteCollection(int $id): bool
    {
        $userId = Session::id();
        $collection = self::getCollection($id);

        if (!$collection || $collection['user_id'] != $userId) {
            throw new \Exception('Collection not found or access denied');
        }

        DB::execute('DELETE FROM cms_graph_collections WHERE id = ?', [$id]);
        return true;
    }

    /**
     * Get a collection by ID
     */
    public static function getCollection(int $id): ?array
    {
        $collection = DB::fetch('SELECT * FROM cms_graph_collections WHERE id = ?', [$id]);

        if (!$collection) {
            return null;
        }

        // Parse JSON fields
        $collection['graph_ids'] = json_decode($collection['graph_ids'], true);
        $collection['layout_config'] = json_decode($collection['layout_config'], true);

        // Load graphs
        $collection['graphs'] = [];
        foreach ($collection['graph_ids'] as $graphId) {
            $graph = self::getGraph($graphId);
            if ($graph) {
                $collection['graphs'][] = $graph;
            }
        }

        return $collection;
    }

    /**
     * List collections for a user
     */
    public static function listCollections(int $userId): array
    {
        $collections = DB::fetchAll('SELECT * FROM cms_graph_collections WHERE user_id = ? ORDER BY created_at DESC', [$userId]);

        // Parse JSON fields
        foreach ($collections as &$collection) {
            $collection['graph_ids'] = json_decode($collection['graph_ids'], true);
            $collection['layout_config'] = json_decode($collection['layout_config'], true);
        }

        return $collections;
    }
}
