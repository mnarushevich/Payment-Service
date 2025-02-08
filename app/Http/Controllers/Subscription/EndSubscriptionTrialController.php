<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EndSubscriptionTrialController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(
            [
                'type' => ['string'],
            ]
        );
        //$externalUserId = $request->input('external_user_id');
        $externalUserId = '9e107d9d-372b-4a6c-8a5b-36d2f3a7b432'; //TODO
        $user = User::query()->where('external_user_id', $externalUserId)->first();

        $subscription = $user->subscription($request->input('type'));

        if (! $subscription) {
            return response()->json(['message' => 'Subscription not found.'], 404);
        }

        if ($subscription->onTrial()) {
            $subscription->endTrial();

            return response()->json(['message' => 'Subscription trial ended.']);
        }

        return response()->json(['message' => 'Subscription is not on trial.']);
    }
}
