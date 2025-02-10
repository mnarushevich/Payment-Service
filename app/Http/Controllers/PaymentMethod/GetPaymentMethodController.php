<?php

declare(strict_types=1);

namespace App\Http\Controllers\PaymentMethod;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class GetPaymentMethodController extends Controller
{
    public function __invoke(Request $request)
    {
        $internalUserId = $request->input('auth_user_id');
        $user = User::query()->where('external_user_id', $internalUserId)->first();

        if (! $user) {
            throw new ModelNotFoundException("User with ID $internalUserId not found.");
        }

        try {
            return $user->paymentMethods();
        } catch (\Exception $e) {
            dd($e->getMessage());

            return response()->json(['message' => 'Failed to get payment methods.'], 400);
        }
    }
}
