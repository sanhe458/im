<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Utils\Response;
use App\Utils\Validator;

class AuthController
{
    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validator = Validator::make($data, [
            'username' => 'required|min:6',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'nickname' => 'required',
        ]);
        
        if ($validator->fails()) {
            Response::error($validator->firstError(), 400, 'VALIDATION_ERROR');
        }
        
        $result = AuthService::register($data);
        
        if (isset($result['error'])) {
            Response::error($result['error'], 400, 'REGISTRATION_ERROR');
        }
        
        Response::success($result, 'Registration successful');
    }
    
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['username']) || empty($data['password'])) {
            Response::error('Username and password are required', 400, 'VALIDATION_ERROR');
        }
        
        $result = AuthService::login($data['username'], $data['password']);
        
        if (isset($result['error'])) {
            Response::error($result['error'], 401, 'LOGIN_ERROR');
        }
        
        Response::success($result, 'Login successful');
    }
    
    public function logout()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches);
        $token = $matches[1] ?? '';
        
        AuthService::logout($token);
        
        Response::success(null, 'Logout successful');
    }
    
    public function refresh()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['refresh_token'])) {
            Response::error('Refresh token is required', 400, 'VALIDATION_ERROR');
        }
        
        $result = AuthService::refresh($data['refresh_token']);
        
        if (isset($result['error'])) {
            Response::error($result['error'], 401, 'REFRESH_ERROR');
        }
        
        Response::success($result, 'Token refreshed');
    }
}
