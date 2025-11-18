<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\StripeCustomerException;
use App\Models\User;
use Illuminate\Support\Arr;
use Stripe\Customer;

class StripeCustomerService
{
    /**
     * @param  array<string, mixed>  $userData
     *
     * @throws StripeCustomerException
     */
    public function createCustomer(User $user, array $userData = []): Customer
    {
        try {
            return $user->createAsStripeCustomer([
                'email' => Arr::get($userData, 'email'),
                'name' => sprintf('%s %s', Arr::get($userData, 'first_name'), Arr::get($userData, 'last_name')),
                'phone' => '',
                'metadata' => [
                    'internal_user_id' => $user->internal_user_id,
                ],
                'address' => [],
            ]);
        } catch (\Exception $exception) {
            throw new StripeCustomerException($exception->getMessage());
        }
    }
}
