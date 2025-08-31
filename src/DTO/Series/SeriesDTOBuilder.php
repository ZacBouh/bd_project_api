<?php

namespace App\DTO\Series;

use App\DTO\Builder\AbstractDTOBuilder;
use App\DTO\Publisher\PublisherDTOBuilder;
use App\Entity\Series;
use App\Entity\Publisher;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use App\Enum\Language;
use App\Enum\OnGoingStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @extends AbstractDTOBuilder<Series>
 */
class SeriesDTOBuilder extends AbstractDTOBuilder
{
    protected function getDenormalizerIgnoredAttributes(): array
    {
        return ['titles'];
    }

    protected function getDenormalizerCallbacks(): array
    {
        return ['publisher' => fn($publisher) => $publisher instanceof Publisher ?  Publisher::normalizeCallback($publisher) : throw new \InvalidArgumentException('Series publisher property is not a Publisher')];
    }

    public function buildWriteDTO(): SeriesWriteDTO
    {
        return parent::denormalizeToDTO(SeriesWriteDTO::class);
    }

    public function buildReadDTO(): SeriesReadDTO
    {
        return parent::denormalizeToDTO(SeriesReadDTO::class);
    }
}
