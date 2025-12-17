<?php

namespace GaiaAlpha\Controller;

class FormController extends BaseController
{
    public function index()
    {
        // Auth Check (Any logged in user can have forms? Or just Admin? "Users can build" implies logged in users.)
        if (!\GaiaAlpha\Session::isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $userId = $_SESSION['user_id'];

        // Fetch user's forms
        $forms = \GaiaAlpha\Model\DB::fetchAll("SELECT * FROM forms WHERE user_id = ? ORDER BY created_at DESC", [\GaiaAlpha\Session::id()]);

        // Decode schema for convenience? Or leave as string.
        // Frontend might expect objects.
        foreach ($forms as &$form) {
            $form['schema'] = json_decode($form['schema']);
        }

        $this->jsonResponse($forms);
    }

    public function show($id)
    {
        if (!\GaiaAlpha\Session::isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $form = \GaiaAlpha\Model\DB::fetch("SELECT * FROM forms WHERE id = ? AND user_id = ?", [$id, \GaiaAlpha\Session::id()]);

        if (!$form) {
            $this->jsonResponse(['error' => 'Form not found'], 404);
            return;
        }

        // Ownership check
        if ($form['user_id'] != \GaiaAlpha\Session::id()) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $form['schema'] = json_decode($form['schema']);
        $this->jsonResponse($form);
    }

    public function store()
    {
        if (!\GaiaAlpha\Session::isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = $this->getJsonInput();

        if (empty($data['title'])) {
            $this->jsonResponse(['error' => 'Title is required'], 400);
            return;
        }

        $userId = \GaiaAlpha\Session::id();
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $submitLabel = $data['submit_label'] ?? 'Submit';
        $schema = isset($data['schema']) ? json_encode($data['schema']) : '[]';

        // Generate Slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        // Ensure uniqueness
        if (\GaiaAlpha\Model\DB::fetchColumn("SELECT COUNT(*) FROM forms WHERE slug = ?", [$slug]) > 0) {
            $slug .= '-' . time();
        }

        try {
            $sql = "INSERT INTO forms (user_id, title, slug, description, submit_label, schema) VALUES (?, ?, ?, ?, ?, ?)";
            \GaiaAlpha\Model\DB::execute($sql, [$userId, $title, $slug, $description, $submitLabel, $schema]);

            $this->jsonResponse([
                'success' => true,
                'id' => \GaiaAlpha\Model\DB::lastInsertId(),
                'slug' => $slug
            ]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => "Create failed: " . $e->getMessage()], 400);
        }
    }

    public function update($id)
    {
        if (!\GaiaAlpha\Session::isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = $this->getJsonInput();

        // Ownership Check
        $form = \GaiaAlpha\Model\DB::fetch("SELECT user_id FROM forms WHERE id = ?", [$id]);

        if (!$form || $form['user_id'] != \GaiaAlpha\Session::id()) {
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
            \GaiaAlpha\Model\DB::execute($sql, $values);
            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => "Update failed: " . $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        if (!\GaiaAlpha\Session::isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $form = \GaiaAlpha\Model\DB::fetch("SELECT user_id FROM forms WHERE id = ?", [$id]);

        if (!$form || $form['user_id'] != \GaiaAlpha\Session::id()) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        \GaiaAlpha\Model\DB::execute("DELETE FROM forms WHERE id = ? AND user_id = ?", [$id, \GaiaAlpha\Session::id()]);
        $this->jsonResponse(['success' => true]);
    }

    // Public Get Schema
    public function publicShow($slug)
    {
        $form = \GaiaAlpha\Model\DB::fetch("SELECT title, description, schema, submit_label FROM forms WHERE slug = ?", [$slug]);

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

        $form = \GaiaAlpha\Model\DB::fetch("SELECT id FROM forms WHERE slug = ?", [$slug]);

        if (!$form) {
            $this->jsonResponse(['error' => 'Form not found'], 404);
            return;
        }

        $formData = json_encode($data['data'] ?? []); // The user submitted values
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            \GaiaAlpha\Model\DB::execute(
                "INSERT INTO form_submissions (form_id, data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)",
                [$form['id'], $formData, $ip, $ua]
            );
            $this->jsonResponse(['success' => true]);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Submission failed'], 500);
        }
    }

    public function submissions($id)
    {
        if (!\GaiaAlpha\Session::isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $form = \GaiaAlpha\Model\DB::fetch("SELECT user_id FROM forms WHERE id = ?", [$id]);

        if (!$form || $form['user_id'] != \GaiaAlpha\Session::id()) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $submissions = \GaiaAlpha\Model\DB::fetchAll("SELECT * FROM form_submissions WHERE form_id = ? ORDER BY created_at DESC", [$id]);

        foreach ($submissions as &$sub) {
            $sub['data'] = json_decode($sub['data']);
        }

        $this->jsonResponse($submissions);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/forms', [$this, 'index']);
        \GaiaAlpha\Router::add('POST', '/@/forms', [$this, 'store']);
        \GaiaAlpha\Router::add('GET', '/@/forms/(\d+)', [$this, 'show']);
        \GaiaAlpha\Router::add('PUT', '/@/forms/(\d+)', [$this, 'update']);
        \GaiaAlpha\Router::add('DELETE', '/@/forms/(\d+)', [$this, 'destroy']);
        \GaiaAlpha\Router::add('GET', '/@/public/form/([\w-]+)', [$this, 'publicShow']);
        \GaiaAlpha\Router::add('POST', '/@/public/form/([\w-]+)', [$this, 'submit']);
        \GaiaAlpha\Router::add('GET', '/@/forms/(\d+)/submissions', [$this, 'submissions']);
    }
}
