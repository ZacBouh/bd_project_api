<?php

namespace App\DTO\User;

class UserReadDTO
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        public int $id,
        public string $pseudo,
        public string $email,
        public array $roles,
        public ?string $googleSub,
        public bool $emailVerified,
    ) {}
}
