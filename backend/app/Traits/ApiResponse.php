<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success($data, string $message = null, int $code = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta,
            'message' => $message,
            'errors' => null,
        ], $code);
    }

    protected function error(string $message, int $code, $errors = null): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => [],
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
