<?php

namespace App\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token
{
    private static function getSecret()
    {
        return require BASE_PATH . '/config/app.php';
    }

    public static function generate($userId, $type = 'access')
    {
        $config = self::getSecret();
        $expire = $type === 'refresh' ? $config['refresh_token_expire'] : $config['jwt_expire'];

        $payload = [
            'iss' => 'im-system',
            'aud' => 'im-system',
            'iat' => time(),
            'exp' => time() + $expire,
            'user_id' => $userId,
            'type' => $type,
        ];

        return JWT::encode($payload, $config['jwt_secret'], 'HS256');
    }

    public static function verify($token)
    {
        $config = self::getSecret();

        try {
            $decoded = JWT::decode($token, new Key($config['jwt_secret'], 'HS256'));
            return (array)$decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getUserId($token)
    {
        $payload = self::verify($token);
        return $payload ? $payload['user_id'] : null;
    }
}
