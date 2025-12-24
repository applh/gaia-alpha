<?php

namespace Comments;

use GaiaAlpha\Request;
use GaiaAlpha\Response;
use GaiaAlpha\Session;

class CommentsController
{

    private $service;

    public function __construct()
    {
        $this->service = new CommentService();
    }

    public function index()
    {
        $type = Request::input('type');
        $id = Request::input('id');

        if (!$type || !$id) {
            return Response::json(['error' => 'Missing type or id parameters'], 400);
        }

        try {
            $comments = $this->service->getCommentsFunction($type, $id);
            return Response::json(['data' => $comments]);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        $data = Request::input();

        // If guest commenting is allowed, user might be null.
        $userId = Session::isLoggedIn() ? Session::id() : null;

        try {
            $newId = $this->service->addComment($userId, $data);
            $newComment = Comment::find($newId);
            return Response::json(['data' => $newComment], 201);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function update($id)
    {
        if (!Session::isLoggedIn()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        $userId = Session::id();
        $userRole = $_SESSION['user']['role'] ?? 'user';

        $comment = Comment::find($id);
        if (!$comment) {
            return Response::json(['error' => 'Comment not found'], 404);
        }

        // Authorization: Only author or admin
        if ($comment->user_id != $userId && $userRole !== 'admin') {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        $data = Request::input();
        // Only allow updating content/rating
        $updateData = [];
        if (isset($data['content']))
            $updateData['content'] = strip_tags($data['content']);
        if (isset($data['rating']))
            $updateData['rating'] = $data['rating'];

        try {
            Comment::update($id, $updateData);
            return Response::json(['data' => Comment::find($id)]);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        if (!Session::isLoggedIn()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        $userId = Session::id();
        $userRole = $_SESSION['user']['role'] ?? 'user';

        $comment = Comment::find($id);
        if (!$comment) {
            return Response::json(['error' => 'Comment not found'], 404);
        }

        // Authorization: Only author or admin
        if ($comment->user_id != $userId && $userRole !== 'admin') {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        try {
            Comment::delete($id);
            return Response::json(['success' => true]);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
