<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class RefundChargeController
{
    public function __invoke(Request $request, UserService $userService): JsonResponse
    {
        $request->validate(['payment_id' => ['required', 'string']]);
        $user = $userService->getByInternalUserId($request->input('auth_user_id'));

        try {
            $user->refund($request->input('payment_id'));

            return response()->json(['message' => 'Payment refunded.']);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return response()->json(['message' => 'Failed to refund payment.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
