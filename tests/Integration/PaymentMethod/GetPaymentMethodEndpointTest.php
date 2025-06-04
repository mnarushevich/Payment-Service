<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use Mockery;
use Stripe\Collection;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Response;

afterEach(function (): void {
    Mockery::close();
});

describe('GET /payment-method/list', function (): void {
    it('rejects when token is not provided', function (): void {
        $this->getJson(getUrl('payment-method.list'))
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
        $this->getJson(getUrl('payment-method.list'), headers: getAuthorizationHeader($token))
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => sprintf('User with ID %s not found.', $internalUserId),
            ]);
    });

    it('returns payment for valid token and existing stripe customer', function (): void {
        $stripeMock = Mockery::mock(StripeClient::class);
        $mockStripeResponse = getMockData('get-payment-methods');
        $mockCollection = new Collection;
        $mockCollection->data = $mockStripeResponse;

        $stripeMock->paymentMethods = Mockery::mock();
        $stripeMock->paymentMethods
            ->shouldReceive('all')
            ->once()
            ->andReturn($mockCollection);
        app()->bind(StripeClient::class, fn () => $stripeMock);

        $token = generateJWTToken($this->user->internal_user_id);
        $this->getJson(getUrl('payment-method.list'), headers: getAuthorizationHeader($token))
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['payment_methods' => $mockStripeResponse]);
    });
});
