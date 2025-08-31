<?php

namespace App\DTO\User;

class UserWriteDTO
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        public ?int $id,
        public ?string $pseudo,
        public string $email,
        public ?string $password,
        public ?array $roles,
        public ?string $googleSub,
    ) {}
}
