<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\CacheService;
use App\Utils\Response;
use App\Utils\Validator;

class UserController
{
    public function profile()
    {
        $currentUser = $_GET['current_user'];
        $user = User::findById($currentUser->id);
        
        if (!$user) {
            Response::error('User not found', 404, 'NOT_FOUND');
        }
        
        $user->is_online = CacheService::isOnline($user->id);
        
        Response::success($user->toArray());
    }
    
    public function updateProfile()
    {
        $currentUser = $_GET['current_user'];
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validator = Validator::make($data, [
            'nickname' => 'required',
            'email' => 'email',
        ]);
        
        if ($validator->fails()) {
            Response::error($validator->firstError(), 400, 'VALIDATION_ERROR');
        }
        
        $user = User::updateProfile($currentUser->id, $data);
        
        Response::success($user->toArray(), 'Profile updated');
    }
    
    public function updatePassword()
    {
        $currentUser = $_GET['current_user'];
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['old_password']) || empty($data['new_password'])) {
            Response::error('Old password and new password are required', 400, 'VALIDATION_ERROR');
        }
        
        if (strlen($data['new_password']) < 6) {
            Response::error('New password must be at least 6 characters', 400, 'VALIDATION_ERROR');
        }
        
        $user = User::findById($currentUser->id);
        
        if (!password_verify($data['old_password'], $user->password)) {
            Response::error('Invalid old password', 400, 'INVALID_PASSWORD');
        }
        
        User::updatePassword($currentUser->id, $data['new_password']);
        
        Response::success(null, 'Password updated');
    }
    
    public function uploadAvatar()
    {
        $currentUser = $_GET['current_user'];
        
        if (empty($_FILES['avatar'])) {
            Response::error('Avatar file is required', 400, 'VALIDATION_ERROR');
        }
        
        $file = $_FILES['avatar'];
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            Response::error('Invalid file type. Allowed: jpeg, png, gif, webp', 400, 'INVALID_FILE_TYPE');
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            Response::error('File size must be less than 5MB', 400, 'FILE_TOO_LARGE');
        }
        
        $uploadDir = BASE_PATH . '/public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $currentUser->id . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            Response::error('Failed to upload file', 500, 'UPLOAD_ERROR');
        }
        
        $avatarUrl = '/uploads/avatars/' . $filename;
        
        $user = User::updateProfile($currentUser->id, ['avatar' => $avatarUrl]);
        
        Response::success(['avatar' => $avatarUrl], 'Avatar uploaded');
    }
    
    public function search()
    {
        $keyword = $_GET['keyword'] ?? '';
        
        if (strlen($keyword) < 1) {
            Response::error('Search keyword is required', 400, 'VALIDATION_ERROR');
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $pageSize = (int)($_GET['page_size'] ?? 20);
        
        $result = User::search($keyword, $page, $pageSize);
        
        foreach ($result['list'] as &$user) {
            $user['is_online'] = CacheService::isOnline($user['id']);
        }
        
        Response::paginate($result['list'], $result['total'], $page, $pageSize);
    }
}
