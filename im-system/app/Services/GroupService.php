<?php

namespace App\Services;

use App\Models\GroupMember;
use App\Models\User;
use App\Utils\Token;
use App\Services\CacheService;

class GroupService
{
    public static function createGroup($userId, $name, $description = '', $avatar = null)
    {
        return \App\Models\Group::create([
            'name' => $name,
            'description' => $description,
            'avatar' => $avatar,
            'owner_id' => $userId,
        ]);
    }
    
    public static function joinGroup($userId, $groupId, $inviteCode = null)
    {
        $group = \App\Models\Group::findById($groupId);
        
        if (!$group) {
            return ['error' => 'Group not found'];
        }
        
        if ($group->invite_code && $inviteCode !== $group->invite_code) {
            return ['error' => 'Invalid invite code'];
        }
        
        if (GroupMember::isMember($groupId, $userId)) {
            return ['error' => 'Already a member'];
        }
        
        GroupMember::create($groupId, $userId, GroupMember::ROLE_MEMBER);
        
        return ['success' => true];
    }
    
    public static function leaveGroup($userId, $groupId)
    {
        if (GroupMember::isOwner($groupId, $userId)) {
            $memberCount = GroupMember::countMembers($groupId);
            if ($memberCount > 1) {
                return ['error' => 'Owner cannot leave'];
            }
            \App\Models\Group::delete($groupId);
        } else {
            GroupMember::delete($groupId, $userId);
        }
        
        return ['success' => true];
    }
    
    public static function isAdmin($groupId, $userId)
    {
        return GroupMember::isAdmin($groupId, $userId);
    }
    
    public static function isOwner($groupId, $userId)
    {
        return GroupMember::isOwner($groupId, $userId);
    }
}
