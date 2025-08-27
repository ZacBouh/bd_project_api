<?php

namespace App\Mapper;

use App\Entity\PublisherCollection;
use App\Entity\Publisher;
use App\DTO\PublisherCollection\PublisherCollectionWriteDTO;

class PublisherCollectionMapper extends AbstractEntityMapper
{

    public static $COVER_IMAGE_FILE = 'coverImageFile';

    protected function getEntityClass(): string
    {
        return PublisherCollection::class;
    }

    protected function instantiateEntity(): object
    {
        return new PublisherCollection();
    }

    public function fromWriteDTO(object $dto, ?object $entity = null, array $extra = []): PublisherCollection
    {
        return parent::fromWriteDTO($dto, $entity, $extra);
    }

    protected function getIgnoredFields(): array
    {
        return [...parent::getIgnoredFields(), 'titleIds', 'publisherId'];
    }


    /** 
     * @param PublisherCollection $entity 
     * @param PublisherCollectionWriteDTO $dto 
     */
    protected function afterDenormalization(object $dto, object $entity, array $extra = []): object
    {
        parent::afterDenormalization($dto, $entity, $extra);
        $entity->setPublisher($this->em->getReference(Publisher::class, $dto->publisherId));
        return $entity;
    }
}
