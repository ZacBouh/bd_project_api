<?php

namespace App\DTO\Publisher;

use App\DTO\Builder\AbstractDTOFactory;
use App\Entity\Publisher;
use App\DTO\Publisher\PublisherReadDTO;
use App\DTO\Publisher\PublisherWriteDTO;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * @extends AbstractDTOFactory<Publisher, PublisherReadDTO, PublisherWriteDTO>
 */
class PublisherDTOFactory extends AbstractDTOFactory
{

    public function writeDtoFromInputBag(InputBag $inputBag, ?FileBag $files = null): object
    {
        $dto = new PublisherWriteDTO(
            $inputBag->getString('name'),
            $this->getIdInput($inputBag),
            $inputBag->getString('description') !== '' ? $inputBag->getString('description') : null,
            $this->getArray($inputBag, 'titles'),
            $inputBag->getString('birthDate') !== '' ? $inputBag->getString('birthDate') : null,
            $inputBag->getString('deathDate') !== '' ? $inputBag->getString('deathDate') : null,
            $this->getCoverImageFile($files),
            $this->getUploadedImagesFiles($files)
        );
        return $dto;
    }

    public function readDtoFromEntity(object $entity): object
    {
        $id = $this->validateId($entity);

        $coverImage = $entity->getCoverImage();
        if ($coverImage) {
            $coverImage = $this->imageDTOFactory->readDtoFromEntity($coverImage);
        }

        $dto = new PublisherReadDTO(
            $id,
            $entity->getName(),
            $entity->getDescription(),
            $coverImage,
            [],
            [],
            $entity->getBirthDate()?->format('Y-m-d'),
            $entity->getDeathDate()?->format('Y-m-d'),
            $entity->getCreatedAt()->format('Y-m-d'),
            $entity->getUpdatedAt()->format('Y-m-d'),
        );

        return $dto;
    }
}
