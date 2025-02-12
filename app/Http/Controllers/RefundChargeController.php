<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class RefundChargeController extends Controller
{
    public function __invoke(string $paymentId, Request $request)
    {
        //$externalUserId = $request->input('internal_user_id');
        $externalUserId = '9e107d9d-372b-4a6c-8a5b-36d2f3a7b432'; //TODO
        $user = User::query()->where('internal_user_id', $externalUserId)->first();

        try {
            $user->refund($paymentId);

            return response()->json(['message' => 'Payment refunded.']);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());

            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
