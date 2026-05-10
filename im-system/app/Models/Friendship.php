<?php

namespace App\Models;

use App\Utils\Database;

class Friendship
{
    public int $id;
    public int $user_id;
    public int $friend_id;
    public int $status;
    public string $created_at;
    
    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_REJECTED = 2;
    
    public static function findById($id)
    {
        $row = Database::fetchOne("SELECT * FROM friendships WHERE id = ?", [$id]);
        if (!$row) return null;
        
        $friendship = new self();
        foreach ($row as $key => $value) {
            $friendship->$key = $value;
        }
        return $friendship;
    }
    
    public static function findFriendship($userId, $friendId)
    {
        $row = Database::fetchOne(
            "SELECT * FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)",
            [$userId, $friendId, $friendId, $userId]
        );
        
        if (!$row) return null;
        
        $friendship = new self();
        foreach ($row as $key => $value) {
            $friendship->$key = $value;
        }
        return $friendship;
    }
    
    public static function create($userId, $friendId)
    {
        $id = Database::insert('friendships', [
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => self::STATUS_PENDING,
        ]);
        
        return self::findById($id);
    }
    
    public static function accept($id)
    {
        $friendship = self::findById($id);
        if (!$friendship) return false;
        
        Database::update('friendships', ['status' => self::STATUS_ACCEPTED], 'id = ?', [$id]);
        
        $reverseRow = Database::fetchOne(
            "SELECT id FROM friendships WHERE user_id = ? AND friend_id = ?",
            [$friendship->friend_id, $friendship->user_id]
        );
        
        if (!$reverseRow) {
            Database::insert('friendships', [
                'user_id' => $friendship->friend_id,
                'friend_id' => $friendship->user_id,
                'status' => self::STATUS_ACCEPTED,
            ]);
        }
        
        return true;
    }
    
    public static function reject($id)
    {
        Database::update('friendships', ['status' => self::STATUS_REJECTED], 'id = ?', [$id]);
        return true;
    }
    
    public static function delete($userId, $friendId)
    {
        Database::delete(
            'friendships',
            '(user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)',
            [$userId, $friendId, $friendId, $userId]
        );
        return true;
    }
    
    public static function getFriends($userId)
    {
        return Database::fetchAll(
            "SELECT u.id, u.username, u.nickname, u.email, u.avatar, u.status, f.created_at 
             FROM friendships f 
             JOIN users u ON f.friend_id = u.id 
             WHERE f.user_id = ? AND f.status = ?",
            [$userId, self::STATUS_ACCEPTED]
        );
    }
    
    public static function getPendingRequests($userId)
    {
        return Database::fetchAll(
            "SELECT f.id, f.created_at, u.id as user_id, u.username, u.nickname, u.avatar 
             FROM friendships f 
             JOIN users u ON f.user_id = u.id 
             WHERE f.friend_id = ? AND f.status = ?",
            [$userId, self::STATUS_PENDING]
        );
    }
    
    public static function isFriend($userId, $friendId)
    {
        $row = Database::fetchOne(
            "SELECT id FROM friendships WHERE user_id = ? AND friend_id = ? AND status = ?",
            [$userId, $friendId, self::STATUS_ACCEPTED]
        );
        return $row !== false;
    }
}
