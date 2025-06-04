<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Services\UserService;
use Laravel\Cashier\SubscriptionBuilder;
use Mockery;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

afterEach(function (): void {
    Mockery::close();
});

describe('POST /subscription', function (): void {
    it('rejects when token is not provided', function (): void {
        $this->postJson(getUrl('subscription.create'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson(
                [
                    'status' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Token not provided.',
                ]
            );
    });

    it('returns 404 in case user from JWT token not found', function (): void {
        $internalUserId = 'invalid-internal-user-id';
        $token = generateJWTToken($internalUserId);
        $this->postJson(
            getUrl('subscription.create'),
            data: [
                'payment_method_id' => 'pm_12345',
                'set_as_default' => true,
            ],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => sprintf('User with ID %s not found.', $internalUserId),
            ]);
    });

    it('returns 400 in case invalid request', function (): void {
        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.create'),
            data: [
                'payment_method_id' => 12345,
            ],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'The payment method id field must be a string.',
            ]);
    });

    it('returns 400 in case already subscribed to this type', function (): void {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscribed')->once()->with($mockPaymentMethodType)->andReturnTrue();

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.create'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'message' => sprintf('User already subscribed to %s subscription.', $mockPaymentMethodType),
            ]);
    });

    it('returns 400 in case provided payment method ID not found', function (): void {
        $mockPaymentMethodId = 'pm_12345';
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscribed')->once()->with($mockPaymentMethodType)->andReturnFalse();
        $userMock->shouldReceive('findPaymentMethod')->once()->with($mockPaymentMethodId)->andReturnFalse();

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.create'),
            data: ['payment_method_id' => $mockPaymentMethodId, 'type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'message' => 'Payment method not found.',
            ]);
    });

    it('returns 400 in case default payment method not found', function (): void {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscribed')->once()->with($mockPaymentMethodType)->andReturnFalse();
        $userMock->shouldReceive('findPaymentMethod')->never();
        $userMock->shouldReceive('defaultPaymentMethod')->once()->andReturnFalse();

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.create'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'message' => 'No payment method found.',
            ]);
    });

    it('creates a subscription with default payment method', function (): void {
        $mockPaymentMethodType = 'silver';
        $defaultPaymentMethodId = 'default_payment_method_id';
        $stripeMock = Mockery::mock(StripeClient::class);
        $mockPaymentMethod = Mockery::mock();
        $mockPaymentMethod->id = $defaultPaymentMethodId;

        $mockStripeResponse = getMockData('create-subscription');
        $mockSubscriptionBuilder = Mockery::mock(SubscriptionBuilder::class);
        $mockSubscriptionBuilder->shouldReceive('trialDays')->never();
        $mockSubscriptionBuilder->shouldReceive('create')->once()->with(
            $mockPaymentMethod->id,
            ['metadata' => ['source' => 'payment-service']],
        )->andReturn($mockStripeResponse);

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscribed')->once()->with($mockPaymentMethodType)->andReturnFalse();
        $userMock->shouldReceive('findPaymentMethod')->never();
        $userMock->shouldReceive('defaultPaymentMethod')->once()->andReturn($mockPaymentMethod);
        $userMock->shouldReceive('newSubscription')->once()->andReturn($mockSubscriptionBuilder);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.create'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['subscription' => $mockStripeResponse]);
    });

    it('creates a subscription with provided payment method and with trial', function (): void {
        $mockPaymentMethodType = 'silver';
        $mockPaymentMethodId = 'pm_12345';
        $stripeMock = Mockery::mock(StripeClient::class);
        $mockPaymentMethod = Mockery::mock();
        $mockPaymentMethod->id = $mockPaymentMethodId;

        $mockStripeResponse = getMockData('create-subscription');
        $mockSubscriptionBuilder = Mockery::mock(SubscriptionBuilder::class);
        $mockSubscriptionBuilder->shouldReceive('trialDays')->once()->andReturn($mockSubscriptionBuilder);
        $mockSubscriptionBuilder->shouldReceive('create')->once()->with(
            $mockPaymentMethod->id,
            ['metadata' => ['source' => 'payment-service']],
        )->andReturn($mockStripeResponse);

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscribed')->once()->with($mockPaymentMethodType)->andReturnFalse();
        $userMock->shouldReceive('findPaymentMethod')->once()->andReturn($mockPaymentMethod);
        $userMock->shouldReceive('defaultPaymentMethod')->never();
        $userMock->shouldReceive('newSubscription')->once()->andReturn($mockSubscriptionBuilder);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.create'),
            data: ['type' => $mockPaymentMethodType, 'trial_num_days' => 10, 'payment_method_id' => $mockPaymentMethodId],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['subscription' => $mockStripeResponse]);
    });

    it('returns 500 in case stripe request exception', function (): void {
        $mockPaymentMethodType = 'silver';
        $mockPaymentMethodId = 'pm_12345';
        $stripeMock = Mockery::mock(StripeClient::class);
        $mockPaymentMethod = Mockery::mock();
        $mockPaymentMethod->id = $mockPaymentMethodId;

        $mockSubscriptionBuilder = Mockery::mock(SubscriptionBuilder::class);
        $mockSubscriptionBuilder->shouldReceive('trialDays')->never();
        $mockSubscriptionBuilder->shouldReceive('create')->never();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscribed')->once()->with($mockPaymentMethodType)->andReturnFalse();
        $userMock->shouldReceive('findPaymentMethod')->once()->andReturn($mockPaymentMethod);
        $userMock->shouldReceive('defaultPaymentMethod')->never();
        $userMock->shouldReceive('newSubscription')->once()->andThrows(new \Exception('Stripe error'));

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.create'),
            data: ['type' => $mockPaymentMethodType, 'payment_method_id' => $mockPaymentMethodId],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(['message' => 'Failed to create subscription.']);
    });
});
