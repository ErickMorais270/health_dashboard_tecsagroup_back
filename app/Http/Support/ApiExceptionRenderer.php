<?php

declare(strict_types=1);

namespace App\Http\Support;

use App\Exceptions\AiProviderException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ApiExceptionRenderer
{
    public static function render(Request $request, Throwable $e): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_failed'),
                'errors' => $e->errors(),
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => __('api.unauthenticated'),
                'errors' => [],
            ], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => __('api.resource_not_found'),
                'errors' => [],
            ], SymfonyResponse::HTTP_NOT_FOUND);
        }

        if ($e instanceof AiProviderException) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : SymfonyResponse::HTTP_BAD_GATEWAY;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [
                    'ai' => [$e->getMessage()],
                ],
            ], $status);
        }

        if ($e instanceof HttpExceptionInterface) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() !== '' ? $e->getMessage() : __('api.http_error'),
                'errors' => [],
            ], $e->getStatusCode());
        }

        return response()->json([
            'success' => false,
            'message' => config('app.debug') ? $e->getMessage() : __('api.server_error'),
            'errors' => [],
        ], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}
