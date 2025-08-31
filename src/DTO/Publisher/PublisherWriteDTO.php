<?php

namespace App\DTO\Publisher;

class PublisherWriteDTO
{
    /**
     * @param int[] $uploadedImages
     * @param int[] $titles
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $description,
        public ?int $coverImage,
        public ?array $uploadedImages,
        public ?array $titles,
        public ?string $birthDate,
        public ?string $deathDate,
    ) {}
}
