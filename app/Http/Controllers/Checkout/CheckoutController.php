<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController
{
    public function __invoke(Request $request, UserService $userService): JsonResponse
    {
        $request->validate(
            [
                'product_id' => ['required'],
                'amount' => ['required', 'integer', 'min:1'],
                'quantity' => ['required', 'integer', 'min:1'],
            ],
        );

        $user = $userService->getByInternalUserId($request->input('auth_user_id'));

        try {
            $checkout = $user->checkoutCharge(
                amount: $request->input('amount'),
                name: $request->input('product_id'),
                quantity: $request->input('quantity'),
                sessionOptions: [
                    'success_url' => route('payment.success'),
                    'cancel_url' => route('payment.cancel'),
                ]);

            return response()->json(['checkout' => $checkout]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Failed to charge user.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
