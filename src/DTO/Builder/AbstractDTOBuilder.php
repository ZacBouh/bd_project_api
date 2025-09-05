<?php

namespace App\DTO\Builder;

use App\DTO\UploadedImage\UploadedImageDTOBuilder;
use App\Entity\ArtistTitleContribution;
use App\Entity\Publisher;
use App\Entity\Skill;
use App\Entity\UploadedImage;
use App\Entity\User;
use App\Entity\Title;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

// use ArrayObject;

/**
 *  @template O of object the entity we take as input when normalizing from an entity.
 * 
 */
abstract class AbstractDTOBuilder implements ServiceSubscriberInterface
{

    use ServiceMethodsSubscriberTrait;

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
    protected EntityManagerInterface $em;

    public function __construct() {}

    // public function __construct(
    //     ?NormalizerInterface $norm = null,
    //     ?DenormalizerInterface $denorm = null,
    //     ?LoggerInterface $log = null,
    //     ?EntityManagerInterface $entityManager = null,
    //     ?UploadedImageDTOBuilder $imgDTOBuilder = null,
    // ) {
    //     if (!is_null($norm)) {
    //         $this->normalizer = $norm;
    //     }
    //     if (!is_null($denorm)) {
    //         $this->denormalizer = $denorm;
    //     }
    //     if (!is_null($log)) {
    //         $this->logger = $log;
    //     }
    //     if (!is_null($imgDTOBuilder)) {
    //         $this->imageDTOBuilder = $imgDTOBuilder;
    //     }
    //     if (!is_null($entityManager)) {
    //         $this->em = $entityManager;
    //     }
    // }

    public static function getSubscribedServices(): array
    {
        return [
            NormalizerInterface::class       => NormalizerInterface::class,
            DenormalizerInterface::class     => DenormalizerInterface::class,
            LoggerInterface::class           => LoggerInterface::class,
            EntityManagerInterface::class    => EntityManagerInterface::class,
            UploadedImageDTOBuilder::class   => UploadedImageDTOBuilder::class,
        ];
    }

    #[Required]
    public function setDependencies(
        NormalizerInterface $normalizer,
        DenormalizerInterface $denormalizer,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        UploadedImageDTOBuilder $imageDTOBuilder,
    ): void {
        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;
        $this->logger = $logger;
        $this->em = $entityManager;
        $this->imageDTOBuilder = $imageDTOBuilder;
    }

    #[SubscribedService]
    protected function normalizer(): NormalizerInterface
    {
        return $this->container->get(__METHOD__); //@phpstan-ignore-line
    }
    #[SubscribedService]
    protected function denormalizer(): DenormalizerInterface
    {
        return $this->container->get(__METHOD__); //@phpstan-ignore-line
    }
    #[SubscribedService]
    protected function logger(): LoggerInterface
    {
        return $this->container->get(__METHOD__); //@phpstan-ignore-line
    }
    #[SubscribedService]
    protected function em(): EntityManagerInterface
    {
        return $this->container->get(__METHOD__); //@phpstan-ignore-line
    }
    #[SubscribedService]
    protected function imageDTOBuilder(): UploadedImageDTOBuilder
    {
        return $this->container->get(__METHOD__); //@phpstan-ignore-line
    }

    public function __get(string $name): mixed
    {
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }
        throw new LogicException("Undefined property: $name call on class " . __CLASS__);
    }


    /** 
     * Callbacks used by the denormalizer(Array -> Entity) to treat specific fields. 
     * @return array<string, callable(mixed): mixed> 
     */
    protected function getDenormalizerCallbacks(): array
    {
        return [
            'skill' => function ($skillName) {
                if (!is_string($skillName) && !is_integer($skillName)) {
                    throw new InvalidArgumentException(sprintf('Id for  %s must be a string or an int', Skill::class));
                }
                $ref = $this->em->getReference(Skill::class, $skillName);
                if (is_null($ref)) {
                    throw new InvalidArgumentException(sprintf('Could not get a ref for %s with id %s', Skill::class, $skillName));
                }
                return $ref;
            }
        ];
    }
    /**
     *  Callbacks used by the normalizer(Entity -> Array) to treat specific fields.
     * @return array<string, callable(mixed): mixed> 
     */
    protected function getNormalizerCallbacks(): array
    {
        return [
            'publisher' => fn($publisher) => Publisher::normalizeCallback($publisher),
            'coverImage' => function ($coverImage) {
                if ($coverImage instanceof UploadedImage) {
                    return $this->imageDTOBuilder->readDTOFromEntity($coverImage)->buildReadDTO();
                }
                $this->logger->warning("Provided coverImage was not a proper UploadedImage instance : " . json_encode($coverImage));
                return null;
            },
            'artistsContributions' => function ($contributions) {
                if (!is_array($contributions)) {
                    $this->logger->warning('artistsContributions was not an array');
                    return ['artistsContributions was not an array'];
                }
                $data = [];
                foreach ($contributions as $contribution) {
                    if (!($contribution instanceof ArtistTitleContribution)) {
                        $this->logger->warning('Contribution was not an ArtistTitleContribution instance : ' . json_encode($contribution));
                    } else {
                        $data[] = ArtistTitleContribution::normalizeCallback($contribution);
                    }
                }
                return $data;
            },
            'uploadedImages' => function ($images) {
                if (!is_array($images)) {
                    $this->logger->warning('uploadedImages was not an array : ' . json_encode($images));
                    return null;
                }
                $data = [];
                foreach ($images as $image) {
                    if (!($image instanceof UploadedImage)) {
                        $this->logger->warning('Image was not an UploadedImage instance : ' . json_encode($image));
                        $data[] = 'Image was not an UploadedImage instance';
                    } else {
                        $data[] = $this->imageDTOBuilder->readDTOFromEntity($image)->buildReadDTO();
                    }
                }
                return $data;
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
     * @throws \UnexpectedValueException
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
     * @throws \LogicException
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
     * @template TInputBagContent of scalar
     * @param InputBag<TInputBagContent> $inputBag
     * @throws \InvalidArgumentException
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
        $this->logger->critical(sprintf('Data content at Denormalization for class %s : %s ', $dtoClass, json_encode($this->data)));
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
