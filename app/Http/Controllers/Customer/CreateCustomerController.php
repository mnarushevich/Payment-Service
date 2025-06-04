<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Exceptions\StripeCustomerException;
use App\Models\User;
use App\Services\StripeCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class CreateCustomerController
{
    public function __invoke(Request $request, StripeCustomerService $stripeCustomerService): JsonResponse
    {
        $user = new User;
        $user->internal_user_id = $request->input('auth_user_id');
        $user->save();

        try {
            $stripeCustomer = $stripeCustomerService->createCustomer($user);

            return response()->json(['message' => $stripeCustomer]);
        } catch (StripeCustomerException $stripeCustomerException) {
            Log::error($stripeCustomerException->getMessage());

            return response()->json(['message' => 'Failed to create customer.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
