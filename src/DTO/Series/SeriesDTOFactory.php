<?php

namespace App\DTO\Series;

use App\DTO\Builder\AbstractDTOFactory;
use App\Entity\Series;
use App\DTO\Series\SeriesReadDTO;
use App\DTO\Series\SeriesWriteDTO;
use App\Entity\Publisher;
use App\Enum\Language;
use App\Enum\OnGoingStatus;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * @extends AbstractDTOFactory<Series, SeriesReadDTO, SeriesWriteDTO>
 */
class SeriesDTOFactory extends AbstractDTOFactory
{

    public function writeDtoFromInputBag(InputBag $inputBag, ?FileBag $files = null): object
    {
        $dto = new SeriesWriteDTO(
            $inputBag->getString('name'),
            $inputBag->getInt('publisherId'),
            $inputBag->getEnum('language', Language::class, Language::FR),
            $this->getIdInput($inputBag),
            $this->getArray($inputBag, 'titlesId'),
            $inputBag->getEnum('onGoingStatus', OnGoingStatus::class),
            $this->getCoverImageFile($files)
        );
        return $dto;
    }

    public function readDtoFromEntity(object $entity): object
    {
        $id = $this->validateId($entity);

        $publisher = Publisher::normalizeCallback($entity->getPublisher());

        $name = $this->validateName($entity);

        $language = $entity->getLanguage()?->value;
        if (is_null($language)) {
            throw new InvalidArgumentException('Series missing language property');
        }
        $coverImage = $entity->getCoverImage();
        if (!is_null($coverImage)) {
            $coverImage = $this->imageDTOFactory->readDtoFromEntity($coverImage);
        }

        $dto = new SeriesReadDTO(
            $id,
            $name,
            $publisher,
            $language,
            $entity->getCreatedAt()->format('Y-m-d'),
            $entity->getUpdatedAt()->format('Y-m-d'),
            [],
            $entity->getOnGoingStatus()?->value,
            $coverImage
        );
        return $dto;
    }
}
