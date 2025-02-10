<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class ValidateAuthToken
{
    /**
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('Token not provided.');
        }

        $token = substr($authHeader, 7);

        try {
            $payload = $this->decodeJwtPayload($token);

            if (isset($payload->exp) && time() >= $payload->exp) {
                throw new AuthenticationException('Token has expired');
            }

            if (! isset($payload->internal_user_id)) {
                throw new AuthenticationException('Invalid token payload');
            }

        } catch (Exception $e) {
            throw new AuthenticationException($e->getMessage());
        }

        $request->merge(['auth_user_id' => $payload->internal_user_id]);

        return $next($request);
    }

    /**
     * @throws AuthenticationException
     */
    private function decodeJwtPayload($token)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new AuthenticationException('Invalid token format');
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), false);

        if (! $payload) {
            throw new AuthenticationException('Invalid token payload');
        }

        return $payload;
    }
}
