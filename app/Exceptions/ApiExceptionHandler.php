<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionHandler
{
    public static function handle(Throwable $exception, Request $request): ?Response
    {
        if (! $request->is('api/*')) {
            return null;
        }

        $status = match ($exception::class) {
            AuthenticationException::class => Response::HTTP_UNAUTHORIZED,
            ValidationException::class => Response::HTTP_BAD_REQUEST,
            NotFoundHttpException::class => Response::HTTP_NOT_FOUND,
            AccessDeniedHttpException::class => Response::HTTP_FORBIDDEN,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };

        return response()->json(['status' => $status, 'message' => $exception->getMessage()], $status);
    }
}
