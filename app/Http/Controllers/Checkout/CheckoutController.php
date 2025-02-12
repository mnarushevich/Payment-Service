<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function __invoke(Request $request, UserService $userService)
    {
        $request->validate(
            [
                'product_id' => ['required'],
                'amount' => ['required', 'integer', 'min:1'],
                'quantity' => ['required', 'integer', 'min:1'],
            ],
        );

        $user = $userService->getByInternalUserId($request->input('auth_user_id'));

        try {
            return $user->checkoutCharge(
                amount: $request->input('amount'),
                name: $request->input('product_id'),
                quantity: $request->input('quantity'),
                sessionOptions: [
                    'success_url' => route('payment.success'),
                    'cancel_url' => route('payment.cancel'),
                ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Failed to charge user.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
