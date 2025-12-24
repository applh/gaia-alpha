<?php

namespace Comments;

use GaiaAlpha\Model\DB;

class Comment
{
    public $id;
    public $user_id;
    public $commentable_type;
    public $commentable_id;
    public $parent_id;
    public $content;
    public $rating;
    public $status;
    public $author_name;
    public $author_email;
    public $meta_data;
    public $created_at;
    public $updated_at;

    public static function create($data)
    {
        $sql = "INSERT INTO comments (
            user_id, commentable_type, commentable_id, parent_id,
            content, rating, status, author_name, author_email, meta_data
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?
        )";

        $params = [
            $data['user_id'] ?? null,
            $data['commentable_type'],
            $data['commentable_id'],
            $data['parent_id'] ?? null,
            $data['content'],
            $data['rating'] ?? null,
            $data['status'] ?? 'approved',
            $data['author_name'] ?? null,
            $data['author_email'] ?? null,
            isset($data['meta_data']) ? json_encode($data['meta_data']) : null
        ];

        DB::execute($sql, $params);
        return DB::lastInsertId();
    }

    public static function find($id)
    {
        $row = DB::fetch("SELECT * FROM comments WHERE id = ?", [$id]);
        if (!$row)
            return null;

        $comment = new self();
        foreach ($row as $key => $value) {
            $comment->$key = $value;
        }
        return $comment;
    }

    public static function update($id, $data)
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if ($key === 'meta_data' && is_array($value)) {
                $value = json_encode($value);
            }
            $fields[] = "$key = ?";
            $params[] = $value;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";

        if (empty($fields))
            return true;

        $params[] = $id;
        $sql = "UPDATE comments SET " . implode(', ', $fields) . " WHERE id = ?";

        return DB::execute($sql, $params);
    }

    public static function delete($id)
    {
        return DB::execute("DELETE FROM comments WHERE id = ?", [$id]);
    }
}
