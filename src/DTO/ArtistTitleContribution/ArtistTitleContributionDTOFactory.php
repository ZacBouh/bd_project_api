<?php

namespace App\DTO\ArtistTitleContribution;

use App\DTO\Builder\AbstractDTOFactory;
use App\DTO\ArtistTitleContribution\ArtistTitleContributionReadDTO;
use App\DTO\ArtistTitleContribution\ArtistTitleContributionWriteDTO;
use App\Entity\ArtistTitleContribution;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * @extends AbstractDTOFactory<ArtistTitleContribution, ArtistTitleContributionReadDTO, ArtistTitleContributionWriteDTO>
 */
class ArtistTitleContributionDTOFactory extends AbstractDTOFactory
{

    public function writeDtoFromInputBag(InputBag $i, ?FileBag $f = null): object
    {
        $id = $this->getIdInput($i);
        $dto = new ArtistTitleContributionWriteDTO(
            $i->getInt('artist'),
            $i->getInt('title'),
            $i->getString('skill'),
            $id
        );
        return $dto;
    }

    /**
     * @return ArtistTitleContributionReadDTO
     */
    public function readDtoFromEntity(object $entity): object
    {
        $id = $this->validateId($entity);
        $artist = $entity->getArtist();
        $title = $entity->getTitle();

        $skill = $entity->getSkill()->getName();

        $dto = new ArtistTitleContributionReadDTO(
            $id,
            ['id' => $artist->getId(), 'name' => $artist->getFullName()],
            ['id' => $title->getId(), 'name' => $this->validateName($title)],
            $skill
        );
        return $dto;
    }
}
