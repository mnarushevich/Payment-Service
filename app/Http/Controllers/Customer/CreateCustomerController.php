<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CreateCustomerController extends Controller
{
    public function __invoke(Request $request)
    {

        $request->validate([
            'external_user_id' => ['required'],
        ]);
        $user = new User;

        $user->external_user_id = $request->input('external_user_id');
        $user->save();

        try {
            $stripeCustomer = $user->createAsStripeCustomer([
                'email' => 'maksim@test.com',
                'name' => 'Maksim Narushevich',
                'phone' => '+66812345678',
                'metadata' => [
                    'external_user_id' => $user->external_user_id,
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
            dd($e);

            return response()->json(['message' => 'Failed to create customer.'], 400);
        }
    }
}
