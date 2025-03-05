<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use Symfony\Component\HttpFoundation\Response;

describe('GET /payment/success', function () {
    it(' checks that payment success page is available', function () {
        $this->get(
            getUrl('payment.success')
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Payment was successful.']);
    });
})->group('no-auth');
