<?php

namespace App\DTO\PublisherCollection;

use App\DTO\Builder\AbstractDTOFactory;
use App\Entity\PublisherCollection;
use App\DTO\PublisherCollection\PublisherCollectionReadDTO;
use App\DTO\PublisherCollection\PublisherCollectionWriteDTO;
use App\Entity\Publisher;
use App\Enum\Language;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * @extends AbstractDTOFactory<PublisherCollection, PublisherCollectionReadDTO, PublisherCollectionWriteDTO>
 */
class PublisherCollectionDTOFactory extends AbstractDTOFactory
{

    public function writeDtoFromInputBag(InputBag $inputBag, ?FileBag $files = null): object
    {
        $dto = new PublisherCollectionWriteDTO(
            $inputBag->getString('name'),
            $inputBag->getInt('publisherId'),
            $inputBag->getString('language'),
            $this->getIdInput($inputBag),
            $this->getArray($inputBag, 'titleIds'),
            $inputBag->getString('description') !== '' ? $inputBag->getString('description') : null,
            $inputBag->getString('birthDate') !== '' ? $inputBag->getString('birthDate') :  null,
            $inputBag->getString('deathDate') !== '' ? $inputBag->getString('deathDate') :  null,
            $this->getCoverImageFile($files)
        );
        return $dto;
    }

    public function readDtoFromEntity(object $entity): object
    {
        $id = $this->validateId($entity);
        $publisher = Publisher::normalizeCallback($entity->getPublisher());
        $language = $entity->getLanguage()?->value;
        if (is_null($language)) {
            throw new InvalidArgumentException("PublisherCollection entity has no language");
        }
        $coverImage = $entity->getCoverImage();
        if (!is_null($coverImage)) {
            $coverImage = $this->imageDTOFactory->readDtoFromEntity($coverImage);
        }
        $dto = new PublisherCollectionReadDTO(
            $id,
            $entity->getName(),
            $publisher,
            $language,
            $entity->getCreatedAt()->format('Y-m-d'),
            $entity->getUpdatedAt()->format('Y-m-d'),
            [],
            $entity->getDescription(),
            $entity->getBirthDate()?->format('Y-m-d'),
            $entity->getDeathDate()?->format('Y-m-d'),
            $coverImage
        );

        return $dto;
    }
}
