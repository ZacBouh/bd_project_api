<?php

namespace App\Mapper;

use App\Entity\PublisherCollection;
use App\Entity\Publisher;
use App\DTO\PublisherCollection\PublisherCollectionWriteDTO;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @extends AbstractEntityMapper<PublisherCollection, PublisherCollectionWriteDTO>
 */
class PublisherCollectionMapper extends AbstractEntityMapper
{

    protected function getEntityClass(): string
    {
        return PublisherCollection::class;
    }

    protected function instantiateEntity(): object
    {
        return new PublisherCollection();
    }

    /**
     * @param PublisherCollection|null $entity
     */
    public function fromWriteDTO(object $dto, ?object $entity = null, array $extra = []): PublisherCollection
    {
        $data = $this->normalizer->normalize($dto, 'array');
        $collection = $this->denormalizer->denormalize($data, PublisherCollection::class, 'array', [AbstractObjectNormalizer::IGNORED_ATTRIBUTES => ['coverImage', 'uploadedImages', 'titles']]);
        $collection = $this->afterDenormalization($dto, $collection, $extra);
        /** @var Publisher $publisher */
        $publisher = $this->em->getReference(Publisher::class, $dto->publisher);
        $collection->setPublisher($publisher);
        return $collection;
    }
}
