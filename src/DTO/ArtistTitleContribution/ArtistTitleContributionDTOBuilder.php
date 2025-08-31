<?php

namespace App\DTO\TitleContribution;

use App\DTO\Builder\AbstractDTOBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Entity\ArtistTitleContribution;
use App\Entity\Artist;

/**
 * @extends AbstractDTOBuilder<ArtistTitleContribution>
 */
class ArtistTitleContributionDTOBuilder extends AbstractDTOBuilder
{
    protected function getNormalizerCallbacks(): array
    {
        return [
            'artist' => fn($artist) => Artist::normalizeCallback($artist),
            'title' => fn($artist) =>  Artist::normalizeCallback($artist)
        ];
    }

    public function buildWriteDTO(): ArtistTitleContributionWriteDTO
    {
        $dto = parent::denormalizeToDTO(ArtistTitleContributionWriteDTO::class);
        return $dto;
    }

    public function buildReadDTO(): ArtistTitleContributionReadDTO
    {
        $dto = parent::denormalizeToDTO(ArtistTitleContributionReadDTO::class);
        return $dto;
    }
}
