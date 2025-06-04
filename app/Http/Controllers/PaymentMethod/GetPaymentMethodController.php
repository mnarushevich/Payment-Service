<?php

declare(strict_types=1);

namespace App\Http\Controllers\PaymentMethod;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class GetPaymentMethodController
{
    public function __invoke(Request $request): JsonResponse
    {
        $internalUserId = $request->input('auth_user_id');
        $user = User::query()->where('internal_user_id', $internalUserId)->first();

        if (! $user) {
            throw new ModelNotFoundException(sprintf('User with ID %s not found.', $internalUserId));
        }

        try {
            return response()->json(['payment_methods' => $user->paymentMethods()]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return response()->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
