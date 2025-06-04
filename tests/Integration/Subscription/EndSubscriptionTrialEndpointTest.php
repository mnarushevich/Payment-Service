<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Services\UserService;
use Laravel\Cashier\Subscription;
use Mockery;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

afterEach(function (): void {
    Mockery::close();
});

describe('POST /subscription/end-trial', function (): void {
    it('rejects when token is not provided', function (): void {
        $this->postJson(getUrl('subscription.end-trial'))
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
            getUrl('subscription.end-trial'),
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
            getUrl('subscription.end-trial'),
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'The type field is required.',
            ]);
    });

    it('returns 404 in case active subscription not found', function (): void {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('onGracePeriod')->never();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturnNull();

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.end-trial'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson(['message' => 'Subscription not found.']);
    });

    it('skips ending trial in case subscription is not on trial period', function (): void {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('onTrial')->once()->andReturnFalse();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturn($mockSubscription);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.end-trial'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Subscription is not on trial.']);
    });

    it('ends subscription trial', function (): void {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('onTrial')->once()->andReturnTrue();
        $mockSubscription->shouldReceive('endTrial')->once()->andReturnTrue();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturn($mockSubscription);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.end-trial'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Subscription trial ended.']);
    });

    it('returns 500 in case stripe request exception', function (): void {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('onTrial')->andThrows(new \Exception('Stripe error'));
        $mockSubscription->shouldReceive('endTrial')->never();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturn($mockSubscription);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.end-trial'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(['message' => 'Failed to end subscription trial.']);
    });
});
