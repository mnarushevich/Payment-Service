<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Illuminate\Support\Arr;

class UserData
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $email,
        public readonly ?string $first_name,
        public readonly ?string $last_name,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::get($data, 'id'),
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
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
        ];
    }
}
