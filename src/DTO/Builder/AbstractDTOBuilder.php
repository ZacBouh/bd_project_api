<?php

namespace App\DTO\Builder;

use App\DTO\UploadedImage\UploadedImageDTOBuilder;
use App\Entity\ArtistTitleContribution;
use App\Entity\Publisher;
use App\Entity\UploadedImage;
use App\Entity\User;
use App\Entity\Title;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

// use ArrayObject;

/**
 *  @template O of object the entity we take as input when normalizing from an entity.
 * 
 */
abstract class AbstractDTOBuilder
{
    /**
     * @var array<string, mixed>|\ArrayObject<string, mixed>
     */
    protected array|\ArrayObject $data = [];

    /** @var array<int, string> */
    protected array $denormalizerIgnoredAttributes = [];
    /** @var array<int, string> */
    protected array $normalizerIgnoredAttributes = [];

    protected NormalizerInterface $normalizer;
    protected DenormalizerInterface $denormalizer;
    protected LoggerInterface $logger;
    protected UploadedImageDTOBuilder $imageDTOBuilder;

    public function __construct(
        protected ContainerInterface $container,
        ?NormalizerInterface $norm = null,
        ?DenormalizerInterface $denorm = null,
        ?LoggerInterface $log = null,
        ?UploadedImageDTOBuilder $imgDTOBuilder = null,
    ) {
        /** @var NormalizerInterface $normalizer */
        $normalizer = $norm ?? $container->get(NormalizerInterface::class);
        /** @var DenormalizerInterface $denormalizer */
        $denormalizer = $denorm ?? $container->get(DenormalizerInterface::class);
        /** @var LoggerInterface $logger */
        $logger = $log ?? $container->get(LoggerInterface::class);
        /** @var UploadedImageDTOBuilder $imageDTOBuilder */
        $imageDTOBuilder = $imgDTOBuilder ?? $container->get(UploadedImageDTOBuilder::class);

        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;
        $this->logger = $logger;
        $this->imageDTOBuilder = $imageDTOBuilder;
    }


    /** 
     * Callbacks used by the denormalizer(Array -> Entity) to treat specific fields. 
     * @return array<string, callable(mixed): mixed> 
     */
    protected function getDenormalizerCallbacks(): array
    {
        return [];
    }
    /**
     *  Callbacks used by the normalizer(Entity -> Array) to treat specific fields.
     * @return array<string, callable(mixed): mixed> 
     */
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
            'uploadedImages' => function ($images) {
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
            },
            'owner' => fn($user) => User::normalizeCallback($user),
            'title' => fn($user) => Title::normalizeCallback($user),
        ];
    }

    /** @return array<int, string> */
    protected function getDenormalizerIgnoredAttributes(): array
    {
        return $this->denormalizerIgnoredAttributes;
    }
    /** @return array<int, string> */
    protected function getNormalizerIgnoredAttributes(): array
    {
        return $this->normalizerIgnoredAttributes;
    }

    /**
     * @param O $entity
     */
    public function readDTOFromEntity(object $entity): static
    {
        $normalized = $this->normalizer->normalize($entity, 'array', [
            AbstractObjectNormalizer::IGNORED_ATTRIBUTES => $this->getNormalizerIgnoredAttributes(),
            AbstractObjectNormalizer::CALLBACKS => $this->getNormalizerCallbacks()
        ]);

        if (!is_array($normalized) && !($normalized instanceof \ArrayObject)) {
            throw new \UnexpectedValueException(sprintf('Expected normalized data to be an array, got %s instead', $normalized));
        };
        /** @var array<string, mixed>|\ArrayObject<string, mixed> $normalized */
        $this->data = $normalized;

        $this->afterNormalization($entity);
        return $this;
    }


    /**
     * @param array<string, mixed>|string[] $context
     * @param array<string, mixed>|\ArrayObject<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function denormalize(array|\ArrayObject $data, string $type, ?string $format = null, array $context = []): array
    {
        $context[AbstractObjectNormalizer::CALLBACKS] =
            ($context[AbstractObjectNormalizer::CALLBACKS] ?? []) + $this->getDenormalizerCallbacks();
        $dataArray = $this->denormalizer->denormalize($data, $type, $format, $context);
        if (!is_array($dataArray)) {
            throw new \LogicException(sprintf("Denormalized data should be an array"));
        }
        /** @var array<string, mixed> $dataArray */
        return  $dataArray;
    }

    /**
     * @param InputBag<scalar> $inputBag
     */
    public function writeDTOFromInputBags(InputBag $inputBag, FileBag $fileBag): static
    {
        /** @var array<string, mixed> $all */
        $all = $inputBag->all();
        $this->data = $all;
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
     * @template T of object the dto object we want to build.
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
     * @param O $entity
     */
    protected function afterNormalization(object $entity): void {}
}
