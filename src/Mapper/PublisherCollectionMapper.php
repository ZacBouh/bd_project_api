<?php

namespace App\Mapper;

use App\Entity\PublisherCollection;
use App\Entity\Publisher;
use App\DTO\PublisherCollection\PublisherCollectionWriteDTO;

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
        return parent::fromWriteDTO($dto, $entity, $extra);
    }

    protected function getDenormalizerIgnoredFields(): array
    {
        // return [...parent::getDenormalizerIgnoredFields(), 'titleIds', 'publisherId'];
        return [...parent::getDenormalizerIgnoredFields()];
    }

    /** 
     * @param PublisherCollection $entity 
     * @param PublisherCollectionWriteDTO $dto 
     * @return PublisherCollection
     */
    protected function afterDenormalization(object $dto, object $entity, array $extra = []): object
    {
        // $entity = parent::afterDenormalization($dto, $entity, $extra);
        // $ref = $this->em->getReference(Publisher::class, $dto->publisherId);
        // if (!is_null($ref)) {
        //     $entity->setPublisher($ref);
        // }
        return $entity;
    }
}
