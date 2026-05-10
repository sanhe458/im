<?php

namespace App\Services;

use App\Utils\Redis;

class CacheService
{
    const TOKEN_PREFIX = 'token:';
    const ONLINE_PREFIX = 'online:user:';
    const UNREAD_PRIVATE_PREFIX = 'unread:private:';
    const UNREAD_GROUP_PREFIX = 'unread:group:';
    const RECENT_PRIVATE_PREFIX = 'recent:private:';
    const RECENT_GROUP_PREFIX = 'recent:group:';
    const TYPING_PREFIX = 'typing:';

    public static function setToken($token, $userId, $ttl = 604800)
    {
        Redis::setex(self::TOKEN_PREFIX . $token, $ttl, $userId);
    }

    public static function getToken($token)
    {
        return Redis::get(self::TOKEN_PREFIX . $token);
    }

    public static function deleteToken($token)
    {
        Redis::del(self::TOKEN_PREFIX . $token);
    }

    public static function setOnline($userId)
    {
        Redis::setex(self::ONLINE_PREFIX . $userId, 60, '1');
    }

    public static function isOnline($userId)
    {
        return Redis::exists(self::ONLINE_PREFIX . $userId);
    }

    public static function setOffline($userId)
    {
        Redis::del(self::ONLINE_PREFIX . $userId);
    }

    public static function incrementUnreadPrivate($userId)
    {
        Redis::incr(self::UNREAD_PRIVATE_PREFIX . $userId);
    }

    public static function getUnreadPrivate($userId)
    {
        return (int)Redis::get(self::UNREAD_PRIVATE_PREFIX . $userId) ?: 0;
    }

    public static function clearUnreadPrivate($userId)
    {
        Redis::set(self::UNREAD_PRIVATE_PREFIX . $userId, 0);
    }

    public static function setTyping($fromUserId, $toUserId)
    {
        Redis::setex(self::TYPING_PREFIX . "{$fromUserId}:{$toUserId}", 5, '1');
    }

    public static function isTyping($fromUserId, $toUserId)
    {
        return Redis::exists(self::TYPING_PREFIX . "{$fromUserId}:{$toUserId}");
    }

    public static function cacheRecentPrivate($userId, $friendId, $message)
    {
        $key = self::RECENT_PRIVATE_PREFIX . "{$userId}:{$friendId}";
        Redis::lpush($key, json_encode($message, JSON_UNESCAPED_UNICODE));
        Redis::ltrim($key, 0, 19);
    }

    public static function getRecentPrivate($userId, $friendId)
    {
        $key = self::RECENT_PRIVATE_PREFIX . "{$userId}:{$friendId}";
        $messages = Redis::lrange($key, 0, 19);
        return array_map(fn($m) => json_decode($m, true), $messages);
    }
}
