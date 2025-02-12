<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;

class ResumeSubscriptionController extends Controller
{
    public function __invoke(Request $request, UserService $userService)
    {
        $request->validate(['type' => ['string']]);
        $user = $userService->getByInternalUserId($request->input('auth_user_id'));

        $subscription = $user->subscription($request->input('type'));

        if (! $subscription) {
            return response()->json(['message' => 'Subscription not found.'], 404);
        }

        if ($subscription->onGracePeriod()) {
            $subscription->resume();

            return response()->json(['message' => 'Subscription resumed.']);
        }

        return response()->json(['message' => 'Subscription is not on grace period.']);
    }
}
