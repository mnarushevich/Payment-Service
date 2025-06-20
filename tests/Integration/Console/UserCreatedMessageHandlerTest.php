<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use App\Models\User;
use App\Services\StripeCustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('it processes the message and creates a stripe customer', function (): void {
    $topic = config('kafka.topics.user.created');
    $messageBody = [
        'uuid' => Str::uuid()->toString(),
        'email' => 'test@example.com',
        'first_name' => 'Test',
        'last_name' => 'User',
    ];

    Kafka::fake();
    Kafka::shouldReceiveMessages([
        new ConsumedMessage(
            topicName: $topic,
            partition: 0,
            headers: [],
            body: json_encode($messageBody),
            key: null,
            offset: 0,
            timestamp: 0
        ),
    ]);

    $this->mock(StripeCustomerService::class, function (MockInterface $mock) use ($messageBody): void {
        $mock->shouldReceive('createCustomer')
            ->once()
            ->withArgs(fn (User $user, array $userData): bool => $user->internal_user_id === $messageBody['uuid']
                && $userData === $messageBody);
    });

    Artisan::call('consumer:user-created');
});
