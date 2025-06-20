<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\StripeEventTypes;
use App\Models\WebhookEvent;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookEventsService
{
    /**
     * @throws SignatureVerificationException
     * @throws Exception
     */
    public function handle(Request $request): void
    {
        $payload = $request->getContent();

        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (! $webhookSecret) {
            Log::warning('Stripe webhook secret is not set.');
            throw new SignatureVerificationException('Stripe webhook secret is not set.');
        }

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $webhookSecret
            );
        } catch (UnexpectedValueException $unexpectedValueException) {
            Log::error($unexpectedValueException->getMessage());
            throw $unexpectedValueException;
        }

        if (in_array($event->type, array_column(StripeEventTypes::cases(), 'value'), true)) {
            $this->process($event);
        } else {
            Log::info('Unhandled event type: '.$event->type);
        }
    }

    /**
     * @throws Exception
     */
    private function process(Event $event): void
    {
        Log::info('Processing event: '.$event->type, ['event_id' => $event->id]);

        try {
            WebhookEvent::create([
                'event_id' => $event->id,
                'event_type' => $event->type,
                'event_data' => $event->toArray(),
                'processed_at' => now(),
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to save event: '.$event->type, ['event_id' => $event->id, 'error' => $exception->getMessage()]);
            throw $exception;
        }

        Log::info('Processed event: '.$event->type, ['event_id' => $event->id]);
    }
}
