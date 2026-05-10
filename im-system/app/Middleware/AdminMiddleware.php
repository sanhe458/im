<?php

namespace App\Middleware;

use App\Models\Admin;
use App\Utils\Token;
use App\Utils\Response;

class AdminMiddleware
{
    public static function handle()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            Response::error('Authorization header is required', 401, 'UNAUTHORIZED', 401);
        }
        
        if (!preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            Response::error('Invalid authorization header format', 401, 'UNAUTHORIZED', 401);
        }
        
        $token = $matches[1];
        $payload = Token::verify($token);
        
        if (!$payload || !isset($payload['admin_id']) || $payload['type'] !== 'admin_access') {
            Response::error('Invalid admin token', 401, 'UNAUTHORIZED', 401);
        }
        
        $admin = Admin::findById($payload['admin_id']);
        
        if (!$admin) {
            Response::error('Admin not found', 401, 'UNAUTHORIZED', 401);
        }
        
        return $admin;
    }
}
