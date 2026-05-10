<?php

namespace App\Models;

use App\Utils\Database;

class Admin
{
    public int $id;
    public string $username;
    public string $password;
    public ?string $email;
    public int $role;
    public ?string $last_login_at;
    public string $created_at;
    
    const ROLE_ADMIN = 1;
    const ROLE_SUPER_ADMIN = 2;
    
    public static function findById($id)
    {
        $row = Database::fetchOne("SELECT * FROM admins WHERE id = ?", [$id]);
        if (!$row) return null;
        
        $admin = new self();
        foreach ($row as $key => $value) {
            $admin->$key = $value;
        }
        return $admin;
    }
    
    public static function findByUsername($username)
    {
        $row = Database::fetchOne("SELECT * FROM admins WHERE username = ?", [$username]);
        if (!$row) return null;
        
        $admin = new self();
        foreach ($row as $key => $value) {
            $admin->$key = $value;
        }
        return $admin;
    }
    
    public static function verifyPassword($username, $password)
    {
        $admin = self::findByUsername($username);
        if (!$admin) return false;
        
        if (!password_verify($password, $admin->password)) {
            return false;
        }
        
        return $admin;
    }
    
    public static function updateLastLogin($id)
    {
        Database::update('admins', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        return true;
    }
    
    public function toArray()
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
        ];
    }
}
