<?php

namespace App\DTO\Series;

use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use App\Enum\Language;
use App\Enum\OnGoingStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SeriesDTOBuilder
{
    private array $data = [];
    private array $denomalizationCallbacks;

    public function __construct(
        private DenormalizerInterface $denormalizer,
        private LoggerInterface $logger,
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
}
