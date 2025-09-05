<?php

namespace App\DTO\Artist;

use App\DTO\Builder\AbstractDTOFactory;
use App\DTO\Artist\ArtistReadDTO;
use App\DTO\Artist\ArtistWriteDTO;
use App\DTO\UploadedImage\UploadedImageDTOFactory;
use App\Entity\Artist;
use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * @extends AbstractDTOFactory<Artist, ArtistReadDTO, ArtistWriteDTO>
 */
class ArtistDTOFactory extends AbstractDTOFactory
{
    public function __construct(
        private UploadedImageDTOFactory $imageDtoFactory
    ) {}

    /**
     * @param InputBag<scalar> $inputBag
     * @param FileBag|null $files
     * @return ArtistWriteDTO
     */
    public function writeDtoFromInputBag(InputBag $inputBag, ?FileBag $files = null): object
    {
        $id = $this->getIdInput($inputBag);
        $dto = new ArtistWriteDTO(
            $id,
            $inputBag->getString('firstName', 'unknown'),
            $inputBag->getString('lastName', 'unknown'),
            $inputBag->getString('pseudo', 'unknown'),
            $this->getArray($inputBag, 'skills'), //@phpstan-ignore-line
            $this->getCoverImageFile($files),
            $inputBag->getString('birthDate') !== '' ? $inputBag->getString('birthDate') : null,
            $inputBag->getString('deathDate') !== '' ? $inputBag->getString('deathDate') : null
        );
        return $dto;
    }

    public function readDtoFromEntity(object $artistEntity): ArtistReadDTO
    {
        $id = $artistEntity->getId();
        if (is_null($id)) {
            $message = 'This entity is not persisted its id is null ' . $artistEntity::class;
            $this->logger->error("ArtistDTOFactory : $message");
            throw new InvalidArgumentException($message);
        }
        $skills = [];
        if (!is_null($artistEntity->getSkills())) {
            foreach ($artistEntity->getSkills() as $skill) {
                $skills[] = $skill->getName();
            }
        }
        $coverImage = $artistEntity->getCoverImage();
        if (!is_null($coverImage)) {
            $coverImage = $this->imageDtoFactory->readDtoFromEntity($coverImage);
        }
        return new ArtistReadDTO(
            $id,
            $artistEntity->getFirstName() ?? '',
            $artistEntity->getLastName() ?? '',
            $artistEntity->getPseudo() ?? '',
            $skills,
            $artistEntity->getCreatedAt()->format('Y-m-d'),
            $artistEntity->getUpdatedAt()->format('Y-m-d'),
            $artistEntity->getBirthDate()?->format('Y-m-d'),
            $artistEntity->getDeathDate()?->format('Y-m-d'),
            $coverImage,
            null,
            null,
        );
    }
}
