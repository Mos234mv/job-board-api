<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * الدالة الأساسية المطلوبة في المواصفات
     */
    public static function sendResponse($code, $msg, $data = null): JsonResponse
    {
        return response()->json([
            'status' => $code >= 200 && $code < 300,
            'message' => $msg,
            'data' => $data,
        ], $code);
    }

    /**
     * اختصار للردود الناجحة
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operation successful',
        int $code = 200
    ): JsonResponse {
        return self::sendResponse($code, $message, $data);
    }

    /**
     * اختصار للردود الفاشلة
     */
    public static function error(
        string $message = 'Something went wrong',
        int $code = 400,
        mixed $errors = null
    ): JsonResponse {
        // هنا بنبعت الـ errors جوه الـ data عشان يتوافق مع sendResponse
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => null,
        ], $code);
    }
}
