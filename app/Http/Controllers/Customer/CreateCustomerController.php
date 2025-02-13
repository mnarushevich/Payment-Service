<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CreateCustomerController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = new User;
        $user->internal_user_id = $request->input('auth_user_id');
        $user->save();

        try {
            $stripeCustomer = $user->createAsStripeCustomer([
                'email' => 'maksim@test.com',
                'name' => 'Maksim Narushevich',
                'phone' => '+66812345678',
                'metadata' => [
                    'internal_user_id' => $user->internal_user_id,
                ],
                'address' => [
                    'line1' => '123 Example Street',
                    'line2' => 'Apt 4B',
                    'city' => 'London',
                    'state' => 'England',
                    'postal_code' => 'SW1A 2AA',
                    'country' => 'GB',
                ],
            ]);

            return response()->json(['message' => $stripeCustomer]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(
                ['message' => 'Failed to create customer.'],
                Response::HTTP_BAD_REQUEST,
            );
        }
    }
}
