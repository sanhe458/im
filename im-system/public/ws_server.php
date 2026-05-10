<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Services\AuthService;
use App\Services\MessageService;
use App\Models\User;
use App\Services\CacheService;
use App\Utils\Redis;

class ChatServer implements MessageComponentInterface
{
    protected $connections = [];
    protected $users = [];

    public function onOpen(ConnectionInterface $conn)
    {
        $conn->data = ['authenticated' => false, 'user_id' => null];
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Invalid message format']));
            return;
        }

        switch ($data['type']) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;
                
            case 'private_message':
                $this->handlePrivateMessage($from, $data);
                break;
                
            case 'group_message':
                $this->handleGroupMessage($from, $data);
                break;
                
            case 'typing':
                $this->handleTyping($from, $data);
                break;
                
            case 'heartbeat':
                $this->handleHeartbeat($from);
                break;
                
            case 'join_group':
                $this->handleJoinGroup($from, $data);
                break;
                
            case 'leave_group':
                $this->handleLeaveGroup($from, $data);
                break;
                
            default:
                $from->send(json_encode(['type' => 'error', 'message' => 'Unknown message type']));
        }
    }

    private function handleAuth(ConnectionInterface $conn, $data)
    {
        if (!isset($data['token'])) {
            $conn->send(json_encode(['type' => 'auth_error', 'message' => 'Token required']));
            return;
        }

        $user = AuthService::verifyToken($data['token']);
        
        if (!$user) {
            $conn->send(json_encode(['type' => 'auth_error', 'message' => 'Invalid token']));
            return;
        }

        $conn->data['authenticated'] = true;
        $conn->data['user_id'] = $user->id;
        
        $this->connections[$conn->resourceId] = $conn;
        $this->users[$user->id] = $conn->resourceId;
        
        CacheService::setOnline($user->id);
        User::updateStatus($user->id, 2);
        
        $conn->send(json_encode([
            'type' => 'auth_success',
            'user_id' => $user->id,
            'username' => $user->username,
        ]));
        
        $this->broadcastUserStatus($user->id, 'online');
    }

    private function handlePrivateMessage(ConnectionInterface $from, $data)
    {
        if (!$from->data['authenticated']) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Not authenticated']));
            return;
        }

        if (!isset($data['to_user_id']) || !isset($data['content'])) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Missing parameters']));
            return;
        }

        $message = MessageService::sendPrivateMessage(
            $from->data['user_id'],
            (int)$data['to_user_id'],
            $data['content'],
            $data['message_type'] ?? 1
        );

        $messageData = array_merge($message->toArray(), [
            'from_user' => [
                'id' => $from->data['user_id'],
                'username' => $message->from_user_id,
                'nickname' => '',
                'avatar' => null,
            ],
        ]);

        $from->send(json_encode([
            'type' => 'private_message_sent',
            'message' => $messageData,
        ]));

        if (isset($this->users[$data['to_user_id']])) {
            $toConn = $this->connections[$this->users[$data['to_user_id']]];
            $toConn->send(json_encode([
                'type' => 'private_message',
                'message' => $messageData,
            ]));
        }
    }

    private function handleGroupMessage(ConnectionInterface $from, $data)
    {
        if (!$from->data['authenticated']) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Not authenticated']));
            return;
        }

        if (!isset($data['group_id']) || !isset($data['content'])) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Missing parameters']));
            return;
        }

        $message = MessageService::sendGroupMessage(
            $from->data['user_id'],
            (int)$data['group_id'],
            $data['content'],
            $data['message_type'] ?? 1
        );

        if (!$message) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Not a member of this group']));
            return;
        }

        $messageData = array_merge($message->toArray(), [
            'user' => [
                'id' => $from->data['user_id'],
                'username' => '',
                'nickname' => '',
                'avatar' => null,
            ],
        ]);

        $this->broadcastToGroup($data['group_id'], [
            'type' => 'group_message',
            'message' => $messageData,
        ]);
    }

    private function handleTyping(ConnectionInterface $from, $data)
    {
        if (!$from->data['authenticated']) return;
        
        if (!isset($data['to_user_id'])) return;

        CacheService::setTyping($from->data['user_id'], $data['to_user_id']);

        if (isset($this->users[$data['to_user_id']])) {
            $toConn = $this->connections[$this->users[$data['to_user_id']]];
            $toConn->send(json_encode([
                'type' => 'typing',
                'from_user_id' => $from->data['user_id'],
            ]));
        }
    }

    private function handleHeartbeat(ConnectionInterface $conn)
    {
        if ($conn->data['authenticated']) {
            CacheService::setOnline($conn->data['user_id']);
        }
        
        $conn->send(json_encode(['type' => 'heartbeat_ack']));
    }

    private function handleJoinGroup(ConnectionInterface $from, $data)
    {
        if (!$from->data['authenticated']) return;
        
        if (!isset($data['group_id'])) return;

        $this->broadcastToGroup($data['group_id'], [
            'type' => 'group_member_joined',
            'user_id' => $from->data['user_id'],
            'username' => '',
        ], $from->data['user_id']);
    }

    private function handleLeaveGroup(ConnectionInterface $from, $data)
    {
        if (!$from->data['authenticated']) return;
        
        if (!isset($data['group_id'])) return;

        $this->broadcastToGroup($data['group_id'], [
            'type' => 'group_member_left',
            'user_id' => $from->data['user_id'],
            'username' => '',
        ]);
    }

    private function broadcastUserStatus($userId, $status)
    {
        $user = User::findById($userId);
        if (!$user) return;

        $friends = \App\Models\Friendship::getFriends($userId);
        
        foreach ($friends as $friend) {
            if (isset($this->users[$friend['id']])) {
                $conn = $this->connections[$this->users[$friend['id']]];
                $conn->send(json_encode([
                    'type' => 'user_status',
                    'user_id' => $userId,
                    'username' => $user->username,
                    'status' => $status,
                ]));
            }
        }
    }

    private function broadcastToGroup($groupId, $message, $excludeUserId = null)
    {
        $members = \App\Models\GroupMember::getMembers($groupId);
        
        foreach ($members as $member) {
            if ($excludeUserId && $member['user_id'] == $excludeUserId) continue;
            
            if (isset($this->users[$member['user_id']])) {
                $conn = $this->connections[$this->users[$member['user_id']]];
                $conn->send(json_encode($message));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        if ($conn->data['authenticated']) {
            $userId = $conn->data['user_id'];
            
            unset($this->users[$userId]);
            
            CacheService::setOffline($userId);
            User::updateStatus($userId, 1);
            
            $this->broadcastUserStatus($userId, 'offline');
        }
        
        unset($this->connections[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        error_log('WebSocket error: ' . $e->getMessage());
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080
);

echo "WebSocket server started on port 8080\n";
$server->run();
