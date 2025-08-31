<?php


namespace App\DTO\Builder;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Mapper\ImageMapper;
use App\Service\UploadedImageService;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @template TEntity
 */
class DTOBuilder
{
    private array $data = [];
    /** @var TEntity */
    private ?object $entity = null;

    public function __construct(
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private ImageMapper $imageMapper,
        private LoggerInterface $logger,
        private UploadedImageService $imageService,
    ) {}

    /**
     * @param TEntity $entity
     */
    public function fromEntity(object $entity, array|string $groups = []): self
    {

        $context = [
            'groups' => (array) $groups,
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => static function ($object) {
                return method_exists($object, 'getId') ? $object->getId() : null;
            },
            AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => 1
        ];

        $this->entity = $entity;
        $this->data = $this->normalizer->normalize($entity, null, $context);
        return $this;
    }

    public function fromArray(array $array): self
    {
        $this->data = $array;
        return $this;
    }

    public function addField(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function addFields(array $fields): self
    {
        foreach ($fields as $key => $value) {
            $this->data[$key] = $value;
        }
        return $this;
    }

    /**
     * @template T
     * @param class-string<T> $dtoClass
     * @return T 
     */
    public function build(string $dtoClass): object
    {
        // $this->logger->warning("Build data " . json_encode($this->data));
        return $this->denormalizer->denormalize($this->data, $dtoClass, null, [
            AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
        ]);
    }

    public function addCoverImage(?string $propertyName = 'coverImage', ?UploadedFile $imageFile = null): self
    {
        if (is_null($this->entity) && is_null($imageFile)) {
            throw new InvalidArgumentException('No entity nor $image argument provided : addCoverImage require that either the DTO is build from an entity or that an UploadedFile $image argument is provided');
        }

        if (is_null($imageFile)) {
            $coverImage = $this->imageMapper->mapWithUrl($this->entity->getCoverImage());
            $this->addField($propertyName, $coverImage);
            return $this;
        }

        if (!$imageFile instanceof UploadedFile || !$imageFile->isValid() || $imageFile->getSize() === 0) {
            $this->logger->error('No valid cover image uploaded', [
                'type'  => get_debug_type($imageFile),
                'err'   => $imageFile instanceof UploadedFile ? $imageFile->getErrorMessage() : null,
            ]);
            throw new InvalidArgumentException('Invalid image file provided : ' . $imageFile instanceof UploadedFile ? $imageFile->getErrorMessage() : "argument provided it not of UploadedFile type");
        }

        $coverImage = $this->imageService->saveUploadedImage($imageFile, "Cover Image");
        $coverImageWithUrl = $this->imageMapper->mapWithUrl($coverImage);
        $this->addField($propertyName, $coverImageWithUrl);
        return $this;
    }

    public function addUploadedImages(?string $propertyName = 'uploadedImages'): self
    {
        $entity = $this->entity;
        if ($entity instanceof HasUploadedImagesInterface) {
            $uploadedImages = $this->imageMapper->mapCollectionWithUrl($entity->getUploadedImages());
            $this->addField($propertyName, $uploadedImages);
        }
        return $this;
    }

    /**
     * @return TEntity
     */
    public function getEntity(): object
    {
        return $this->entity;
    }
}
