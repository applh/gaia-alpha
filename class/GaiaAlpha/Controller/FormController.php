<?php

namespace GaiaAlpha\Controller;

class FormController extends BaseController
{
    public function index()
    {
        // Auth Check (Any logged in user can have forms? Or just Admin? "Users can build" implies logged in users.)
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $userId = $_SESSION['user_id'];
        $pdo = $this->db->getPdo();

        // Fetch user's forms
        $stmt = $pdo->prepare("SELECT * FROM forms WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $forms = $stmt->fetchAll();

        // Decode schema for convenience? Or leave as string.
        // Frontend might expect objects.
        foreach ($forms as &$form) {
            $form['schema'] = json_decode($form['schema']);
        }

        $this->jsonResponse($forms);
    }

    public function show($id)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ?");
        $stmt->execute([$id]);
        $form = $stmt->fetch();

        if (!$form) {
            $this->jsonResponse(['error' => 'Form not found'], 404);
            return;
        }

        // Ownership check
        if ($form['user_id'] != $_SESSION['user_id']) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $form['schema'] = json_decode($form['schema']);
        $this->jsonResponse($form);
    }

    public function store()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = $this->getJsonInput();

        if (empty($data['title'])) {
            $this->jsonResponse(['error' => 'Title is required'], 400);
            return;
        }

        $userId = $_SESSION['user_id'];
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $submitLabel = $data['submit_label'] ?? 'Submit';
        $schema = isset($data['schema']) ? json_encode($data['schema']) : '[]';

        // Generate Slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        // Ensure uniqueness
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM forms WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            $slug .= '-' . time();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO forms (user_id, title, slug, description, submit_label, schema) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $slug, $description, $submitLabel, $schema]);
            $this->jsonResponse(['success' => true, 'id' => $pdo->lastInsertId(), 'slug' => $slug]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => "Create failed: " . $e->getMessage()], 400);
        }
    }

    public function update($id)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = $this->getJsonInput();
        $pdo = $this->db->getPdo();

        // Ownership Check
        $stmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = ?");
        $stmt->execute([$id]);
        $form = $stmt->fetch();

        if (!$form || $form['user_id'] != $_SESSION['user_id']) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $fields = [];
        $values = [];

        if (isset($data['title'])) {
            $fields[] = "title = ?";
            $values[] = $data['title'];
        }
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $values[] = $data['description'];
        }
        if (isset($data['submit_label'])) {
            $fields[] = "submit_label = ?";
            $values[] = $data['submit_label'];
        }
        if (isset($data['schema'])) {
            $fields[] = "schema = ?";
            $values[] = json_encode($data['schema']);
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";

        if (empty($values)) {
            $this->jsonResponse(['success' => true]); // Nothing to update
            return;
        }

        $values[] = $id;
        $sql = "UPDATE forms SET " . implode(', ', $fields) . " WHERE id = ?";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => "Update failed: " . $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = ?");
        $stmt->execute([$id]);
        $form = $stmt->fetch();

        if (!$form || $form['user_id'] != $_SESSION['user_id']) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $pdo->prepare("DELETE FROM forms WHERE id = ?")->execute([$id]);
        $this->jsonResponse(['success' => true]);
    }

    // Public Get Schema
    public function publicShow($slug)
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT title, description, schema, submit_label FROM forms WHERE slug = ?");
        $stmt->execute([$slug]);
        $form = $stmt->fetch();

        if (!$form) {
            $this->jsonResponse(['error' => 'Form not found'], 404);
            return;
        }

        $form['schema'] = json_decode($form['schema']);
        $this->jsonResponse($form);
    }

    // Public Submission Endpoint
    public function submit($slug)
    {
        $data = $this->getJsonInput();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT id FROM forms WHERE slug = ?");
        $stmt->execute([$slug]);
        $form = $stmt->fetch();

        if (!$form) {
            $this->jsonResponse(['error' => 'Form not found'], 404);
            return;
        }

        $formData = json_encode($data['data'] ?? []); // The user submitted values
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, data, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $stmt->execute([$form['id'], $formData, $ip, $ua]);
            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Submission failed'], 500);
        }
    }

    public function submissions($id)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = ?");
        $stmt->execute([$id]);
        $form = $stmt->fetch();

        if (!$form || $form['user_id'] != $_SESSION['user_id']) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $stmt = $pdo->prepare("SELECT * FROM form_submissions WHERE form_id = ? ORDER BY submitted_at DESC");
        $stmt->execute([$id]);
        $submissions = $stmt->fetchAll();

        foreach ($submissions as &$sub) {
            $sub['data'] = json_decode($sub['data']);
        }

        $this->jsonResponse($submissions);
    }

    public function registerRoutes(\GaiaAlpha\Router $router)
    {
        $router->add('GET', '/api/forms', [$this, 'index']);
        $router->add('POST', '/api/forms', [$this, 'store']);
        $router->add('GET', '/api/forms/(\d+)', [$this, 'show']);
        $router->add('PUT', '/api/forms/(\d+)', [$this, 'update']);
        $router->add('DELETE', '/api/forms/(\d+)', [$this, 'destroy']);
        $router->add('GET', '/api/public/form/([\w-]+)', [$this, 'publicShow']);
        $router->add('POST', '/api/public/form/([\w-]+)', [$this, 'submit']);
        $router->add('GET', '/api/forms/(\d+)/submissions', [$this, 'submissions']);
    }
}
