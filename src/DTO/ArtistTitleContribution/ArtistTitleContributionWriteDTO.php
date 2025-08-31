<?php

namespace App\DTO\TitleContribution;

use App\DTO\Artist\ArtistReadDTO;
use App\DTO\Title\TitleReadDTO;

class ArtistTitleContributionWriteDTO
{
    public function __construct(
        public int $id,
        public int $artist,
        public int $title,
        public string $skill,
    ) {}
}
