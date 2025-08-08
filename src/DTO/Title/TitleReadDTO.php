<?php

namespace App\DTO\Title;

class TitleReadDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $language,
        public \DateTime $releaseDate,
        public ?string $description,
        public array $publisher,
        public array $artistsContributions,
        public array $uploadedImages,
        public array $coverImage
    ) {}
}
