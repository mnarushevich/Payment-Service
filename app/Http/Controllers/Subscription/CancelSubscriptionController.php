<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class CancelSubscriptionController
{
    public function __invoke(Request $request, UserService $userService): JsonResponse
    {
        $request->validate(
            [
                'type' => ['required', 'string'],
                'is_cancel_now' => ['bool'],
                'cancel_after_num_days' => ['int', 'min:1'],
            ]
        );
        $user = $userService->getByInternalUserId($request->input('auth_user_id'));
        $subscriptionType = $request->input('type');

        if ($user->subscribed($subscriptionType)) {
            return response()->json(
                ['message' => sprintf('User already subscribed to %s subscription.', $subscriptionType)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            if ($request->input('is_cancel_now')) {
                $user->subscription($subscriptionType)->cancelNow();

                return response()->json(['message' => 'Subscription cancelled.']);
            }

            $cancelAfterNumberDays = $request->input('cancel_after_num_days');

            if ($cancelAfterNumberDays) {
                $user->subscription($subscriptionType)->cancelAt(now()->addDays($cancelAfterNumberDays));

                return response()->json(['message' => sprintf('Subscription will be cancelled after %s days.', $cancelAfterNumberDays)]);
            }

            $user->subscription($subscriptionType)->cancel();

            return response()->json(['message' => 'Subscription cancelled.']);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return response()->json(['message' => 'Failed to cancel subscription.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
