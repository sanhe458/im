<?php

namespace App\Models;

use App\Utils\Database;

class User
{
    public int $id;
    public string $username;
    public string $nickname;
    public string $email;
    public string $password;
    public ?string $avatar;
    public int $status;
    public ?string $last_login_at;
    public string $created_at;
    public string $updated_at;
    
    public static function findById($id)
    {
        $row = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$row) return null;
        
        $user = new self();
        foreach ($row as $key => $value) {
            $user->$key = $value;
        }
        return $user;
    }
    
    public static function findByUsername($username)
    {
        $row = Database::fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
        if (!$row) return null;
        
        $user = new self();
        foreach ($row as $key => $value) {
            $user->$key = $value;
        }
        return $user;
    }
    
    public static function findByEmail($email)
    {
        $row = Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        if (!$row) return null;
        
        $user = new self();
        foreach ($row as $key => $value) {
            $user->$key = $value;
        }
        return $user;
    }
    
    public static function create($data)
    {
        $id = Database::insert('users', [
            'username' => $data['username'],
            'nickname' => $data['nickname'] ?? $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'avatar' => $data['avatar'] ?? null,
            'status' => 1,
        ]);
        
        return self::findById($id);
    }
    
    public static function updateProfile($userId, $data)
    {
        $updateData = [];
        
        if (isset($data['nickname'])) {
            $updateData['nickname'] = $data['nickname'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (isset($data['avatar'])) {
            $updateData['avatar'] = $data['avatar'];
        }
        
        if (!empty($updateData)) {
            Database::update('users', $updateData, 'id = ?', [$userId]);
        }
        
        return self::findById($userId);
    }
    
    public static function updatePassword($userId, $newPassword)
    {
        Database::update('users', ['password' => password_hash($newPassword, PASSWORD_BCRYPT)], 'id = ?', [$userId]);
        return true;
    }
    
    public static function updateStatus($userId, $status)
    {
        Database::update('users', ['status' => $status], 'id = ?', [$userId]);
        return true;
    }
    
    public static function updateLastLogin($userId)
    {
        Database::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
        return true;
    }
    
    public static function delete($userId)
    {
        Database::delete('users', 'id = ?', [$userId]);
        return true;
    }
    
    public static function search($keyword, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;
        $keyword = "%{$keyword}%";
        
        $total = Database::fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE username LIKE ? OR nickname LIKE ? OR email LIKE ?",
            [$keyword, $keyword, $keyword]
        );
        
        $users = Database::fetchAll(
            "SELECT id, username, nickname, email, avatar, status, created_at FROM users WHERE username LIKE ? OR nickname LIKE ? OR email LIKE ? LIMIT ? OFFSET ?",
            [$keyword, $keyword, $keyword, $pageSize, $offset]
        );
        
        return [
            'list' => $users,
            'total' => $total['count'],
        ];
    }
    
    public static function paginate($page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;
        
        $total = Database::fetchOne("SELECT COUNT(*) as count FROM users");
        
        $users = Database::fetchAll(
            "SELECT id, username, nickname, email, avatar, status, last_login_at, created_at FROM users ORDER BY id DESC LIMIT ? OFFSET ?",
            [$pageSize, $offset]
        );
        
        return [
            'list' => $users,
            'total' => $total['count'],
        ];
    }
    
    public static function count()
    {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM users");
        return $result['count'];
    }
    
    public function toArray()
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
