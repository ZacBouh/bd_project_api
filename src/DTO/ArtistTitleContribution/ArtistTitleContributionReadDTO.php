<?php

namespace App\DTO\TitleContribution;

use App\DTO\Artist\ArtistReadDTO;
use App\DTO\Title\TitleReadDTO;
use App\Entity\Trait\HasDefaultNormalizeCallback;

/**
 * @phpstan-import-type NormalizeCallbackDefaultReturn from HasDefaultNormalizeCallback
 */
class ArtistTitleContributionReadDTO
{
    /**
     * @param array<NormalizeCallbackDefaultReturn> $artist
     * @param array<NormalizeCallbackDefaultReturn> $title
     */
    public function __construct(
        public int $id,
        public array $artist,
        public array $title,
        public string $skill,
    ) {}
}
