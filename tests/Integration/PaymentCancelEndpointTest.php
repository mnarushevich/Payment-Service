<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use Symfony\Component\HttpFoundation\Response;

describe('GET /payment/cancel', function (): void {
    it(' checks that payment cancelled page is available', function (): void {
        $this->get(
            getUrl('payment.cancel')
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Payment was cancelled.']);
    });
})->group('no-auth');
