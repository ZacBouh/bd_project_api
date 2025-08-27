<?php

namespace App\DTO\PublisherCollection;

use App\DTO\Publisher\PublisherReadDTO;

class PublisherCollectionReadDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public PublisherReadDTO $publisher,
        public string $language,
        public string $createdAt,
        public string $updatedAt,
        public ?array $titles,
        public ?string $description,
        public ?string $birthDate,
        public ?string $deathDate,
        public ?array $coverImage,
    ) {}
}
