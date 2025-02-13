<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CreateSubscriptionController
{
    public function __invoke(Request $request, UserService $userService): JsonResponse
    {
        $request->validate([
            'payment_method_id' => ['string'],
            'type' => ['string'],
            'prices' => ['array'],
            'trial_num_days' => ['nullable', 'int', 'min:1'],
        ]);
        $user = $userService->getByInternalUserId($request->input('auth_user_id'));
        $subscriptionType = $request->input('type', 'default');

        if ($user->subscribed($subscriptionType)) {
            return response()->json(
                ['message' => "User already subscribed to $subscriptionType subscription."],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $trialDaysNumber = $request->input('trial_num_days');
        $subscriptionPrices = $request->input('prices', []);
        $paymentMethodId = $request->input('payment_method_id');
        $paymentMethod = null;

        try {
            if ($paymentMethodId) {
                $paymentMethod = $user->findPaymentMethod($paymentMethodId);

                if (! $paymentMethod) {
                    return response()->json(['message' => 'Payment method not found.'], Response::HTTP_BAD_REQUEST);
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if (! $paymentMethod) {
            $paymentMethod = $user->defaultPaymentMethod();
        }

        if (! $paymentMethod) {
            return response()->json(['message' => 'No payment method found.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $subscriptionBuilder = $user->newSubscription($subscriptionType, $subscriptionPrices);

            if ($trialDaysNumber) {
                $subscriptionBuilder->trialDays($trialDaysNumber);
            }

            $subscription = $subscriptionBuilder->create(
                $paymentMethod->id,
                ['metadata' => ['source' => 'payment-service']],
            );

            return response()->json(['subscription' => $subscription]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Failed to create subscription.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
