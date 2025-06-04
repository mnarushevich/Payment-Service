<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use Symfony\Component\HttpFoundation\Response;

describe('GET /healthcheck', function (): void {
    it('checks that application status is OK', function (): void {
        $this->get(
            getUrl('healthcheck')
        )
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['status' => 'ok']);
    });
})->group('no-auth');
