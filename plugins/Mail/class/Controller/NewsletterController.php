<?php

namespace Mail\Controller;

use GaiaAlpha\Controller\BaseController;
use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Model\DB;
use Mail\Mail; // Use existing Mail facade/helper if available, or direct driver usage

class NewsletterController extends BaseController
{
    public function registerRoutes()
    {
        // Newsletter CRUD
        \GaiaAlpha\Router::get('/@/mail/newsletters', [$this, 'index']);
        \GaiaAlpha\Router::get('/@/mail/newsletters/(\d+)', [$this, 'get']);
        \GaiaAlpha\Router::post('/@/mail/newsletters', [$this, 'save']);
        \GaiaAlpha\Router::put('/@/mail/newsletters/(\d+)', [$this, 'save']); // Update
        \GaiaAlpha\Router::delete('/@/mail/newsletters/(\d+)', [$this, 'delete']);

        // Sending
        \GaiaAlpha\Router::post('/@/mail/newsletters/(\d+)/send', [$this, 'send']);

        // Subscribers & Lists (Basic implementation)
        \GaiaAlpha\Router::get('/@/mail/lists', [$this, 'getLists']);
        \GaiaAlpha\Router::post('/@/mail/lists', [$this, 'saveList']);
        \GaiaAlpha\Router::get('/@/mail/subscribers', [$this, 'getSubscribers']);
        \GaiaAlpha\Router::post('/@/mail/subscribers', [$this, 'saveSubscriber']); // Add single subscriber
    }

    public function index()
    {
        if (!$this->requireAuth())
            return;
        $newsletters = DB::fetchAll("SELECT * FROM newsletters ORDER BY created_at DESC");
        Response::json($newsletters);
    }

    public function get($id)
    {
        if (!$this->requireAuth())
            return;
        $newsletter = DB::fetch("SELECT * FROM newsletters WHERE id = ?", [$id]);
        if (!$newsletter) {
            Response::json(['error' => 'Newsletter not found'], 404);
            return;
        }
        Response::json($newsletter);
    }

    public function save($id = null)
    {
        if (!$this->requireAuth())
            return;

        $input = Request::input();

        if (empty($input['subject'])) {
            Response::json(['error' => 'Subject is required'], 400);
            return;
        }

        $data = [
            'subject' => $input['subject'],
            'content_md' => $input['content_md'] ?? '',
            'content_html' => $input['content_html'] ?? '',
            'status' => $input['status'] ?? 'draft'
        ];

        if ($id) {
            // Update
            $setParts = [];
            $params = [];
            foreach ($data as $key => $value) {
                $setParts[] = "$key = ?";
                $params[] = $value;
            }
            $params[] = $id; // For WHERE clause

            DB::query("UPDATE newsletters SET " . implode(', ', $setParts) . " WHERE id = ?", $params);
            $newsletterId = $id;
        } else {
            // create
            DB::insert('newsletters', $data);
            $newsletterId = DB::lastInsertId();
        }

        Response::json(['id' => $newsletterId, 'status' => 'success']);
    }

    public function delete($id)
    {
        if (!$this->requireAuth())
            return;
        DB::query("DELETE FROM newsletters WHERE id = ?", [$id]);
        Response::json(['status' => 'success']);
    }

    public function send($id)
    {
        if (!$this->requireAuth())
            return;

        $newsletter = DB::fetch("SELECT * FROM newsletters WHERE id = ?", [$id]);
        if (!$newsletter) {
            Response::json(['error' => 'Newsletter not found'], 404);
            return;
        }

        // Mock sending for now - in real implementation, this would loop through subscribers
        // For this task, we will just simulate success and update status.

        // TODO: Get subscribers from selected list(s)
        // $subscribers = ...

        // Update status
        DB::query("UPDATE newsletters SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE id = ?", [$id]);

        Response::json(['status' => 'success', 'message' => 'Newsletter Queued for Sending (Mock)']);
    }

    // --- Auxiliary Methods for Lists/Subscribers ---

    public function getLists()
    {
        if (!$this->requireAuth())
            return;
        $lists = DB::fetchAll("SELECT * FROM newsletter_lists ORDER BY name ASC");
        Response::json($lists);
    }

    public function saveList()
    {
        if (!$this->requireAuth())
            return;
        $input = Request::input();
        if (empty($input['name'])) {
            Response::json(['error' => 'Name is required'], 400);
            return;
        }
        DB::insert('newsletter_lists', ['name' => $input['name'], 'description' => $input['description'] ?? '']);
        Response::json(['status' => 'success']);
    }

    public function getSubscribers()
    {
        if (!$this->requireAuth())
            return;
        // Simple fetch all for now
        $subscribers = DB::fetchAll("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC LIMIT 100");
        Response::json($subscribers);
    }

    public function saveSubscriber()
    {
        if (!$this->requireAuth())
            return;
        $input = Request::input();

        if (empty($input['email'])) {
            Response::json(['error' => 'Email is required'], 400);
            return;
        }

        // Check exists
        $exists = DB::fetch("SELECT id FROM newsletter_subscribers WHERE email = ?", [$input['email']]);
        if ($exists) {
            Response::json(['error' => 'Subscriber already exists'], 400);
            return;
        }

        DB::insert('newsletter_subscribers', [
            'email' => $input['email'],
            'name' => $input['name'] ?? '',
            'status' => 'active'
        ]);

        Response::json(['status' => 'success']);
    }
}
