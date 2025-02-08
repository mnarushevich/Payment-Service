<?php

declare(strict_types=1);

namespace App\Http\Controllers\PaymentMethod;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class GetPaymentMethodController extends Controller
{
    public function __invoke(Request $request)
    {
        //$externalUserId = $request->input('external_user_id');
        $externalUserId = '9e107d9d-372b-4a6c-8a5b-36d2f3a7b432'; //TODO
        $user = User::query()->where('external_user_id', $externalUserId)->first();

        try {
            return $user->paymentMethods();
        } catch (\Exception $e) {
            dd($e->getMessage());

            return response()->json(['message' => 'Failed to get payment methods.'], 400);
        }
    }
}
