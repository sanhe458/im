<?php

namespace App\Utils;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            $config = require BASE_PATH . '/config/database.php';

            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset']
                );

                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new \Exception('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    public static function query($sql, $params = [])
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne($sql, $params = [])
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }

    public static function fetchAll($sql, $params = [])
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function insert($table, $data)
    {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));

        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        self::query($sql, array_values($data));

        return self::getInstance()->lastInsertId();
    }

    public static function update($table, $data, $where, $whereParams = [])
    {
        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "{$key} = ?";
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE {$where}";
        self::query($sql, array_merge(array_values($data), $whereParams));

        return true;
    }

    public static function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        self::query($sql, $params);
        return true;
    }
}
