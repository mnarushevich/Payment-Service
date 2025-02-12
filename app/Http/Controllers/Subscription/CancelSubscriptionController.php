<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CancelSubscriptionController extends Controller
{
    public function __invoke(Request $request, UserService $userService)
    {
        $request->validate(
            [
                'type' => ['string'],
                'is_cancel_now' => ['bool'],
                'cancel_after_num_days' => ['int', 'min:1'],
            ]
        );
        $cancelAfterNumberDays = $request->input('cancel_after_num_days');
        $user = $userService->getByInternalUserId($request->input('auth_user_id'));

        try {
            if ($request->input('is_cancel_now')) {
                return $user->subscription($request->input('type'))->cancelNow();
            }
            if ($cancelAfterNumberDays) {
                return $user->subscription($request->input('type'))->cancelAt(now()->addDays($cancelAfterNumberDays));
            }

            return $user->subscription($request->input('type'))->cancel();
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
