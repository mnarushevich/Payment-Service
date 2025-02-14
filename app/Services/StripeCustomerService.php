<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\StripeCustomerException;
use App\Models\User;
use Stripe\Customer;

class StripeCustomerService
{
    /**
     * @throws StripeCustomerException
     */
    public function createCustomer(User $user): Customer
    {
        try {
            return $user->createAsStripeCustomer([
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
        } catch (\Exception $e) {
            throw new StripeCustomerException($e->getMessage());
        }
    }
}
