<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['product_id' => ['required']]);

        $amount = 100;
        //$externalUserId = $request->input('internal_user_id'); //TODO
        $productId = $request->input('product_id');

        $user = User::query()->where('internal_user_id', '9e107d9d-372b-4a6c-8a5b-36d2f3a7b432')->first();

        try {
            return $user->checkoutCharge($amount, $productId, 1, [
                'success_url' => route('payment.success'),
                'cancel_url' => route('payment.cancel'),
            ]);
        } catch (\Exception) {
            return response()->json(['message' => 'Failed to charge user.'], 400);
        }
    }
}
