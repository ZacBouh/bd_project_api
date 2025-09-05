<?php

namespace App\DTO\Copy;

use App\DTO\Builder\AbstractDTOFactory;
use App\DTO\Copy\CopyReadDTO;
use App\DTO\Copy\CopyWriteDTO;
use App\Entity\Copy;
use App\Entity\User;
use App\Entity\Title;
use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * @extends AbstractDTOFactory<Copy, CopyReadDTO, CopyWriteDTO >
 */
class CopyDTOFactory extends AbstractDTOFactory
{

    public function writeDtoFromInputBag(InputBag $i, ?FileBag $f = null): object
    {
        $id = $this->getIdInput($i);
        $dto = new CopyWriteDTO(
            $i->getInt('ownerId'),
            $i->getInt('titleId'),
            $i->getEnum('copyCondition', CopyCondition::class, CopyCondition::POOR),
            $id,
            $this->getFloat($i, 'price'),
            $i->getEnum('currency', PriceCurrency::class),
            $this->getFloat($i, 'boughtForPrice'),
            $i->getEnum('boughtForCurrency', PriceCurrency::class),
            $this->getCoverImageFile($f),
            $this->getUploadedImagesFiles($f)
        );
        return $dto;
    }

    public function readDtoFromEntity(object $entity): object
    {
        $id = $this->validateId($entity);
        $owner = User::normalizeCallback($entity->getOwner());
        $title = Title::normalizeCallback($entity->getTitle());
        $coverImage = $entity->getCoverImage();
        if (!is_null($coverImage)) {
            $coverImage = $this->imageDTOFactory->readDtoFromEntity($coverImage);
        }

        $dto = new CopyReadDTO(
            $id,
            $owner,
            $title,
            $entity->getCopyCondition()->value,
            $entity->getCreatedAt()->format('Y-m-d'),
            $entity->getUpdatedAt()->format('Y-m-d'),
            $entity->getPrice(),
            $entity->getCurrency()?->value,
            $entity->getBoughtForPrice(),
            $entity->getBoughtForCurrency()?->value,
            $coverImage,
            []
        );

        return $dto;
    }
}
