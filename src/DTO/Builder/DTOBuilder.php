<?php


namespace App\DTO\Builder;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Mapper\ImageMapper;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @template TEntity
 */
class DTOBuilder
{
    private array $data = [];
    /** @var TEntity */
    private object $entity;

    public function __construct(
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private ImageMapper $imageMapper,
    ) {}

    /**
     * @param TEntity $entity
     */
    public function fromEntity(object $entity, array|string $groups = []): self
    {

        $this->entity = $entity;
        $this->data = $this->normalizer->normalize($entity, null, [
            'groups' => (array) $groups
        ]);

        return $this;
    }

    public function addField(string $name, mixed $value): self
    {
        $this->data[$name] = $value;
        return $this;
    }

    public function addFields(array $fields): self
    {
        foreach ($fields as $name => $value) {
            $this->data[$name] = $value;
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
        return $this->denormalizer->denormalize($this->data, $dtoClass);
    }

    public function addCoverImage(?string $propertyName = 'coverImage'): self
    {
        $entity = $this->entity;
        if ($entity instanceof HasUploadedImagesInterface) {
            $coverImage = $this->imageMapper->mapWithUrl($entity->getCoverImage());
            $this->addField($propertyName, $coverImage);
        }

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
