<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SingleChargeController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(
            [
                'amount' => ['int', 'required', 'min:1'],
            ]
        );
        //$externalUserId = $request->input('external_user_id');
        $externalUserId = '9e107d9d-372b-4a6c-8a5b-36d2f3a7b432'; //TODO
        $user = User::query()->where('external_user_id', $externalUserId)->first();
        $paymentMethod = $user->defaultPaymentMethod();

        if ($paymentMethod === null) {
            return response()->json(['message' => 'No payment method found.'], 404);
        }

        try {
            $payment = $user->charge($request->input('amount'), $paymentMethod->id, [
                'return_url' => route('payment.success'),
            ]);

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Payment successful.',
                    'payment' => $payment,
                ],
            );
        } catch (\Exception $e) {
            // Log::error($e->getMessage());

            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
