<?php

namespace Comments;

use GaiaAlpha\Model\DB;

class CommentService
{

    public function getCommentsFunction($type, $id)
    {
        // Fetch all approved comments for this entity
        // Ordered by creation date. We'll handle nesting in PHP to keep SQL simple
        $sql = "SELECT c.*, u.username as user_username, u.avatar as user_avatar 
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.commentable_type = ?
                AND c.commentable_id = ? 
                AND c.status = 'approved'
                ORDER BY c.created_at ASC";

        $rows = DB::fetchAll($sql, [$type, $id]);

        return $this->buildTree($rows);
    }

    private function buildTree(array $elements, $parentId = null)
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['replies'] = $children;
                } else {
                    $element['replies'] = [];
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    public function addComment($userId, $data)
    {
        // Validation
        if (empty($data['content'])) {
            throw new \Exception("Content is required");
        }
        if (empty($data['commentable_type']) || empty($data['commentable_id'])) {
            throw new \Exception("Target entity is required");
        }

        // Prepare data
        $insertData = [
            'user_id' => $userId, // Can be null
            'commentable_type' => $data['commentable_type'],
            'commentable_id' => $data['commentable_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'content' => strip_tags($data['content']), // Basic sanitization
            'rating' => $data['rating'] ?? null,
            'status' => 'approved', // Default to approved for now, could be config driven
            'author_name' => $data['author_name'] ?? null,
            'author_email' => $data['author_email'] ?? null
        ];

        return Comment::create($insertData);
    }
}
