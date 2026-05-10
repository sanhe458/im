<?php

namespace App\Models;

use App\Utils\Database;

class PrivateMessage
{
    public int $id;
    public int $from_user_id;
    public int $to_user_id;
    public string $content;
    public int $message_type;
    public int $is_read;
    public string $created_at;
    
    const TYPE_TEXT = 1;
    const TYPE_IMAGE = 2;
    const TYPE_FILE = 3;
    
    public static function create($data)
    {
        $id = Database::insert('private_messages', [
            'from_user_id' => $data['from_user_id'],
            'to_user_id' => $data['to_user_id'],
            'content' => $data['content'],
            'message_type' => $data['message_type'] ?? self::TYPE_TEXT,
        ]);
        
        return self::findById($id);
    }
    
    public static function findById($id)
    {
        $row = Database::fetchOne("SELECT * FROM private_messages WHERE id = ?", [$id]);
        if (!$row) return null;
        
        $message = new self();
        foreach ($row as $key => $value) {
            $message->$key = $value;
        }
        return $message;
    }
    
    public static function getHistory($userId, $friendId, $limit = 20, $beforeId = null)
    {
        if ($beforeId) {
            return Database::fetchAll(
                "SELECT m.*, u.username, u.nickname, u.avatar 
                 FROM private_messages m 
                 JOIN users u ON m.from_user_id = u.id 
                 WHERE ((m.from_user_id = ? AND m.to_user_id = ?) OR (m.from_user_id = ? AND m.to_user_id = ?)) 
                 AND m.id < ? 
                 ORDER BY m.id DESC 
                 LIMIT ?",
                [$userId, $friendId, $friendId, $userId, $beforeId, $limit]
            );
        }
        
        return Database::fetchAll(
            "SELECT m.*, u.username, u.nickname, u.avatar 
             FROM private_messages m 
             JOIN users u ON m.from_user_id = u.id 
             WHERE (m.from_user_id = ? AND m.to_user_id = ?) OR (m.from_user_id = ? AND m.to_user_id = ?) 
             ORDER BY m.id DESC 
             LIMIT ?",
            [$userId, $friendId, $friendId, $userId, $limit]
        );
    }
    
    public static function markAsRead($userId, $fromUserId)
    {
        Database::update(
            'private_messages',
            ['is_read' => 1],
            'to_user_id = ? AND from_user_id = ? AND is_read = 0',
            [$userId, $fromUserId]
        );
        return true;
    }
    
    public static function getUnreadCount($userId)
    {
        $result = Database::fetchOne(
            "SELECT COUNT(*) as count FROM private_messages WHERE to_user_id = ? AND is_read = 0",
            [$userId]
        );
        return $result['count'];
    }
    
    public static function getConversations($userId)
    {
        $sql = "SELECT 
                    CASE 
                        WHEN from_user_id = ? THEN to_user_id 
                        ELSE from_user_id 
                    END as friend_id,
                    MAX(id) as last_message_id
                FROM private_messages 
                WHERE from_user_id = ? OR to_user_id = ?
                GROUP BY friend_id
                ORDER BY last_message_id DESC";
        
        return Database::fetchAll($sql, [$userId, $userId, $userId]);
    }
    
    public function toArray()
    {
        return [
            'id' => $this->id,
            'from_user_id' => $this->from_user_id,
            'to_user_id' => $this->to_user_id,
            'content' => $this->content,
            'message_type' => $this->message_type,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at,
        ];
    }
}
