<?php

namespace App\DTO\Publisher;

use App\DTO\Builder\DTOBuilder;
use App\DTO\Builder\EntityDTOBuilderInterface;
use App\Entity\Title;
use App\Entity\Publisher;

class PublisherDTOBuilder implements EntityDTOBuilderInterface
{
    public function __construct(
        private DTOBuilder $builder,
    ) {}


    /** @param Publisher $entity */
    public function fromEntity(object $entity, array|string $groups = []): static
    {
        $this->builder->fromEntity($entity, 'publisher:read');
        $this->builder->addCoverImage();
        return $this;
    }

    public function withTitlesIds(): static
    {
        $titlesIds = [];
        /** @var Title[] */
        $titleEntities = $this->builder->getEntity();
        foreach ($titleEntities as $title) {
            $titlesIds[] = $title->getId();
        }
        return $this;
    }

    public function withUploadedImages(): static
    {
        $this->builder->addUploadedImages();
        return $this;
    }

    public function build(string $dtoClass = PublisherReadDTO::class): object
    {
        return $this->builder->build($dtoClass);
    }
}
