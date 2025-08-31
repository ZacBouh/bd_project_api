<?php

namespace App\DTO\PublisherCollection;

use App\DTO\Builder\AbstractDTOBuilder;
use App\Entity\UploadedImage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Entity\Publisher;
use App\Entity\Title;
use App\Entity\PublisherCollection;

/**
 * @extends AbstractDTOBuilder<PublisherCollection>
 */
class PublisherCollectionDTOBuilder extends AbstractDTOBuilder
{

    public function buildWriteDTO(): PublisherCollectionWriteDTO
    {
        $dto = parent::denormalizeToDTO(PublisherCollectionWriteDTO::class);
        return $dto;
    }

    /**
     * @return array<callable>
     */
    protected function getNormalizerCallbacks(): array
    {
        return [
            'publisher' => fn(Publisher $publisher) => Publisher::normalizeCallback($publisher),
            'titles' => function ($titles) {
                /** @var Title[] $titles */
                $data = [];
                foreach ($titles as $title) {
                    $data[] = Title::normalizeCallback($title);
                }
                return $data;
            },
            'coverImage' => fn(?UploadedImage $coverImage = null) => is_null($coverImage) ? null : ['id' => $coverImage->getId(), 'imageName' => $coverImage->getImageName()],
        ];
    }

    public function buildReadDTO(): PublisherCollectionReadDTO
    {
        $dto = parent::denormalizeToDTO(PublisherCollectionReadDTO::class);
        return $dto;
    }
}
