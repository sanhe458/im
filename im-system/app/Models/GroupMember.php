<?php

namespace App\Models;

use App\Utils\Database;

class GroupMember
{
    public int $id;
    public int $group_id;
    public int $user_id;
    public int $role;
    public string $joined_at;
    
    const ROLE_MEMBER = 1;
    const ROLE_ADMIN = 2;
    const ROLE_OWNER = 3;
    
    public static function findById($id)
    {
        $row = Database::fetchOne("SELECT * FROM group_members WHERE id = ?", [$id]);
        if (!$row) return null;
        
        $member = new self();
        foreach ($row as $key => $value) {
            $member->$key = $value;
        }
        return $member;
    }
    
    public static function findMember($groupId, $userId)
    {
        $row = Database::fetchOne(
            "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?",
            [$groupId, $userId]
        );
        
        if (!$row) return null;
        
        $member = new self();
        foreach ($row as $key => $value) {
            $member->$key = $value;
        }
        return $member;
    }
    
    public static function create($groupId, $userId, $role = self::ROLE_MEMBER)
    {
        $id = Database::insert('group_members', [
            'group_id' => $groupId,
            'user_id' => $userId,
            'role' => $role,
        ]);
        
        return self::findById($id);
    }
    
    public static function updateRole($groupId, $userId, $role)
    {
        Database::update(
            'group_members',
            ['role' => $role],
            'group_id = ? AND user_id = ?',
            [$groupId, $userId]
        );
        return true;
    }
    
    public static function delete($groupId, $userId)
    {
        Database::delete(
            'group_members',
            'group_id = ? AND user_id = ?',
            [$groupId, $userId]
        );
        return true;
    }
    
    public static function getMembers($groupId)
    {
        return Database::fetchAll(
            "SELECT gm.*, u.username, u.nickname, u.avatar, u.status
             FROM group_members gm
             JOIN users u ON gm.user_id = u.id
             WHERE gm.group_id = ?
             ORDER BY gm.role DESC, gm.joined_at ASC",
            [$groupId]
        );
    }
    
    public static function isMember($groupId, $userId)
    {
        $row = Database::fetchOne(
            "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?",
            [$groupId, $userId]
        );
        return $row !== false;
    }
    
    public static function isAdmin($groupId, $userId)
    {
        $member = self::findMember($groupId, $userId);
        return $member && ($member->role === self::ROLE_ADMIN || $member->role === self::ROLE_OWNER);
    }
    
    public static function isOwner($groupId, $userId)
    {
        $member = self::findMember($groupId, $userId);
        return $member && $member->role === self::ROLE_OWNER;
    }
    
    public static function countMembers($groupId)
    {
        $result = Database::fetchOne(
            "SELECT COUNT(*) as count FROM group_members WHERE group_id = ?",
            [$groupId]
        );
        return $result['count'];
    }
}
