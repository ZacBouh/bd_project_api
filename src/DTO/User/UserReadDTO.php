<?php

namespace App\DTO\User;

use OpenApi\Attributes as OA;

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
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
        #[OA\Property(format: 'date-time')]
        public string $updatedAt,
    ) {}
}
