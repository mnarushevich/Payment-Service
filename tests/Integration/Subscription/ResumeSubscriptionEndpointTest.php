<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Services\UserService;
use Laravel\Cashier\Subscription;
use Mockery;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

afterEach(function () {
    Mockery::close();
});

describe('POST /subscription/resume', function () {
    it('rejects when token is not provided', function () {
        $this->postJson(getUrl('subscription.resume'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson(
                [
                    'status' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Token not provided.',
                ]
            );
    });

    it('returns 404 in case user from JWT token not found', function () {
        $internalUserId = 'invalid-internal-user-id';
        $token = generateJWTToken($internalUserId);
        $this->postJson(
            getUrl('subscription.resume'),
            data: ['type' => 'silver'],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => "User with ID $internalUserId not found.",
            ]);
    });

    it('returns 400 in case invalid request', function () {
        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.resume'),
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'The type field is required.',
            ]);
    });

    it('returns 404 in case active subscription not found', function () {
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
            getUrl('subscription.resume'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson(['message' => 'Subscription not found.']);
    });

    it('skips resuming in case subscription is not on grace period', function () {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('onGracePeriod')->once()->andReturnFalse();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturn($mockSubscription);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.resume'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Subscription is not on grace period.']);
    });

    it('resumes subscription', function () {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('onGracePeriod')->once()->andReturnTrue();
        $mockSubscription->shouldReceive('resume')->once()->andReturnTrue();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturn($mockSubscription);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.resume'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Subscription resumed.']);
    });

    it('returns 500 in case stripe request exception', function () {
        $mockPaymentMethodType = 'silver';
        $stripeMock = Mockery::mock(StripeClient::class);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->shouldReceive('onGracePeriod')->andThrows(new \Exception('Stripe error'));
        $mockSubscription->shouldReceive('resume')->never();

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('subscription')->once()->with($mockPaymentMethodType)->andReturn($mockSubscription);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('subscription.resume'),
            data: ['type' => $mockPaymentMethodType],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(['message' => 'Failed to resume subscription.']);
    });
});
