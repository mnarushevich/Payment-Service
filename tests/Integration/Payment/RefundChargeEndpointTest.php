<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Services\UserService;
use Mockery;
use Stripe\Refund;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

afterEach(function () {
    Mockery::close();
});

describe('POST /charge/refund', function () {
    it('rejects when token is not provided', function () {
        $this->postJson(getUrl('charge.refund'))
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
            getUrl('charge.refund'),
            data: ['payment_id' => 'test_payment_id'],
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
        $this->postJson(getUrl('charge.refund'), headers: getAuthorizationHeader($token))
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'The payment id field is required.',
            ]);
    });

    it('makes a refund', function () {
        $stripeMock = Mockery::mock(StripeClient::class);
        $mockRefund = Mockery::mock(Refund::class);

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('refund')->once()->andReturn($mockRefund);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('charge.refund'),
            data: ['payment_id' => 'test_payment_id'],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Payment refunded.']);
    });

    it('returns 500 in case stripe request exception', function () {
        $stripeMock = Mockery::mock(StripeClient::class);

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('refund')->once()->andThrows(new \Exception);

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('charge.refund'),
            data: ['payment_id' => 'test_payment_id'],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson(['message' => 'Failed to refund payment.']);
    });
});
