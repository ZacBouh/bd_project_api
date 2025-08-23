<?php

namespace App\DTO\Copy;

use App\DTO\Builder\DTOBuilder;
use App\DTO\Builder\EntityDTOBuilderInterface;
use App\Entity\Copy;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CopyDTOBuilder implements EntityDTOBuilderInterface
{
    public function __construct(
        private DTOBuilder $builder,
        private LoggerInterface $logger,
    ) {}


    /** @param Copy $entity */
    public function fromEntity(object $entity, array|string $groups = []): static
    {
        $this->builder->fromEntity($entity, $groups);
        $this->builder->addField("owner", [
            "id" => $entity->getOwner()->getId(),
            "pseudo" => $entity->getOwner()->getPseudo()
        ]);
        $this->builder->addField("title", [
            "id" => $entity->getTitle()->getId(),
            "name" => $entity->getTitle()->getName(),
            "publisher" => [
                "id" => $entity->getTitle()->getPublisher()->getId(),
                "name" => $entity->getTitle()->getPublisher()->getName()
            ],
            "artistsContributions" => [
                ["artist" => [
                    "id" => $entity->getTitle()->getArtistsContributions()[0]->getArtist()->getId(),
                    "fullName" => $entity->getTitle()->getArtistsContributions()[0]->getArtist()->getFullName(),
                ]]
            ]
        ]);
        $this->builder->addCoverImage();
        return $this;
    }

    public function fromArray(array $array): static
    {
        $this->builder->fromArray($array);
        return $this;
    }

    public function withUploadedImages(): static
    {
        $this->builder->addUploadedImages();
        return $this;
    }

    public function addCoverImage(?string $propertyName = 'coverImage', ?UploadedFile $imageFile = null): static
    {
        $this->builder->addCoverImage($propertyName, $imageFile);
        return $this;
    }

    public function build(string $dtoClass = CopyReadDTO::class): object
    {
        return $this->builder->build($dtoClass);
    }
}
