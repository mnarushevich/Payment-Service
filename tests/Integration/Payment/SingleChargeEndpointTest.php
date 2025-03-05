<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Services\UserService;
use Mockery;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

afterEach(function () {
    Mockery::close();
});

describe('POST /charge', function () {
    it('rejects when token is not provided', function () {
        $this->postJson(getUrl('charge'))
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
            getUrl('charge'),
            data: ['amount' => 500],
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
        $this->postJson(getUrl('charge'), headers: getAuthorizationHeader($token))
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'The amount field is required.',
            ]);
    });

    it('returns 404 in case default payment method not found', function () {
        $stripeMock = Mockery::mock(StripeClient::class);

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('defaultPaymentMethod')->once()->andReturnNull();

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('charge'),
            data: ['amount' => 500],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'No payment method found.',
            ]);
    });

    it('makes a single payment', function () {
        $stripeMock = Mockery::mock(StripeClient::class);

        $defaultPaymentMethodId = 'default_payment_method_id';
        $mockPaymentMethod = Mockery::mock();
        $mockPaymentMethod->id = $defaultPaymentMethodId;

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('defaultPaymentMethod')->once()->andReturn($mockPaymentMethod);
        $mockStripeResponse = getMockData('single-payment');
        $userMock->shouldReceive('charge')->once()->andReturn($mockStripeResponse);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('charge'),
            data: ['amount' => 500],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
                'message' => 'Payment successful.',
                'payment' => $mockStripeResponse,
            ]);
    });

    it('returns 500 in case stripe request exception', function () {
        $stripeMock = Mockery::mock(StripeClient::class);

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('defaultPaymentMethod')->once()->andThrows(new \Exception);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('charge'),
            data: ['amount' => 500],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(['message' => 'Payment failed.']);
    });
});
