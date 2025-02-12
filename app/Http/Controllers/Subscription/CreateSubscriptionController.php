<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CreateSubscriptionController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'payment_method_id' => ['string'],
            'type' => ['string'],
            'trial_num_days' => ['int', 'min:1'],
        ]);
        //$externalUserId = $request->input('internal_user_id');
        $externalUserId = '9e107d9d-372b-4a6c-8a5b-36d2f3a7b432'; //TODO
        $user = User::query()->where('internal_user_id', $externalUserId)->first();

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
        } catch (\Exception) {
            return response()->json(['message' => 'Failed to create subscription.'], 400);
        }
    }
}
