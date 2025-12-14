<?php
namespace GaiaAlpha\Model;

use GaiaAlpha\Controller\DbController;
use PDO;

class Message extends BaseModel
{
    protected static $table = 'messages';
    protected static $fillable = ['sender_id', 'receiver_id', 'content', 'is_read'];

    public static function create($data)
    {
        $senderId = $data['sender_id'];
        $receiverId = $data['receiver_id'];
        $content = $data['content'];

        BaseModel::query(
            "INSERT INTO messages (sender_id, receiver_id, content, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)",
            [$senderId, $receiverId, $content]
        );

        return BaseModel::lastInsertId();
    }

    public static function getConversation($user1, $user2, $limit = 50, $offset = 0)
    {
        $db = DbController::getPdo();
        $sql = "SELECT m.*, 
                       u1.username as sender_name, 
                       u2.username as receiver_name 
                FROM messages m
                LEFT JOIN users u1 ON m.sender_id = u1.id
                LEFT JOIN users u2 ON m.receiver_id = u2.id
                WHERE (sender_id = ? AND receiver_id = ?) 
                   OR (sender_id = ? AND receiver_id = ?)
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";

        return array_reverse(BaseModel::fetchAll($sql, [$user1, $user2, $user2, $user1, $limit, $offset]));
    }

    public static function markAsRead($senderId, $receiverId)
    {
        $sql = "UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
        return BaseModel::execute($sql, [$senderId, $receiverId]) > 0;
    }

    public static function getUnreadCounts($userId)
    {
        $sql = "SELECT sender_id, COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0 GROUP BY sender_id";
        return BaseModel::fetchAll($sql, [$userId], PDO::FETCH_KEY_PAIR);
    }
}
