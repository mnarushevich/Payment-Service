<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Services\UserService;
use Mockery;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

afterEach(function (): void {
    Mockery::close();
});

describe('POST /payment-method/add', function (): void {
    it('rejects when token is not provided', function (): void {
        $this->postJson(getUrl('payment-method.add'))
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
            getUrl('payment-method.add'),
            data: [
                'payment_method' => 'pm_card_visa',
                'set_as_default' => true,
            ],
            headers: getAuthorizationHeader($token)
        )
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => sprintf('User with ID %s not found.', $internalUserId),
            ]);
    });

    it('returns 400 in case invalid request', function (): void {
        $internalUserId = 'invalid-internal-user-id';
        $token = generateJWTToken($internalUserId);
        $this->postJson(
            getUrl('payment-method.add'),
            headers: getAuthorizationHeader($token)
        )
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'The payment method field is required.',
            ]);
    });

    it('returns creates payment for valid payment method and set it as default', function (): void {
        $mockPaymentMethod = 'pm_card_visa';
        $stripeMock = Mockery::mock(StripeClient::class);
        $setupIntent = Mockery::mock();
        $setupIntent->payment_method = $mockPaymentMethod;

        $stripeMock->setupIntents = Mockery::mock();
        $stripeMock->setupIntents
            ->shouldReceive('create')
            ->once()
            ->andReturn($setupIntent);

        $userMock = Mockery::mock($this->user)->makePartial();
        $userMock->shouldReceive('addPaymentMethod')
            ->once()
            ->with($mockPaymentMethod)
            ->andReturnTrue();
        $userMock->shouldReceive('updateDefaultPaymentMethod')
            ->once()
            ->with($mockPaymentMethod)
            ->andReturnTrue();

        $mockUserService = Mockery::mock(UserService::class)->makePartial();
        $mockUserService->shouldReceive('getByInternalUserId')->once()->andReturn($userMock);
        app()->bind(UserService::class, fn () => $mockUserService);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->postJson(
            getUrl('payment-method.add'),
            data: [
                'payment_method' => $mockPaymentMethod,
                'set_as_default' => true,
            ],
            headers: getAuthorizationHeader($token),
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Payment method added successfully',
                'payment_method' => $setupIntent->payment_method,
            ]);
    });
});
