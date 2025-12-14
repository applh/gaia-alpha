<?php

namespace GaiaAlpha\Controller;

use GaiaAlpha\Model\User;
use GaiaAlpha\Model\Todo;
use GaiaAlpha\Model\Page;

class AdminController extends BaseController
{
    public function index()
    {
        $this->requireAdmin();
        $this->jsonResponse(User::findAll());
    }

    public function stats()
    {
        $this->requireAdmin();

        // Raw queries for forms as we don't have a model method for "count all" easily accessible without refactor
        // Actually FormController has no model usage, it uses raw SQL. So we do same here.
        // Raw queries for forms as we don't have a model method for "count all" easily accessible without refactor
        // Actually FormController has no model usage, it uses raw SQL. So we do same here.
        $formsCount = \GaiaAlpha\Model\BaseModel::query("SELECT COUNT(*) FROM forms")->fetchColumn();
        $subsCount = \GaiaAlpha\Model\BaseModel::query("SELECT COUNT(*) FROM form_submissions")->fetchColumn();
        $templatesCount = \GaiaAlpha\Model\BaseModel::query("SELECT COUNT(*) FROM cms_templates")->fetchColumn();

        $this->jsonResponse([
            'users' => User::count(),
            'todos' => Todo::count(),
            'pages' => Page::count('page'),
            'templates' => $templatesCount,
            'images' => Page::count('image'),
            'forms' => $formsCount,
            'submissions' => $subsCount,
            'datastore' => \GaiaAlpha\Model\BaseModel::query("SELECT COUNT(*) FROM data_store")->fetchColumn()
        ]);
    }

    public function create()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();

        if (empty($data['username']) || empty($data['password'])) {
            $this->jsonResponse(['error' => 'Missing username or password'], 400);
        }

        try {
            $id = User::create($data['username'], $data['password'], $data['level'] ?? 10);
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
    }

    public function update($id)
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();
        User::update($id, $data);
        $this->jsonResponse(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireAdmin();
        if ($id == $_SESSION['user_id']) {
            $this->jsonResponse(['error' => 'Cannot delete yourself'], 400);
        }

        User::delete($id);
        $this->jsonResponse(['success' => true]);
    }

    // Database Management Endpoints

    public function getTables()
    {
        $this->requireAdmin();
        // Get all tables from SQLite
        $stmt = \GaiaAlpha\Model\BaseModel::query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $this->jsonResponse(['tables' => $tables]);
    }

    public function getTableData($tableName)
    {
        $this->requireAdmin();

        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            $this->jsonResponse(['error' => 'Invalid table name'], 400);
            return;
        }

