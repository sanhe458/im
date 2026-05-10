<?php

namespace App\Utils;

class Response
{
    public static function json($code, $message, $data = null, $httpCode = 200)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');

        $response = [
            'code' => $code,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success($data = null, $message = 'success')
    {
        return self::json(200, $message, $data);
    }

    public static function error($message, $code = 400, $errorCode = 'ERROR', $httpCode = 400)
    {
        return self::json($code, $message, ['error_code' => $errorCode], $httpCode);
    }

    public static function paginate($list, $total, $page, $pageSize)
    {
        return self::json(200, 'success', [
            'list' => $list,
            'pagination' => [
                'total' => (int)$total,
                'page' => (int)$page,
                'page_size' => (int)$pageSize,
                'total_pages' => ceil($total / $pageSize),
            ]
        ]);
    }
}
