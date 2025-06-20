<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StripeWebhookEventsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

final class HandleWebhookController
{
    public function __invoke(
        Request $request,
        StripeWebhookEventsService $webhookEventsService
    ): JsonResponse {
        Log::info('Webhook received', $request->all());

        try {
            $webhookEventsService->handle(request: $request);
        } catch (UnexpectedValueException) {
            return response()->json(['message' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException) {
            return response()->json(['message' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        } catch (Exception $exception) {
            Log::error('Error processing webhook', ['exception' => $exception->getMessage()]);

            return response()->json(['message' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['message' => 'Webhook received.']);
    }
}
