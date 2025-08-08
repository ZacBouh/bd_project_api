<?php

namespace App\DTO\Builder;

/**
 * @template TEntity
 */
interface EntityDTOBuilderInterface
{

    /**
     * Sets the entity to transform and the serialization groups.
     *
     * @param TEntity $entity
     * @param array|string $groups
     * @return static
     */
    public function fromEntity(object $entity, array|string $groups = []): static;


    /**
     * Builds and returns the DTO instance.
     *
     * @template T
     * @param class-string<T> $dtoClass
     * @return T
     */
    public function build(string $dtoClass): object;
}
