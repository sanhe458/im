<?php

namespace App\Middleware;

use App\Services\AuthService;
use App\Utils\Response;

class AuthMiddleware
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
        $user = AuthService::verifyToken($token);
        
        if (!$user) {
            Response::error('Invalid or expired token', 401, 'UNAUTHORIZED', 401);
        }
        
        return $user;
    }
}
