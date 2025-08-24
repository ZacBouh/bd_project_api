<?php

namespace App\DTO\Series;

use App\DTO\Publisher\PublisherDTOBuilder;
use App\Entity\Series;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use App\Enum\Language;
use App\Enum\OnGoingStatus;
use Normalizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SeriesDTOBuilder
{
    private array $data = [];
    private array $denomalizationCallbacks;

    public function __construct(
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private LoggerInterface $logger,
        private PublisherDTOBuilder $publisherDTOBuilder,
    ) {
        $this->denomalizationCallbacks = [
            'language' => fn($value) => $value instanceof Language ? $value : Language::from($value),
            'onGoingStatus' => fn($value) => $value instanceof OnGoingStatus ? $value : OnGoingStatus::tryFrom($value),
        ];
    }

    public function writeDTOFromInputBags(InputBag $inputBag, FileBag $fileBag): static
    {
        $this->data = $inputBag->all();
        $imageFile = $fileBag->get('coverImageFile');
        if ($imageFile !== null && !$imageFile instanceof UploadedFile) {
            throw new \InvalidArgumentException('coverImageFile must be an UploadedFile.');
        }
        $this->data['coverImageFile'] = $imageFile;
        return $this;
    }

    public function readDTOFromEntity(Series $series): static
    {
        $this->data = $this->normalizer->normalize($series, 'array', [
            AbstractObjectNormalizer::IGNORED_ATTRIBUTES => [
                'publisher',
                'titles'
            ],

        ]);

        $this->data['publisher'] = [
            'id' => $series->getPublisher()->getId(),
            'name' => $series->getPublisher()->getName()
        ];
        return $this;
    }

    public function buildWriteDTO(): SeriesWriteDTO
    {
        /** @var SeriesWriteDTO $dto */
        $dto = $this->denormalizer->denormalize($this->data, SeriesWriteDTO::class, null, [
            AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            AbstractObjectNormalizer::CALLBACKS => $this->denomalizationCallbacks,
            AbstractObjectNormalizer::IGNORED_ATTRIBUTES => ['coverImageFile'],
        ]);
        if ($this->data['coverImageFile']) {
            $dto->coverImageFile = $this->data['coverImageFile'];
        }
        return $dto;
    }

    public function buildReadDTO(): SeriesReadDTO
    {
        $this->logger->critical('Content of the data to build dto ' . json_encode($this->data));
        $dto = $this->denormalizer->denormalize($this->data, SeriesReadDTO::class, 'array', []);

        return $dto;
    }
}
