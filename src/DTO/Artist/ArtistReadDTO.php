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
        public ?string $pseudo,
        public ?string $birthDate,
        public ?string $deathDate,
        public array $skills,
        public ?UploadedImageReadDTO $coverImage,
        public array $uploadedImages,
        public ?array $titlesContributions,
        public ?\DateTime $createdAt,
        public ?\DateTime $updatedAt,
    ) {}
}
