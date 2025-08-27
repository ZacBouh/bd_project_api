<?php

namespace App\DTO\Builder;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

abstract class AbstractDTOBuilder
{
    protected array $data = [];

    /** @var array<string, callable(mixed): mixed> */
    protected array $denormalizationCallbacks = [];
    protected array $normalizationCallabacks = [];
    /** @var array<int, string> */
    protected array $denormalizerIgnoredAttributes = [];
    protected array $normalizerIgnoredAttributes = [];

    public function __construct(
        protected NormalizerInterface $normalizer,
        protected DenormalizerInterface $denormalizer,
        protected ?LoggerInterface $logger = null,
    ) {}

    /** This allows children to override / augment denormalizerCallbacks cleanly */
    protected function getDenormalizerCallbacks(): array
    {
        return $this->denormalizationCallbacks;
    }
    protected function getNormalizerCallbacks(): array
    {
        return $this->normalizationCallabacks;
    }
    protected function getDenormalizerIgnoredAttributes(): array
    {
        return $this->denormalizerIgnoredAttributes;
    }
    protected function getNormalizerIgnoredAttributes(): array
    {
        return $this->normalizerIgnoredAttributes;
    }

    public function readDTOFromEntity(object $entity): static
    {
        $this->data = $this->normalizer->normalize($entity, 'array', [
            AbstractObjectNormalizer::IGNORED_ATTRIBUTES => $this->getNormalizerIgnoredAttributes(),
            AbstractObjectNormalizer::CALLBACKS => $this->getNormalizerCallbacks()
        ]);

        $this->afterNormalization($entity);
        return $this;
    }

    protected function denormalize(array $data, string $type, ?string $format = null, array $context = []): array
    {
        $context[AbstractObjectNormalizer::CALLBACKS] =
            ($context[AbstractObjectNormalizer::CALLBACKS] ?? []) + $this->getDenormalizerCallbacks();
        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    public function writeDTOFromInputBags(InputBag $inputBag, FileBag $fileBag): static
    {
        $this->data = $inputBag->all();
        $imageFile = $fileBag->get('coverImageFile');
        if ($imageFile !== null && !$imageFile instanceof UploadedFile) {
            throw new \InvalidArgumentException('coverImageFile must be an UploadedFile.');
        } else {
            $this->logger->critical('Yes it has a proper image');
        }
        $this->data['coverImageFile'] = $imageFile;
        return $this;
    }

    /**
     * @template T of object
     * @param class-string<T> $dtoClass
     * @return T
     */
    public function denormalizeToDTO(string $dtoClass): object
    {
        /** @var T $dto */
        $dto = $this->denormalizer->denormalize($this->data, $dtoClass, null, [
            AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            AbstractObjectNormalizer::CALLBACKS => $this->getDenormalizerCallbacks(),
            AbstractObjectNormalizer::IGNORED_ATTRIBUTES => $this->getDenormalizerIgnoredAttributes(),
        ]);
        if (property_exists($dto, 'coverImageFile')) {
            $dto->coverImageFile = $this->data['coverImageFile'] ?? null;
        }
        return $dto;
    }

    /**
     * Applied after normalization
     */
    protected function afterNormalization(object $entity) {}
}
