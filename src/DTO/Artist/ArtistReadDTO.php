<?php

namespace App\DTO\Artist;

class ArtistReadDTO
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public ?string $pseudo,
        public ?string $birthDate,
        public ?string $deathDate,
        public array $skills,
        public ?array $coverImage,
        public array $uploadedImages,
        public ?array $titlesContributions,
        public ?\DateTime $createdAt,
        public ?\DateTime $updatedAt,
    ) {}
}
