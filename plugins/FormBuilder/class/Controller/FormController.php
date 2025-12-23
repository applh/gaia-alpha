<?php

namespace FormBuilder\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Response;
use GaiaAlpha\Request;
use GaiaAlpha\Model\DB;
use GaiaAlpha\Session;
use GaiaAlpha\Router;

class FormController extends BaseController
{
    public function index()
    {
        if (!Session::isLoggedIn()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        $userId = $_SESSION['user_id'];
        $forms = DB::fetchAll("SELECT * FROM forms WHERE user_id = ? ORDER BY created_at DESC", [Session::id()]);

        foreach ($forms as &$form) {
            $form['schema'] = json_decode($form['schema']);
            $form['settings'] = json_decode($form['settings'] ?? '{}');
        }

        Response::json($forms);
    }

    public function show($id)
    {
        if (!Session::isLoggedIn()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        $form = DB::fetch("SELECT * FROM forms WHERE id = ? AND user_id = ?", [$id, Session::id()]);

        if (!$form) {
            Response::json(['error' => 'Form not found'], 404);
            return;
        }

        $form['schema'] = json_decode($form['schema']);
        $form['settings'] = json_decode($form['settings'] ?? '{}');
        Response::json($form);
    }

    public function store()
    {
        if (!Session::isLoggedIn()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = Request::input();

        if (empty($data['title'])) {
            Response::json(['error' => 'Title is required'], 400);
            return;
        }

        $userId = Session::id();
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $submitLabel = $data['submit_label'] ?? 'Submit';
        $schema = isset($data['schema']) ? json_encode($data['schema']) : '[]';
        $type = $data['type'] ?? 'form';
        $settings = isset($data['settings']) ? json_encode($data['settings']) : '{}';

        // Generate Slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        // Ensure uniqueness
        if (DB::fetchColumn("SELECT COUNT(*) FROM forms WHERE slug = ?", [$slug]) > 0) {
            $slug .= '-' . time();
        }

        try {
            $sql = "INSERT INTO forms (user_id, title, slug, description, submit_label, schema, type, settings) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            DB::execute($sql, [$userId, $title, $slug, $description, $submitLabel, $schema, $type, $settings]);

            Response::json([
                'success' => true,
                'id' => DB::lastInsertId(),
                'slug' => $slug
            ]);
        } catch (\PDOException $e) {
            Response::json(['error' => "Create failed: " . $e->getMessage()], 400);
            return;
        }
    }

    public function update($id)
    {
        if (!Session::isLoggedIn()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = Request::input();
        $form = DB::fetch("SELECT user_id FROM forms WHERE id = ?", [$id]);

        if (!$form || $form['user_id'] != Session::id()) {
            Response::json(['error' => 'Forbidden'], 403);
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
        if (isset($data['type'])) {
            $fields[] = "type = ?";
            $values[] = $data['type'];
        }
        if (isset($data['settings'])) {
            $fields[] = "settings = ?";
            $values[] = json_encode($data['settings']);
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";

        if (empty($values)) {
            Response::json(['success' => true]);
            return;
        }

        $values[] = $id;
        $sql = "UPDATE forms SET " . implode(', ', $fields) . " WHERE id = ?";

        try {
            DB::execute($sql, $values);
            Response::json(['success' => true]);
        } catch (\PDOException $e) {
            Response::json(['error' => "Update failed: " . $e->getMessage()], 400);
            return;
        }
    }

    public function destroy($id)
    {
        if (!Session::isLoggedIn()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        $form = DB::fetch("SELECT user_id FROM forms WHERE id = ?", [$id]);

        if (!$form || $form['user_id'] != Session::id()) {
            Response::json(['error' => 'Forbidden'], 403);
            return;
        }

        DB::execute("DELETE FROM forms WHERE id = ? AND user_id = ?", [$id, Session::id()]);
        Response::json(['success' => true]);
    }

    // Public Get Schema
    public function publicShow($slug)
    {
        $form = DB::fetch("SELECT * FROM forms WHERE slug = ?", [$slug]);

        if (!$form) {
            Response::json(['error' => 'Form not found'], 404);
            return;
        }

        $form['schema'] = json_decode($form['schema']);
        $form['settings'] = json_decode($form['settings'] ?? '{}');

        // Hide correct answers if it's a quiz, unless we want to leak them? 
        // For security, strict quizzes should run validation on server, but we need to supply options.
        // We should strip 'correctAnswer' from the schema sent to client if it exists.
        if ($form['type'] === 'quiz') {
            foreach ($form['schema'] as &$field) {
                if (isset($field->correctAnswer)) {
                    unset($field->correctAnswer); // Don't send correct answer to client
                    // Actually $field is an object or array? json_decode defaults to objects usually unless 2nd arg is true.
                    // The original code didn't specify, likely objects.
                    // But here I'm modifying it. Let's ensure consistency.
                }
                if (is_array($field) && isset($field['correctAnswer'])) {
                    unset($field['correctAnswer']);
                }
            }
        }

        Response::json($form);
    }

    // Public Submission Endpoint
    public function submit($slug)
    {
        $data = Request::input();
        $form = DB::fetch("SELECT * FROM forms WHERE slug = ?", [$slug]);

        if (!$form) {
            Response::json(['error' => 'Form not found'], 404);
            return;
        }

        $submissionData = $data['data'] ?? [];
        $ip = Request::ip();
        $ua = Request::userAgent();

        $score = null;
        $metadata = [];

        if ($form['type'] === 'quiz') {
            $schema = json_decode($form['schema'], true); // Assoc array
            $calc = $this->calculateScore($schema, $submissionData);
            $score = $calc['score'];
            $metadata['results'] = $calc['results'];
            $metadata['totalPoints'] = $calc['totalPoints'];
        }

        try {
            DB::execute(
                "INSERT INTO form_submissions (form_id, data, ip_address, user_agent, created_at, score, metadata) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)",
                [$form['id'], json_encode($submissionData), $ip, $ua, $score, json_encode($metadata)]
            );

            $response = ['success' => true];
            if ($form['type'] === 'quiz') {
                $response['score'] = $score;
                $response['total'] = $metadata['totalPoints'];
                $response['results'] = $metadata['results']; // Detailed breakdown
            }
            // For poll, we might want to return updated stats
            if ($form['type'] === 'poll') {
                // Return minimal stats? Or let the frontend fetch?
                // Let's just return success for now.
            }

            Response::json($response);

        } catch (\PDOException $e) {
            Response::json(['error' => 'Submission failed'], 500);
            return;
        }
    }

    private function calculateScore($schema, $submission)
    {
        $score = 0;
        $totalPoints = 0;
        $results = [];

        foreach ($schema as $field) {
            // Only score fields that have points and a correct answer
            if (isset($field['points']) && isset($field['correctAnswer'])) {
                $points = intval($field['points']);
                $totalPoints += $points;

                $key = $field['key'] ?? '';
                $userAnswer = $submission[$key] ?? null;
                $correctAnswer = $field['correctAnswer'];

                $isCorrect = false;
                if (is_array($correctAnswer)) {
                    // Multi-select or something? Simple equality for now
                    $isCorrect = ($userAnswer == $correctAnswer);
                } else {
                    $isCorrect = ($userAnswer == $correctAnswer);
                }

                if ($isCorrect) {
                    $score += $points;
                }

                $results[$key] = [
                    'correct' => $isCorrect,
                    'userAnswer' => $userAnswer,
                    'correctAnswer' => $correctAnswer, // Send back so user sees it? Maybe configurable.
                    'points' => $isCorrect ? $points : 0
                ];
            }
        }

        return ['score' => $score, 'totalPoints' => $totalPoints, 'results' => $results];
    }

    public function submissions($id)
    {
        if (!Session::isLoggedIn()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        $form = DB::fetch("SELECT user_id FROM forms WHERE id = ?", [$id]);

        if (!$form || $form['user_id'] != Session::id()) {
            Response::json(['error' => 'Forbidden'], 403);
            return;
        }

        $submissions = DB::fetchAll("SELECT * FROM form_submissions WHERE form_id = ? ORDER BY created_at DESC", [$id]);

        foreach ($submissions as &$sub) {
            $sub['data'] = json_decode($sub['data']);
            $sub['metadata'] = json_decode($sub['metadata'] ?? '{}');
        }

        Response::json($submissions);
    }

    public function stats($id)
    {
        if (!Session::isLoggedIn()) {
            Response::json(['error' => 'Unauthorized'], 401);
            return;
        }

        $form = DB::fetch("SELECT * FROM forms WHERE id = ? AND user_id = ?", [$id, Session::id()]);
        if (!$form) {
            Response::json(['error' => 'Not found'], 404);
            return;
        }

        // Aggregate Data
        $totalSubmissions = DB::fetchColumn("SELECT COUNT(*) FROM form_submissions WHERE form_id = ?", [$id]);
        $avgScore = null;
        if ($form['type'] === 'quiz') {
            $avgScore = DB::fetchColumn("SELECT AVG(score) FROM form_submissions WHERE form_id = ?", [$id]);
        }

        // Distribution of answers for categorical fields (radio, select, checkbox)
        $schema = json_decode($form['schema'], true);
        $distribution = [];

        $submissions = DB::fetchAll("SELECT data FROM form_submissions WHERE form_id = ?", [$id]);

        foreach ($schema as $field) {
            if (in_array($field['type'], ['select', 'radio', 'checkbox'])) {
                $key = $field['key'];
                $counts = [];
                // Initialize with options if available
                if (isset($field['options'])) {
                    foreach ($field['options'] as $opt) {
                        $counts[$opt] = 0;
                    }
                }

                foreach ($submissions as $sub) {
                    $data = json_decode($sub['data'], true);
                    $val = $data[$key] ?? null;
                    if ($val) {
                        if (is_array($val)) { // Checkbox might be array
                            foreach ($val as $v) {
                                if (!isset($counts[$v]))
                                    $counts[$v] = 0;
                                $counts[$v]++;
                            }
                        } else {
                            if (!isset($counts[$val]))
                                $counts[$val] = 0;
                            $counts[$val]++;
                        }
                    }
                }
                $distribution[$key] = [
                    'label' => $field['label'],
                    'counts' => $counts
                ];
            }
        }

        Response::json([
            'total' => $totalSubmissions,
            'avgScore' => $avgScore,
            'distribution' => $distribution
        ]);
    }

    public function registerRoutes()
    {
        Router::add('GET', '/@/forms', [$this, 'index']);
        Router::add('POST', '/@/forms', [$this, 'store']);
        Router::add('GET', '/@/forms/(\d+)', [$this, 'show']);
        Router::add('PUT', '/@/forms/(\d+)', [$this, 'update']);
        Router::add('DELETE', '/@/forms/(\d+)', [$this, 'destroy']);

        Router::add('GET', '/@/public/form/([\w-]+)', [$this, 'publicShow']);
        Router::add('POST', '/@/public/form/([\w-]+)', [$this, 'submit']);

        Router::add('GET', '/@/forms/(\d+)/submissions', [$this, 'submissions']);
        Router::add('GET', '/@/forms/(\d+)/stats', [$this, 'stats']);
    }
}
