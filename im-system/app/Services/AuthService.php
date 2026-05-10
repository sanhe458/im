<?php

namespace App\Services;

use App\Models\User;
use App\Utils\Token;
use App\Utils\Redis;

class AuthService
{
    public static function register($data)
    {
        if (User::findByUsername($data['username'])) {
            return ['error' => 'Username already exists'];
        }
        
        if (User::findByEmail($data['email'])) {
            return ['error' => 'Email already exists'];
        }
        
        $user = User::create($data);
        
        $accessToken = Token::generate($user->id, 'access');
        $refreshToken = Token::generate($user->id, 'refresh');
        
        CacheService::setToken($accessToken, $user->id);
        CacheService::setToken($refreshToken, $user->id);
        
        return [
            'user' => $user->toArray(),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }
    
    public static function login($username, $password)
    {
        $user = User::findByUsername($username);
        if (!$user) {
            $user = User::findByEmail($username);
        }
        
        if (!$user) {
            return ['error' => 'User not found'];
        }
        
        if (!password_verify($password, $user->password)) {
            return ['error' => 'Invalid password'];
        }
        
        if ($user->status === 0) {
            return ['error' => 'Account is disabled'];
        }
        
        User::updateStatus($user->id, 2);
        User::updateLastLogin($user->id);
        
        $accessToken = Token::generate($user->id, 'access');
        $refreshToken = Token::generate($user->id, 'refresh');
        
        CacheService::setToken($accessToken, $user->id);
        CacheService::setToken($refreshToken, $user->id);
        CacheService::setOnline($user->id);
        
        return [
            'user' => $user->toArray(),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }
    
    public static function logout($token)
    {
        $userId = Token::getUserId($token);
        
        if ($userId) {
            CacheService::deleteToken($token);
            CacheService::setOffline($userId);
            User::updateStatus($userId, 1);
        }
        
        return true;
    }
    
    public static function refresh($refreshToken)
    {
        $payload = Token::verify($refreshToken);
        
        if (!$payload || $payload['type'] !== 'refresh') {
            return ['error' => 'Invalid refresh token'];
        }
        
        $userId = $payload['user_id'];
        $user = User::findById($userId);
        
        if (!$user) {
            return ['error' => 'User not found'];
        }
        
        $accessToken = Token::generate($userId, 'access');
        $newRefreshToken = Token::generate($userId, 'refresh');
        
        CacheService::setToken($accessToken, $userId);
        CacheService::setToken($newRefreshToken, $userId);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
        ];
    }
    
    public static function verifyToken($token)
    {
        $payload = Token::verify($token);
        
        if (!$payload || $payload['type'] !== 'access') {
            return null;
        }
        
        $userId = $payload['user_id'];
        $cachedUserId = CacheService::getToken($token);
        
        if ($cachedUserId != $userId) {
            return null;
        }
        
        return User::findById($userId);
    }
}
