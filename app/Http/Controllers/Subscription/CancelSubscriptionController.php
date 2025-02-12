<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CancelSubscriptionController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(
            [
                'type' => ['string'],
                'is_cancel_now' => ['bool'],
                'cancel_after_num_days' => ['int', 'min:1'],
            ]
        );
        $cancelAfterNumberDays = $request->input('cancel_after_num_days');
        //$externalUserId = $request->input('internal_user_id');
        $externalUserId = '9e107d9d-372b-4a6c-8a5b-36d2f3a7b432'; //TODO
        $user = User::query()->where('internal_user_id', $externalUserId)->first();

        try {
            if ($request->input('is_cancel_now')) {
                return $user->subscription($request->input('type'))->cancelNow();
            }
            if ($cancelAfterNumberDays) {
                return $user->subscription($request->input('type'))->cancelAt(now()->addDays($cancelAfterNumberDays));
            }

            return $user->subscription($request->input('type'))->cancel();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
