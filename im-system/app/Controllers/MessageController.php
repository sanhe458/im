<?php

namespace App\Controllers;

use App\Models\PrivateMessage;
use App\Models\User;
use App\Services\CacheService;
use App\Utils\Response;

class MessageController
{
    public function sendPrivate()
    {
        $currentUser = $_GET['current_user'];
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['to_user_id']) || empty($data['content'])) {
            Response::error('to_user_id and content are required', 400, 'VALIDATION_ERROR');
        }
        
        $toUserId = (int)$data['to_user_id'];
        
        if ($toUserId === $currentUser->id) {
            Response::error('Cannot send message to yourself', 400, 'INVALID_REQUEST');
        }
        
        $toUser = User::findById($toUserId);
        if (!$toUser) {
            Response::error('Recipient not found', 404, 'NOT_FOUND');
        }
        
        $message = PrivateMessage::create([
            'from_user_id' => $currentUser->id,
            'to_user_id' => $toUserId,
            'content' => htmlspecialchars($data['content'], ENT_QUOTES, 'UTF-8'),
            'message_type' => $data['message_type'] ?? PrivateMessage::TYPE_TEXT,
        ]);
        
        CacheService::incrementUnreadPrivate($toUserId);
        CacheService::cacheRecentPrivate($currentUser->id, $toUserId, $message->toArray());
        CacheService::cacheRecentPrivate($toUserId, $currentUser->id, $message->toArray());
        
        $messageData = array_merge($message->toArray(), [
            'from_user' => [
                'id' => $currentUser->id,
                'username' => $currentUser->username,
                'nickname' => $currentUser->nickname,
                'avatar' => $currentUser->avatar,
            ],
        ]);
        
        Response::success([
            'id' => $message->id,
            'message' => $messageData,
        ], 'Message sent');
    }
    
    public function getHistory($userId)
    {
        $currentUser = $_GET['current_user'];
        $userId = (int)$userId;
        
        $user = User::findById($userId);
        if (!$user) {
            Response::error('User not found', 404, 'NOT_FOUND');
        }
        
        $limit = (int)($_GET['limit'] ?? 20);
        $beforeId = isset($_GET['before_id']) ? (int)$_GET['before_id'] : null;
        
        $messages = PrivateMessage::getHistory($currentUser->id, $userId, $limit, $beforeId);
        
        $messages = array_map(function($msg) {
            $msg['is_mine'] = $msg['from_user_id'] === $_GET['current_user']->id;
            return $msg;
        }, $messages);
        
        $messages = array_reverse($messages);
        
        Response::success($messages);
    }
    
    public function markRead($userId)
    {
        $currentUser = $_GET['current_user'];
        $userId = (int)$userId;
        
        PrivateMessage::markAsRead($currentUser->id, $userId);
        CacheService::clearUnreadPrivate($currentUser->id);
        
        Response::success(null, 'Messages marked as read');
    }
    
    public function getUnread()
    {
        $currentUser = $_GET['current_user'];
        
        $count = CacheService::getUnreadPrivate($currentUser->id);
        
        if ($count === 0) {
            $count = PrivateMessage::getUnreadCount($currentUser->id);
        }
        
        Response::success(['unread_count' => $count]);
    }
    
    public function getConversations()
    {
        $currentUser = $_GET['current_user'];
        
        $conversations = PrivateMessage::getConversations($currentUser->id);
        
        $result = [];
        foreach ($conversations as $conv) {
            $friendId = $conv['friend_id'];
            $friend = User::findById($friendId);
            
            if (!$friend) continue;
            
            $lastMessage = PrivateMessage::findById($conv['last_message_id']);
            $unreadCount = PrivateMessage::getUnreadCount($currentUser->id);
            
            $recentMessages = CacheService::getRecentPrivate($currentUser->id, $friendId);
            
            $result[] = [
                'friend_id' => $friendId,
                'friend' => [
                    'id' => $friend->id,
                    'username' => $friend->username,
                    'nickname' => $friend->nickname,
                    'avatar' => $friend->avatar,
                    'is_online' => CacheService::isOnline($friend->id),
                ],
                'last_message' => $lastMessage ? $lastMessage->toArray() : null,
                'unread_count' => $unreadCount,
            ];
        }
        
        Response::success($result);
    }
}
