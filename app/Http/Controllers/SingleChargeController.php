<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SingleChargeController
{
    public function __invoke(Request $request, UserService $userService): JsonResponse
    {
        $request->validate(
            [
                'amount' => ['required',  'int', 'min:1'],
            ]
        );
        $user = $userService->getByInternalUserId($request->input('auth_user_id'));
        $paymentMethod = $user->defaultPaymentMethod();

        if ($paymentMethod === null) {
            return response()->json(['message' => 'No payment method found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $payment = $user->charge(
                $request->input('amount'),
                $paymentMethod->id,
                ['return_url' => route('payment.success')],
            );

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Payment successful.',
                    'payment' => $payment,
                ],
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Payment failed.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
