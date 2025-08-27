<?php

namespace App\DTO\PublisherCollection;

use App\DTO\Builder\AbstractDTOBuilder;
use App\DTO\Publisher\PublisherDTOBuilder;
use App\Entity\UploadedImage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PublisherCollectionDTOBuilder extends AbstractDTOBuilder
{
    public function __construct(
        NormalizerInterface $normalizer,
        DenormalizerInterface $denormalizer,
        LoggerInterface $logger,
        private PublisherDTOBuilder $pubDTOBuilder,
    ) {
        parent::__construct($normalizer, $denormalizer, $logger);
    }

    public function buildWriteDTO(): PublisherCollectionWriteDTO
    {
        $dto = parent::denormalizeToDTO(PublisherCollectionWriteDTO::class);
        return $dto;
    }

    protected function getNormalizerCallbacks(): array
    {
        return [
            'publisher' => fn($publisher) => $this->pubDTOBuilder->fromEntity($publisher)->build(),
            'titles' => fn($titles) => ['normalization callback not implemented check PublisherCollectionDTOBuilder'],
            'coverImage' => fn(?UploadedImage $coverImage = null) => is_null($coverImage) ? null : ['id' => $coverImage->getId(), 'imageName' => $coverImage->getImageName()],
        ];
    }

    public function buildReadDTO(): PublisherCollectionReadDTO
    {
        $dto = parent::denormalizeToDTO(PublisherCollectionReadDTO::class);
        return $dto;
    }
}
