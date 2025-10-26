<?php

namespace App\Service;

use App\DTO\Artist\ArtistDTOFactory;
use App\DTO\Artist\ArtistReadDTO;
use App\Entity\Artist;
use App\Mapper\ArtistEntityMapper;
use App\Repository\ArtistRepository;
use App\Security\Role;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArtistManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ArtistRepository $artistRepository,
        private UploadedImageService $imageService,
        private ValidatorInterface $validator,
        private ArtistEntityMapper $artistMapper,
        private ArtistDTOFactory $dtoFactory,
        private LoggerInterface $logger,
        private Security $security,
    ) {}

    /**
     * @param InputBag<scalar> $newArtistContent
     */
    public function createArtist(InputBag $newArtistContent, FileBag $files): Artist
    {
        $dto = $this->dtoFactory->writeDtoFromInputBag($newArtistContent, $files);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }

        $coverImage = null;
        if (!is_null($dto->coverImageFile)) {
            $coverImage = $this->imageService->saveUploadedImage($dto->coverImageFile, 'Artist Picture');
        }
        $entity = $this->artistMapper->fromWriteDTO($dto, extra: ['coverImage' => $coverImage]);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * @param InputBag<scalar> $inputBag
     */
    public function updateArtist(InputBag $inputBag, FileBag $files): Artist
    {
        $dto = $this->dtoFactory->writeDtoFromInputBag($inputBag, $files);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }
        if (is_null($dto->id)) {
            throw new InvalidArgumentException('Update artist : id is null');
        }

        /** @var Artist|null $artist */
        $artist = $this->artistRepository->find($dto->id);
        if (is_null($artist) || $artist->isDeleted()) {
            throw new ResourceNotFoundException('No artist was found for id ' . $dto->id);
        }

        $extra = [];
        if (!is_null($dto->coverImageFile)) {
            $extra['coverImage'] = $this->imageService->saveUploadedImage($dto->coverImageFile, 'Artist Picture');
        }

        $artist = $this->artistMapper->fromWriteDTO($dto, $artist, $extra);
        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $artist;
    }

    public function removeArtist(int $artistId, bool $hardDelete = false): void
    {
        /** @var Artist|null $artist */
        $artist = $this->artistRepository->find($artistId);
        if (is_null($artist)) {
            throw new ResourceNotFoundException('No artist was found for id ' . $artistId);
        }

        if ($artist->isDeleted() && !$hardDelete) {
            throw new ResourceNotFoundException('No artist was found for id ' . $artistId);
        }

        if ($hardDelete) {
            if (!$this->security->isGranted(Role::ADMIN->value)) {
                throw new AccessDeniedException('Hard delete requires administrator role');
            }
            $this->entityManager->remove($artist);
        } else {
            $artist->markAsDeleted();
            $this->entityManager->persist($artist);
        }

        $this->entityManager->flush();
    }

    /**
     * @return array<ArtistReadDTO>
     */
    public function getAll(): array
    {
        /** @var array<Artist> $artists */
        $artists = $this->artistRepository->findWithAllRelations();
        $data = [];
        foreach ($artists as $artist) {
            $data[] = $this->dtoFactory->readDtoFromEntity($artist);
        }
        return $data;
    }

    /**
     * @return array<ArtistReadDTO>
     */
    public function searchArtist(string $query, int $limit = 200, int $offset = 0): array
    {
        $this->logger->debug("Searching for artists with query : $query");

        if (trim($query, " \n\r\t\v\0") == "") {
            throw new InvalidArgumentException("Cannot search title with an empty string as query");
        }
        $queryWords = preg_split('/\s+/', trim($query));
        if ($queryWords === false) {
            throw new InvalidArgumentException('The query does not contain any valid word');
        }
        $queryWords = array_filter($queryWords);
        $query =  implode(' ', array_map(fn($word) => "+$word*", $queryWords));

        $artists = $this->artistRepository->searchArtist($query, $limit, $offset);
        /**@var array<ArtistReadDTO> $dtos */
        $dtos = [];
        foreach ($artists as $artist) {
            $dtos[] = $this->dtoFactory->readDtoFromEntity($artist);
        }
        return $dtos;
    }
}
