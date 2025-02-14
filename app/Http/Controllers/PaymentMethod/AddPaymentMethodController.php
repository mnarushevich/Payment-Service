<?php

declare(strict_types=1);

namespace App\Http\Controllers\PaymentMethod;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Symfony\Component\HttpFoundation\Response;

final class AddPaymentMethodController
{
    public function __invoke(Request $request, UserService $userService): JsonResponse
    {
        $request->validate(
            [
                'payment_method' => ['required', 'string'],
                'set_as_default' => ['nullable', 'boolean'],
            ]
        );
        $internalUserId = $request->input('auth_user_id');
        $user = $userService->getByInternalUserId($internalUserId);

        try {
            $setupIntent = Cashier::stripe()->setupIntents->create([
                'customer' => $user->stripe_id,
                'payment_method' => $request->input('payment_method'),
                'confirm' => true,
                'return_url' => route('payment.success'),
            ]);

            $user->addPaymentMethod($setupIntent->payment_method);

            if ($request->input('set_as_default')) {
                $user->updateDefaultPaymentMethod($setupIntent->payment_method);
            }

            return response()->json([
                'message' => 'Payment method added successfully',
                'payment_method' => $setupIntent->payment_method,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
