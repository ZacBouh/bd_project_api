<?php

namespace App\DTO\Publisher;

use App\DTO\Builder\AbstractDTOBuilder;
use App\Entity\Publisher;


/**
 * @extends AbstractDTOBuilder<Publisher>
 */
class PublisherDTOBuilder extends AbstractDTOBuilder
{

    /** 
     * @param Publisher $entity 
     */
    public function fromEntity(object $entity): static
    {
        return parent::readDTOFromEntity($entity);
    }

    /**
     * @param class-string<PublisherReadDTO>|class-string<PublisherWriteDTO> $dtoClass
     */
    public function build(string $dtoClass = PublisherReadDTO::class): object
    {
        return parent::denormalizeToDTO($dtoClass);
    }

    protected function getNormalizerIgnoredAttributes(): array
    {
        return [
            'titles'
        ];
    }
}
