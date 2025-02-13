<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RefundChargeController
{
    public function __invoke(string $paymentId, Request $request, UserService $userService): JsonResponse
    {
        $user = $userService->getByInternalUserId($request->input('auth_user_id'));

        try {
            $user->refund($paymentId);

            return response()->json(['message' => 'Payment refunded.']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Failed to refund payment.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
