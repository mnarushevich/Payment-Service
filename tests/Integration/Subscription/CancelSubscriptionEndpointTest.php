<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Services\UserService;
use Carbon\Carbon;
use Laravel\Cashier\Subscription;
use Mockery;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

afterEach(function (): void {
    Mockery::close();
});

describe('POST /subscription/cancel', function (): void {
    it('rejects when token is not provided', function (): void {
        $this->postJson(getUrl('subscription.cancel'))
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
            getUrl('subscription.cancel'),
            data: ['type' => 'silver'],
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
            getUrl('subscription.cancel'),
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'The type field is required.',
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
            getUrl('subscription.cancel'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'message' => sprintf('User already subscribed to %s subscription.', $mockPaymentMethodType),
            ]);
    });

    it('cancels subscription', function (): void {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('cancel')->once()->andReturnTrue();
        $mockSubscription->shouldReceive('cancelNow')->never();
        $mockSubscription->shouldReceive('cancelAt')->never();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscribed')->once()->with($mockPaymentMethodType)->andReturnFalse();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturn($mockSubscription);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.cancel'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Subscription cancelled.']);
    });

    it('cancels subscription immediately', function (): void {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('cancelNow')->once()->andReturnTrue();
        $mockSubscription->shouldReceive('cancel')->never();
        $mockSubscription->shouldReceive('cancelAt')->never();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscribed')->once()->with($mockPaymentMethodType)->andReturnFalse();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturn($mockSubscription);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.cancel'),
            data: ['type' => $mockPaymentMethodType, 'is_cancel_now' => true],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Subscription cancelled.']);
    });

    it('cancels subscription after number of days', function (): void {
        $mockPaymentMethodType = 'silver';
        $cancelAfterNumberDays = 10;
        $stripeMock = Mockery::mock(StripeClient::class);
        $testNow = Carbon::parse('2025-02-13 12:00:00');
        Carbon::setTestNow($testNow);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('cancelAt')->once()->with('2025-02-23 12:00:00')->andReturnTrue();
        $mockSubscription->shouldReceive('cancel')->never();
        $mockSubscription->shouldReceive('cancelNow')->never();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscribed')->once()->with($mockPaymentMethodType)->andReturnFalse();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturn($mockSubscription);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.cancel'),
            data: ['type' => $mockPaymentMethodType, 'cancel_after_num_days' => $cancelAfterNumberDays],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => sprintf('Subscription will be cancelled after %d days.', $cancelAfterNumberDays)]);
    });

    it('returns 500 in case stripe request exception', function (): void {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('cancelNow')->andThrows(new \Exception('Stripe error'));
        $mockSubscription->shouldReceive('cancel')->never();
        $mockSubscription->shouldReceive('cancelAt')->never();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscribed')->once()->with($mockPaymentMethodType)->andReturnFalse();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturn($mockSubscription);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.cancel'),
            data: ['type' => $mockPaymentMethodType, 'is_cancel_now' => true],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(['message' => 'Failed to cancel subscription.']);
    });
});
