<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EndSubscriptionTrialController
{
    public function __invoke(Request $request, UserService $userService): JsonResponse
    {
        $request->validate(['type' => ['required', 'string']]);
        $user = $userService->getByInternalUserId($request->input('auth_user_id'));
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
