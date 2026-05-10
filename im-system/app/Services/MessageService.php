<?php

namespace App\Services;

use App\Models\PrivateMessage;
use App\Models\GroupMessage;
use App\Models\GroupMember;
use App\Models\User;
use App\Utils\Redis;

class MessageService
{
    public static function sendPrivateMessage($fromUserId, $toUserId, $content, $messageType = 1)
    {
        $message = PrivateMessage::create([
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'content' => $content,
            'message_type' => $messageType,
        ]);
        
        CacheService::incrementUnreadPrivate($toUserId);
        CacheService::cacheRecentPrivate($fromUserId, $toUserId, $message->toArray());
        CacheService::cacheRecentPrivate($toUserId, $fromUserId, $message->toArray());
        
        return $message;
    }
    
    public static function sendGroupMessage($fromUserId, $groupId, $content, $messageType = 1)
    {
        if (!GroupMember::isMember($groupId, $fromUserId)) {
            return null;
        }
        
        $message = GroupMessage::create([
            'group_id' => $groupId,
            'user_id' => $fromUserId,
            'content' => $content,
            'message_type' => $messageType,
        ]);
        
        return $message;
    }
    
    public static function getUnreadCount($userId)
    {
        return CacheService::getUnreadPrivate($userId) ?: PrivateMessage::getUnreadCount($userId);
    }
}
