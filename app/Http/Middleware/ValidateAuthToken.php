<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class ValidateAuthToken
{
    private const ERROR_TOKEN_NOT_PROVIDED = 'Token not provided.';

    private const ERROR_TOKEN_EXPIRED = 'Token has expired';

    private const ERROR_INVALID_TOKEN_PAYLOAD = 'Invalid token payload';

    private const ERROR_INVALID_TOKEN_FORMAT = 'Invalid token format';

    /**
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');
        $tokenType = 'Bearer';

        if (! $authHeader || ! str_starts_with($authHeader, $tokenType)) {
            throw new AuthenticationException(self::ERROR_TOKEN_NOT_PROVIDED);
        }

        $token = substr($authHeader, strlen($tokenType));
        $payload = $this->validateToken($token);

        $request->merge(['auth_user_id' => $payload->internal_user_id]);

        return $next($request);
    }

    /**
     * @throws AuthenticationException
     */
    private function validateToken(string $token): object
    {
        try {
            $payload = $this->decodeJwtPayload($token);

            if (isset($payload->exp) && time() >= $payload->exp) {
                throw new AuthenticationException(self::ERROR_TOKEN_EXPIRED);
            }

            if (! isset($payload->internal_user_id)) {
                throw new AuthenticationException(self::ERROR_INVALID_TOKEN_PAYLOAD);
            }

            return $payload;
        } catch (Exception $e) {
            throw new AuthenticationException($e->getMessage());
        }
    }

    /**
     * @throws AuthenticationException
     */
    private function decodeJwtPayload(string $token): object
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new AuthenticationException(self::ERROR_INVALID_TOKEN_FORMAT);
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), false);

        if (! $payload) {
            throw new AuthenticationException(self::ERROR_INVALID_TOKEN_PAYLOAD);
        }

        return $payload;
    }
}
