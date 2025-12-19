<?php

namespace App\Http\Responses;

class ApiResponse
{
    public static function success($data = null, $message = '操作成功', $code = 200)
    {
        return response()->json([
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error($message = '操作失败', $code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    public static function paginated($data, $message = '获取成功')
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ],
        ]);
    }
}