        // Get table schema
        // PRAGMA queries are safe to log? Yes.
        $schemaStmt = \GaiaAlpha\Model\BaseModel::query("PRAGMA table_info($tableName)");
        $schema = $schemaStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get table data
        $dataStmt = \GaiaAlpha\Model\BaseModel::query("SELECT * FROM $tableName LIMIT 100");
        $data = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->jsonResponse([
            'table' => $tableName,
            'schema' => $schema,
            'data' => $data,
            'count' => count($data)
        ]);
    }

    public function executeQuery()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();

        if (empty($data['query'])) {
            $this->jsonResponse(['error' => 'No query provided'], 400);
            return;
        }

        $query = trim($data['query']);
        $pdo = DbController::getPdo();

        try {
            // Determine if it's a SELECT query or a modification query
            $isSelect = stripos($query, 'SELECT') === 0;

            if ($isSelect) {
                $stmt = \GaiaAlpha\Model\BaseModel::query($query);
                $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $this->jsonResponse([
                    'success' => true,
                    'type' => 'select',
                    'results' => $results,
                    'count' => count($results)
                ]);
            } else {
                $stmt = \GaiaAlpha\Model\BaseModel::query($query);
                $affectedRows = $stmt->rowCount();
                $this->jsonResponse([
                    'success' => true,
                    'type' => 'modification',
                    'affected_rows' => $affectedRows
                ]);
            }
        } catch (\PDOException $e) {
            $this->jsonResponse([
                'error' => 'Query execution failed: ' . $e->getMessage()
            ], 400);
        }
    }

    public function createRecord($tableName)
    {
        $this->requireAdmin();

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            $this->jsonResponse(['error' => 'Invalid table name'], 400);
            return;
        }

        $data = $this->getJsonInput();
        $pdo = DbController::getPdo();

        try {
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $tableName,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            \GaiaAlpha\Model\BaseModel::query($sql, array_values($data));

            $this->jsonResponse([
                'success' => true,
                'id' => \GaiaAlpha\Model\BaseModel::lastInsertId()
            ]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Insert failed: ' . $e->getMessage()], 400);
        }
    }

    public function updateRecord($tableName, $id)
    {
        $this->requireAdmin();

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            $this->jsonResponse(['error' => 'Invalid table name'], 400);
            return;
        }

        $data = $this->getJsonInput();
        $pdo = DbController::getPdo();

        try {
            $setParts = [];
            $values = [];

            foreach ($data as $column => $value) {
                $setParts[] = "$column = ?";
                $values[] = $value;
            }
            $values[] = $id;

            $sql = sprintf(
                "UPDATE %s SET %s WHERE id = ?",
                $tableName,
                implode(', ', $setParts)
            );

            \GaiaAlpha\Model\BaseModel::query($sql, $values);

            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Update failed: ' . $e->getMessage()], 400);
        }
    }

    public function deleteRecord($tableName, $id)
    {
        $this->requireAdmin();

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            $this->jsonResponse(['error' => 'Invalid table name'], 400);
            return;
        }

        try {
            \GaiaAlpha\Model\BaseModel::query("DELETE FROM $tableName WHERE id = ?", [$id]);

            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Delete failed: ' . $e->getMessage()], 400);
        }
    }

    // Template Management

    public function getTemplates()
    {
        $this->requireAdmin();
        // Get DB Templates
        $dbTemplates = \GaiaAlpha\Model\Template::findAllByUserId($_SESSION['user_id']); // Assuming filtered by user or all? Model says findAllByUserId
        // Actually admins should see all? But model enforces user_id check. For now use Session User.

        // Get File Templates
        $fileTemplates = [];
        $files = glob(dirname(__DIR__, 3) . '/templates/*.php');
        foreach ($files as $file) {
            $slug = basename($file, '.php');
            // Skip if slug exists in DB (DB overrides file? or separate?)
            // Let's mark them as type='file'
            // But wait, checking usage of "template_slug" in PublicController, it prefers FILE.
            // So if file exists, it's used.
            // Use File logic for list
            $fileTemplates[] = [
                'id' => 'file_' . $slug,
                'title' => ucfirst(str_replace('_', ' ', $slug)) . ' (File)',
                'slug' => $slug,
                'content' => '', // Don't load content for list
                'type' => 'file',
                'created_at' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }

        // Merge. DB templates might shadow files if we change precedence.
        // For now, list both.
        // Transform DB templates to have type='db'
        $dbTemplsFormatted = array_map(function ($t) {
            $t['type'] = 'db';
            return $t;
        }, $dbTemplates);

        $this->jsonResponse(array_merge($dbTemplsFormatted, $fileTemplates));
    }

    public function getTemplate($id)
    {
        $this->requireAdmin();
        // If ID starts with file_, it's a file
        if (strpos($id, 'file_') === 0) {
            $slug = substr($id, 5);
            $path = dirname(__DIR__, 3) . '/templates/' . $slug . '.php';
            if (file_exists($path)) {
                $this->jsonResponse([
                    'slug' => $slug,
                    'title' => ucfirst(str_replace('_', ' ', $slug)),
                    'content' => file_get_contents($path),
                    'type' => 'file',
                    'readonly' => true // Prevent editing files for now for safety
                ]);
            } else {
                $this->jsonResponse(['error' => 'Template file not found'], 404);
            }
        } else {
            // DB Template
            // Use raw PDO or Template Model? Template Model doesn't have findById, only Slug.
            // But we can add findById or just use generic DB call.
            // Let's use simple query as we are in AdminController
            // Let's use simple query as we are in AdminController
            $stmt = \GaiaAlpha\Model\BaseModel::query("SELECT * FROM cms_templates WHERE id = ?", [$id]);
            $tmpl = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($tmpl) {
                $tmpl['type'] = 'db';
                $this->jsonResponse($tmpl);
            } else {
                $this->jsonResponse(['error' => 'Template not found'], 404);
            }
        }
    }

    public function createTemplate()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();

        if (empty($data['title']) || empty($data['slug'])) {
            $this->jsonResponse(['error' => 'Title and slug are required'], 400);
            return;
        }

        try {
            $id = \GaiaAlpha\Model\Template::create($_SESSION['user_id'], $data);
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Slug already exists'], 400);
        }
    }

    public function updateTemplate($id)
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();
        \GaiaAlpha\Model\Template::update($id, $_SESSION['user_id'], $data);
        $this->jsonResponse(['success' => true]);
    }

    public function deleteTemplate($id)
    {
        $this->requireAdmin();
        \GaiaAlpha\Model\Template::delete($id, $_SESSION['user_id']);
        $this->jsonResponse(['success' => true]);
    }

    // Partial Management

    public function getPartials()
    {
        $this->requireAdmin();
        $stmt = \GaiaAlpha\Model\BaseModel::query("SELECT * FROM cms_partials ORDER BY name ASC");
        $this->jsonResponse($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function createPartial()
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();
        if (empty($data['name'])) {
            $this->jsonResponse(['error' => 'Name is required'], 400);
            return;
        }

        try {
            \GaiaAlpha\Model\BaseModel::query(
                "INSERT INTO cms_partials (user_id, name, content, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$_SESSION['user_id'], $data['name'], $data['content'] ?? '']
            );
            $this->jsonResponse(['success' => true, 'id' => \GaiaAlpha\Model\BaseModel::lastInsertId()]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Name already exists'], 400);
        }
    }

    public function updatePartial($id)
    {
        $this->requireAdmin();
        $data = $this->getJsonInput();
        $pdo = \GaiaAlpha\Controller\DbController::getPdo();

        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $values[] = $data['name'];
        }
        if (isset($data['content'])) {
            $fields[] = "content = ?";
            $values[] = $data['content'];
        }

        if (empty($fields)) {
            $this->jsonResponse(['success' => true]);
            return;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $id;

        $sql = "UPDATE cms_partials SET " . implode(', ', $fields) . " WHERE id = ?";
        \GaiaAlpha\Model\BaseModel::query($sql, $values);
        $this->jsonResponse(['success' => true]);
    }

    public function deletePartial($id)
    {
        $this->requireAdmin();
        \GaiaAlpha\Model\BaseModel::query("DELETE FROM cms_partials WHERE id = ?", [$id]);
        $this->jsonResponse(['success' => true]);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/admin/users', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/admin/users', [$this, 'create']);
        \GaiaAlpha\Router::add('PATCH', '/@/admin/users/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', '/@/admin/users/(\d+)', [$this, 'delete']);
        \GaiaAlpha\Router::add('GET', '/@/admin/stats', [$this, 'stats']);

        // Database Management
        \GaiaAlpha\Router::add('GET', '/@/admin/db/tables', [$this, 'getTables']);
        \GaiaAlpha\Router::add('GET', '/@/admin/db/table/(\w+)', [$this, 'getTableData']);
        \GaiaAlpha\Router::add('POST', '/@/admin/db/query', [$this, 'executeQuery']);
        \GaiaAlpha\Router::add('POST', '/@/admin/db/table/(\w+)', [$this, 'createRecord']);
        \GaiaAlpha\Router::add('PATCH', '/@/admin/db/table/(\w+)/(\d+)', [$this, 'updateRecord']);
        \GaiaAlpha\Router::add('DELETE', '/@/admin/db/table/(\w+)/(\d+)', [$this, 'deleteRecord']);

        // Template Management
        \GaiaAlpha\Router::add('GET', '/@/cms/templates', [$this, 'getTemplates']);
        \GaiaAlpha\Router::add('POST', '/@/cms/templates', [$this, 'createTemplate']);
        \GaiaAlpha\Router::add('GET', '/@/cms/templates/(\w+)', [$this, 'getTemplate']); // accept id or file_slug
        \GaiaAlpha\Router::add('PATCH', '/@/cms/templates/(\d+)', [$this, 'updateTemplate']);
        \GaiaAlpha\Router::add('DELETE', '/@/cms/templates/(\d+)', [$this, 'deleteTemplate']);

        // Partial Management
        \GaiaAlpha\Router::add('GET', '/@/cms/partials', [$this, 'getPartials']);
        \GaiaAlpha\Router::add('POST', '/@/cms/partials', [$this, 'createPartial']);
        \GaiaAlpha\Router::add('PATCH', '/@/cms/partials/(\d+)', [$this, 'updatePartial']);
        \GaiaAlpha\Router::add('DELETE', '/@/cms/partials/(\d+)', [$this, 'deletePartial']);
    }
}
