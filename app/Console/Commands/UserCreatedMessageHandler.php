<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Kafka\Handlers\UserCreatedHandler;
use Exception;
use Illuminate\Console\Command;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Facades\Kafka;

class UserCreatedMessageHandler extends Command
{
    protected $signature = 'consumer:user-created';

    protected $description = 'Handle user created messages in a long-running process';

    public function handle(): void
    {
        $consumer = Kafka::consumer()
            ->subscribe(config('kafka.topics.user_created'))
            ->withHandler(app(UserCreatedHandler::class))
            ->build();

        try {
            $this->info("Starting consumer for 'user.created' topic...");
            $consumer->consume();
        } catch (ConsumerException $e) {
            $this->error('Consumer error: '.$e->getMessage());
        } catch (Exception $e) {
            $this->error('An unexpected error occurred: '.$e->getMessage());
        } finally {
            $consumer->stopConsuming();
        }
    }
}
