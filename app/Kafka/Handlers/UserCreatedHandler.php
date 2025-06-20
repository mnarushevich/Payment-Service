<?php

declare(strict_types=1);

namespace App\Kafka\Handlers;

use App\DataTransferObjects\UserData;
use App\Exceptions\StripeCustomerException;
use App\Models\User;
use App\Services\StripeCustomerService;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;

readonly class UserCreatedHandler
{
    public function __construct(private StripeCustomerService $stripeCustomerService) {}

    public function __invoke(ConsumerMessage $message): void
    {
        $messageBody = json_decode((string) $message->getBody(), true);
        $userData = UserData::fromArray($messageBody);

        $user = User::firstOrCreate(
            ['internal_user_id' => $userData->uuid],
        );

        if ($user->wasRecentlyCreated) {
            try {
                $stripeCustomer = $this->stripeCustomerService->createCustomer($user, $userData->toArray());
                Log::info('Stripe customer created for user.', [
                    'user_id' => $user->id,
                    'internal_user_id' => $user->internal_user_id,
                    'stripe_customer_id' => $stripeCustomer->id,
                ]);
            } catch (StripeCustomerException $e) {
                Log::error('Failed to create Stripe customer.', [
                    'internal_user_id' => $userData->uuid,
                    'exception' => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('User with internal_user_id already exists, skipping Stripe customer creation.',
                ['internal_user_id' => $userData->uuid],
            );
        }
    }
}
