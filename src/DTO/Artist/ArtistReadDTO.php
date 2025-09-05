<?php

namespace App\DTO\Artist;

use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\DTO\TitleContribution\ArtistTitleContributionReadDTO;

class ArtistReadDTO
{
    /**
     * @param string[] $skills
     * @param array<int, UploadedImageReadDTO> $uploadedImages
     * @param array<int, ArtistTitleContributionReadDTO>|null $titlesContributions
     */
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $pseudo,
        public array $skills,
        public string $createdAt,
        public string $updatedAt,
        public ?string $birthDate,
        public ?string $deathDate,
        public ?UploadedImageReadDTO $coverImage,
        public ?array $uploadedImages,
        public ?array $titlesContributions,
    ) {}
}
