<?php

namespace App\Models;

use App\Utils\Database;

class GroupMessage
{
    public int $id;
    public int $group_id;
    public int $user_id;
    public string $content;
    public int $message_type;
    public string $created_at;
    
    const TYPE_TEXT = 1;
    const TYPE_IMAGE = 2;
    const TYPE_FILE = 3;
    
    public static function create($data)
    {
        $id = Database::insert('group_messages', [
            'group_id' => $data['group_id'],
            'user_id' => $data['user_id'],
            'content' => $data['content'],
            'message_type' => $data['message_type'] ?? self::TYPE_TEXT,
        ]);
        
        return self::findById($id);
    }
    
    public static function findById($id)
    {
        $row = Database::fetchOne("SELECT * FROM group_messages WHERE id = ?", [$id]);
        if (!$row) return null;
        
        $message = new self();
        foreach ($row as $key => $value) {
            $message->$key = $value;
        }
        return $message;
    }
    
    public static function getHistory($groupId, $limit = 50, $beforeId = null)
    {
        if ($beforeId) {
            return Database::fetchAll(
                "SELECT m.*, u.username, u.nickname, u.avatar 
                 FROM group_messages m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE m.group_id = ? AND m.id < ? 
                 ORDER BY m.id DESC 
                 LIMIT ?",
                [$groupId, $beforeId, $limit]
            );
        }
        
        return Database::fetchAll(
            "SELECT m.*, u.username, u.nickname, u.avatar 
             FROM group_messages m 
             JOIN users u ON m.user_id = u.id 
             WHERE m.group_id = ? 
             ORDER BY m.id DESC 
             LIMIT ?",
            [$groupId, $limit]
        );
    }
    
    public static function delete($id)
    {
        Database::delete('group_messages', 'id = ?', [$id]);
        return true;
    }
    
    public static function countToday()
    {
        $result = Database::fetchOne(
            "SELECT COUNT(*) as count FROM group_messages WHERE DATE(created_at) = CURDATE()"
        );
        return $result['count'];
    }
    
    public function toArray()
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'user_id' => $this->user_id,
            'content' => $this->content,
            'message_type' => $this->message_type,
            'created_at' => $this->created_at,
        ];
    }
}
