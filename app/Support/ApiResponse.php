<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Helper respons JSON konsisten untuk seluruh API.
 *
 * Format respons:
 *   sukses: { "success": true, "data": <payload>, "message"?: string }
 *   gagal:  { "success": false, "error": string, "errors"?: array }
 *
 * Pakai di controller seperti:
 *   return ApiResponse::ok($todo, 'Tugas tersimpan');
 *   return ApiResponse::error('Tidak ditemukan', 404);
 */
class ApiResponse
{
    public static function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }

    public static function created(mixed $data = null, ?string $message = 'Berhasil dibuat'): JsonResponse
    {
        return self::ok($data, $message, 201);
    }

    public static function error(string $error, int $status = 400, ?array $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'error'   => $error,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    public static function forbidden(string $error = 'Akses ditolak'): JsonResponse
    {
        return self::error($error, 403);
    }

    public static function notFound(string $error = 'Data tidak ditemukan'): JsonResponse
    {
        return self::error($error, 404);
    }

    public static function validationError(array $errors, string $message = 'Data tidak valid'): JsonResponse
    {
        return self::error($message, 422, $errors);
    }
}
