<?php

namespace App\DTO\TitleContribution;

use App\DTO\Artist\ArtistReadDTO;
use App\DTO\Title\TitleReadDTO;
use App\Enum\Skill;
use Symfony\Component\Validator\Constraints as Assert;

class ArtistTitleContributionWriteDTO
{
    public function __construct(
        #[Assert\Positive]
        public int $artist,
        #[Assert\Positive]
        public int $title,

        #[Assert\Choice(callback: 'skillChoices')]
        public string $skill,

        #[Assert\Positive]
        public ?int $id,
    ) {}

    /**
     * @return array<non-empty-string>
     */
    public function skillChoices(): array
    {
        return Skill::values();
    }
}
