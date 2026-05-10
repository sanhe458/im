<?php

namespace App\Utils;

use Predis\Client;

class Redis
{
    private static ?Client $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            $config = require BASE_PATH . '/config/redis.php';

            self::$instance = new Client([
                'scheme' => 'tcp',
                'host' => $config['host'],
                'port' => $config['port'],
                'password' => $config['password'],
                'database' => $config['database'],
            ]);
        }

        return self::$instance;
    }

    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::getInstance(), $method], $args);
    }
}
