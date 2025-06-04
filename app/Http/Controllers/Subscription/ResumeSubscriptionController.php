<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class ResumeSubscriptionController
{
    public function __invoke(Request $request, UserService $userService): JsonResponse
    {
        $request->validate(['type' => ['required', 'string']]);
        $user = $userService->getByInternalUserId($request->input('auth_user_id'));
        $subscription = $user->subscription($request->input('type'));

        if (! $subscription) {
            return response()->json(['message' => 'Subscription not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($subscription->onGracePeriod()) {
                $subscription->resume();

                return response()->json(['message' => 'Subscription resumed.']);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return response()->json(['message' => 'Failed to resume subscription.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['message' => 'Subscription is not on grace period.']);
    }
}
