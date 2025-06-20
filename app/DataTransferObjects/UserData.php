<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Illuminate\Support\Arr;

readonly class UserData
{
    public function __construct(
        public string $uuid,
        public ?string $email,
        public ?string $first_name,
        public ?string $last_name,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            uuid: Arr::get($data, 'uuid'),
            email: Arr::get($data, 'email'),
            first_name: Arr::get($data, 'first_name'),
            last_name: Arr::get($data, 'last_name'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
        ];
    }
}
