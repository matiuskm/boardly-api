<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ApiResponse
{
    public static function success(mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => self::meta($meta),
        ], $status);
    }

    public static function error(string $message, string|int|null $code = null, int $status = 400, array $meta = []): JsonResponse
    {
        $error = ['message' => $message];

        if ($code !== null) {
            $error['code'] = (string) $code;
        }

        return response()->json([
            'error' => $error,
            'meta' => self::meta($meta),
        ], $status);
    }

    protected static function meta(array $meta = []): array
    {
        return array_merge(['request_id' => self::requestId()], $meta);
    }

    protected static function requestId(): string
    {
        return request()->attributes->get('request_id') ?? (string) Str::uuid();
    }
}
