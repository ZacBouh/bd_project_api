<?php

namespace App\DTO\Title;

use App\DTO\Builder\AbstractDTOBuilder;
use App\DTO\UploadedImage\UploadedImageDTOBuilder;
use App\Entity\ArtistTitleContribution;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Entity\Title;
use App\Entity\Publisher;
use App\Entity\UploadedImage;

/**
 * @extends AbstractDTOBuilder<Title>
 */
class TitleDTOBuilder extends AbstractDTOBuilder
{
    public function buildReadDTO(): TitleReadDTO
    {
        return parent::denormalizeToDTO(TitleReadDTO::class);
    }

    public function buildWriteDTO(): TitleWriteDTO
    {
        return parent::denormalizeToDTO(TitleWriteDTO::class);
    }

    protected function getNormalizerCallbacks(): array
    {
        return [
            'publisher' => fn($publisher) => Publisher::normalizeCallback($publisher),
            'coverImage' => fn($coverImage) => $coverImage instanceof UploadedImage ? $this->imageDTOBuilder->readDTOFromEntity($coverImage)->buildReadDTO() : "Provided coverImage was not a proper UploadedImage instance",
            'artistsContributions' => function ($contributions) {
                if (!is_array($contributions)) {
                    $this->logger->warning('artistsContributions was not an array');
                    return ['artistsContributions was not an array'];
                }
                $data = [];
                foreach ($contributions as $contribution) {
                    if (!($contribution instanceof ArtistTitleContribution)) {
                        $this->logger->warning('Contribution was not an ArtistTitleContribution instance');
                        $data[] = 'Contribution was not an ArtistTitleContribution instance';
                    } else {
                        $data[] = ArtistTitleContribution::normalizeCallback($contribution);
                    }
                }
                return $data;
            },
            '$uploadedImages' => function ($images) {
                if (!is_array($images)) {
                    $this->logger->warning('images was not an array');
                    return ['images was not an array'];
                }
                $data = [];
                foreach ($images as $image) {
                    if (!($image instanceof UploadedImage)) {
                        $this->logger->warning('Image was not an UploadedImage instance');
                        $data[] = 'Image was not an UploadedImage instance';
                    } else {
                        $data[] = $this->imageDTOBuilder->readDTOFromEntity($image)->buildReadDTO();
                    }
                }
            }
        ];
    }
}
