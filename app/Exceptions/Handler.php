<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Auth\AuthenticationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'code' => 422,
                    'message' => '数据验证失败',
                    'errors' => $exception->errors(),
                ], 422);
            }

            if ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false,
                    'code' => 404,
                    'message' => '资源不存在',
                ], 404);
            }

            if ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'code' => 404,
                    'message' => '接口不存在',
                ], 404);
            }

            if ($exception instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'success' => false,
                    'code' => 405,
                    'message' => '请求方法不允许',
                ], 405);
            }

            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'code' => 401,
                    'message' => '未授权，请先登录',
                ], 401);
            }

            return response()->json([
                'success' => false,
                'code' => 500,
                'message' => $exception->getMessage() ?: '服务器内部错误',
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
