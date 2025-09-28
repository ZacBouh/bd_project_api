<?php

namespace App\DTO\Title;

use App\DTO\TitleContribution\ArtistTitleContributionReadDTO;
use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Entity\Trait\HasDefaultNormalizeCallback;

/**
 * @phpstan-import-type NormalizeCallbackDefaultReturn from HasDefaultNormalizeCallback
 */
class TitleReadDTO
{
    /**
     * @param NormalizeCallbackDefaultReturn $publisher
     * @param array<UploadedImageReadDTO> $uploadedImages
     * @param array<mixed> $artistsContributions
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $publisher,
        public string $language,
        public ?string $description,
        public ?array $artistsContributions,
        public ?UploadedImageReadDTO $coverImage,
        public ?string $releaseDate,
        public ?array $uploadedImages,
        public ?string $isbn
    ) {}
}
