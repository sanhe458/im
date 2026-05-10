<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Group;
use App\Models\PrivateMessage;
use App\Models\GroupMessage;
use App\Models\Admin;
use App\Utils\Token;
use App\Utils\Response;

class AdminController
{
    private static function getAdminFromToken()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }
        
        $token = $matches[1];
        $payload = Token::verify($token);
        
        if (!$payload || !isset($payload['admin_id'])) {
            return null;
        }
        
        return Admin::findById($payload['admin_id']);
    }
    
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['username']) || empty($data['password'])) {
            Response::error('Username and password are required', 400, 'VALIDATION_ERROR');
        }
        
        $admin = Admin::verifyPassword($data['username'], $data['password']);
        
        if (!$admin) {
            Response::error('Invalid credentials', 401, 'LOGIN_ERROR');
        }
        
        Admin::updateLastLogin($admin->id);
        
        $payload = [
            'iss' => 'im-system',
            'aud' => 'im-system',
            'iat' => time(),
            'exp' => time() + 86400,
            'admin_id' => $admin->id,
            'type' => 'admin_access',
        ];
        
        $token = \Firebase\JWT\JWT::encode(
            $payload, 
            require BASE_PATH . '/config/app.php'['jwt_secret'], 
            'HS256'
        );
        
        Response::success([
            'admin' => $admin->toArray(),
            'token' => $token,
        ], 'Login successful');
    }
    
    public function logout()
    {
        Response::success(null, 'Logout successful');
    }
    
    public function dashboard()
    {
        $admin = $_GET['admin_user'];
        
        $totalUsers = User::count();
        $totalGroups = Group::count();
        $todayMessages = PrivateMessage::countToday() + GroupMessage::countToday();
        
        $onlineUsers = 0;
        
        Response::success([
            'total_users' => $totalUsers,
            'online_users' => $onlineUsers,
            'total_groups' => $totalGroups,
            'today_messages' => $todayMessages,
            'stats' => [
                'new_users_today' => 0,
                'new_groups_today' => 0,
            ],
        ]);
    }
    
    public function users()
    {
        $admin = $_GET['admin_user'];
        $page = (int)($_GET['page'] ?? 1);
        $pageSize = (int)($_GET['page_size'] ?? 20);
        $keyword = $_GET['keyword'] ?? '';
        
        if (!empty($keyword)) {
            $result = User::search($keyword, $page, $pageSize);
        } else {
            $result = User::paginate($page, $pageSize);
        }
        
        Response::paginate($result['list'], $result['total'], $page, $pageSize);
    }
    
    public function showUser($id)
    {
        $admin = $_GET['admin_user'];
        $user = User::findById((int)$id);
        
        if (!$user) {
            Response::error('User not found', 404, 'NOT_FOUND');
        }
        
        Response::success($user->toArray());
    }
    
    public function updateUser($id)
    {
        $admin = $_GET['admin_user'];
        $data = json_decode(file_get_contents('php://input'), true);
        
        $user = User::findById((int)$id);
        if (!$user) {
            Response::error('User not found', 404, 'NOT_FOUND');
        }
        
        $updateData = [];
        if (isset($data['nickname'])) $updateData['nickname'] = $data['nickname'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        
        $user = User::updateProfile((int)$id, $updateData);
        
        Response::success($user->toArray(), 'User updated');
    }
    
    public function deleteUser($id)
    {
        $admin = $_GET['admin_user'];
        $id = (int)$id;
        
        $user = User::findById($id);
        if (!$user) {
            Response::error('User not found', 404, 'NOT_FOUND');
        }
        
        User::delete($id);
        
        Response::success(null, 'User deleted');
    }
    
    public function updateUserStatus($id)
    {
        $admin = $_GET['admin_user'];
        $data = json_decode(file_get_contents('php://input'), true);
        
        $user = User::findById((int)$id);
        if (!$user) {
            Response::error('User not found', 404, 'NOT_FOUND');
        }
        
        $status = isset($data['status']) ? (int)$data['status'] : 1;
        User::updateStatus($id, $status);
        
        Response::success(['status' => $status], 'User status updated');
    }
    
    public function groups()
    {
        $admin = $_GET['admin_user'];
        $page = (int)($_GET['page'] ?? 1);
        $pageSize = (int)($_GET['page_size'] ?? 20);
        
        $result = Group::paginate($page, $pageSize);
        
        Response::paginate($result['list'], $result['total'], $page, $pageSize);
    }
    
    public function showGroup($id)
    {
        $admin = $_GET['admin_user'];
        $group = Group::findById((int)$id);
        
        if (!$group) {
            Response::error('Group not found', 404, 'NOT_FOUND');
        }
        
        $members = \App\Models\GroupMember::getMembers((int)$id);
        
        Response::success([
            'group' => $group->toArray(),
            'members' => $members,
        ]);
    }
    
    public function updateGroup($id)
    {
        $admin = $_GET['admin_user'];
        $data = json_decode(file_get_contents('php://input'), true);
        
        $group = Group::findById((int)$id);
        if (!$group) {
            Response::error('Group not found', 404, 'NOT_FOUND');
        }
        
        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['description'])) $updateData['description'] = $data['description'];
        if (isset($data['avatar'])) $updateData['avatar'] = $data['avatar'];
        
        $group = Group::update((int)$id, $updateData);
        
        Response::success($group->toArray(), 'Group updated');
    }
    
    public function deleteGroup($id)
    {
        $admin = $_GET['admin_user'];
        
        $group = Group::findById((int)$id);
        if (!$group) {
            Response::error('Group not found', 404, 'NOT_FOUND');
        }
        
        Group::delete((int)$id);
        
        Response::success(null, 'Group deleted');
    }
    
    public function messages()
    {
        $admin = $_GET['admin_user'];
        $page = (int)($_GET['page'] ?? 1);
        $pageSize = (int)($_GET['page_size'] ?? 20);
        $type = $_GET['type'] ?? 'private';
        
        $offset = ($page - 1) * $pageSize;
        
        if ($type === 'private') {
            $total = \App\Utils\Database::fetchOne("SELECT COUNT(*) as count FROM private_messages");
            $messages = \App\Utils\Database::fetchAll(
                "SELECT pm.*, 
                        fu.username as from_username, fu.nickname as from_nickname,
                        tu.username as to_username, tu.nickname as to_nickname
                 FROM private_messages pm
                 JOIN users fu ON pm.from_user_id = fu.id
                 JOIN users tu ON pm.to_user_id = tu.id
                 ORDER BY pm.id DESC
                 LIMIT ? OFFSET ?",
                [$pageSize, $offset]
            );
        } else {
            $total = \App\Utils\Database::fetchOne("SELECT COUNT(*) as count FROM group_messages");
            $messages = \App\Utils\Database::fetchAll(
                "SELECT gm.*, g.name as group_name, u.username, u.nickname
                 FROM group_messages gm
                 JOIN `groups` g ON gm.group_id = g.id
                 JOIN users u ON gm.user_id = u.id
                 ORDER BY gm.id DESC
                 LIMIT ? OFFSET ?",
                [$pageSize, $offset]
            );
        }
        
        Response::paginate($messages, $total['count'], $page, $pageSize);
    }
    
    public function deleteMessage($id)
    {
        $admin = $_GET['admin_user'];
        $type = $_GET['type'] ?? 'private';
        
        if ($type === 'private') {
            \App\Utils\Database::delete('private_messages', 'id = ?', [(int)$id]);
        } else {
            GroupMessage::delete((int)$id);
        }
        
        Response::success(null, 'Message deleted');
    }
    
    public function settings()
    {
        $admin = $_GET['admin_user'];
        $settings = \App\Utils\Database::fetchAll("SELECT `key`, value FROM system_settings");
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['key']] = $setting['value'];
        }
        
        Response::success($result);
    }
    
    public function updateSettings()
    {
        $admin = $_GET['admin_user'];
        $data = json_decode(file_get_contents('php://input'), true);
        
        foreach ($data as $key => $value) {
            $exists = \App\Utils\Database::fetchOne(
                "SELECT id FROM system_settings WHERE `key` = ?",
                [$key]
            );
            
            if ($exists) {
                \App\Utils\Database::update(
                    'system_settings',
                    ['value' => $value],
                    '`key` = ?',
                    [$key]
                );
            } else {
                \App\Utils\Database::insert('system_settings', [
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }
        
        Response::success(null, 'Settings updated');
    }
}
