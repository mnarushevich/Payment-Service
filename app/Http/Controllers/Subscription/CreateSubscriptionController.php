<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreateSubscriptionController extends Controller
{
    public function __invoke(Request $request, UserService $userService)
    {
        $request->validate([
            'payment_method_id' => ['string'],
            'type' => ['string'],
            'trial_num_days' => ['int', 'min:1'],
        ]);
        $user = $userService->getByInternalUserId($request->input('auth_user_id'));

        $subscriptionType = $request->input('type', 'default');
        $trialDaysNumber = $request->input('trial_num_days');
        $subscriptionPrices = $request->input('prices', []);
        $paymentMethodId = $request->input('payment_method_id');
        if ($request->has('payment_method_id')) {
            $paymentMethod = $user->defaultPaymentMethod();
            $paymentMethodId = $paymentMethod->id;
        }

        try {
            $subscription = $user->newSubscription($subscriptionType, $subscriptionPrices);
            if ($trialDaysNumber) {
                $subscription->trialDays($trialDaysNumber);
            }

            return $subscription->create($paymentMethodId, ['metadata' => ['init_time' => Carbon::now()->toDateTimeString()]]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Failed to create subscription.'], 400);
        }
    }
}
