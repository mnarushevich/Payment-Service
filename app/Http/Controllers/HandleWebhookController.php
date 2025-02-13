<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HandleWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Webhook received', $payload);

        return response()->json(['message' => 'Webhook received.']);
    }
}
