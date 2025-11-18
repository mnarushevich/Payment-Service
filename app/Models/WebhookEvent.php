<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'event_type',
        'event_data',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'event_id' => 'string',
            'event_type' => 'string',
            'event_data' => 'array',
            'processed_at' => 'timestamp',
        ];
    }
}
