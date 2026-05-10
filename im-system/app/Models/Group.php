<?php

namespace App\Models;

use App\Utils\Database;

class Group
{
    public int $id;
    public string $name;
    public ?string $description;
    public ?string $avatar;
    public int $owner_id;
    public ?string $invite_code;
    public string $created_at;
    
    public static function findById($id)
    {
        $row = Database::fetchOne("SELECT * FROM `groups` WHERE id = ?", [$id]);
        if (!$row) return null;
        
        $group = new self();
        foreach ($row as $key => $value) {
            $group->$key = $value;
        }
        return $group;
    }
    
    public static function findByInviteCode($code)
    {
        $row = Database::fetchOne("SELECT * FROM `groups` WHERE invite_code = ?", [$code]);
        if (!$row) return null;
        
        $group = new self();
        foreach ($row as $key => $value) {
            $group->$key = $value;
        }
        return $group;
    }
    
    public static function create($data)
    {
        $inviteCode = substr(md5(uniqid(rand(), true)), 0, 8);
        
        $id = Database::insert('groups', [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'owner_id' => $data['owner_id'],
            'invite_code' => $inviteCode,
        ]);
        
        GroupMember::create($id, $data['owner_id'], GroupMember::ROLE_OWNER);
        
        return self::findById($id);
    }
    
    public static function update($id, $data)
    {
        $updateData = [];
        
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['description'])) $updateData['description'] = $data['description'];
        if (isset($data['avatar'])) $updateData['avatar'] = $data['avatar'];
        
        if (!empty($updateData)) {
            Database::update('groups', $updateData, 'id = ?', [$id]);
        }
        
        return self::findById($id);
    }
    
    public static function delete($id)
    {
        Database::delete('group_messages', 'group_id = ?', [$id]);
        Database::delete('group_members', 'group_id = ?', [$id]);
        Database::delete('groups', 'id = ?', [$id]);
        return true;
    }
    
    public static function getUserGroups($userId)
    {
        return Database::fetchAll(
            "SELECT g.*, gm.role 
             FROM `groups` g 
             JOIN group_members gm ON g.id = gm.group_id 
             WHERE gm.user_id = ? 
             ORDER BY g.created_at DESC",
            [$userId]
        );
    }
    
    public static function paginate($page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;
        
        $total = Database::fetchOne("SELECT COUNT(*) as count FROM `groups`");
        
        $groups = Database::fetchAll(
            "SELECT g.*, u.username as owner_name, 
                    (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
             FROM `groups` g
             JOIN users u ON g.owner_id = u.id
             ORDER BY g.id DESC 
             LIMIT ? OFFSET ?",
            [$pageSize, $offset]
        );
        
        return [
            'list' => $groups,
            'total' => $total['count'],
        ];
    }
    
    public static function count()
    {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM `groups`");
        return $result['count'];
    }
    
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'avatar' => $this->avatar,
            'owner_id' => $this->owner_id,
            'invite_code' => $this->invite_code,
            'created_at' => $this->created_at,
        ];
    }
}
