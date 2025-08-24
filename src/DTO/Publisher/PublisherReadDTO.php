<?php

namespace App\DTO\Publisher;

class PublisherReadDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?array $coverImage,
        public ?array $uploadedImages,
        public ?array $titles,
        public ?\DateTime $birthDate,
        public ?\DateTime $deathDate,
        public \DateTime $createdAt,
        public \DateTime $updatedAt,
    ) {}
}
