<?php

namespace App\Service;

use App\DTO\Artist\ArtistDTOFactory;
use App\DTO\Artist\ArtistReadDTO;
use App\Entity\Artist;
use App\Mapper\ArtistEntityMapper;
use App\Repository\ArtistRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
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
     * @return array<ArtistReadDTO>
     */
    public function getAll(): array
    {
        /** @var Array<int, Artist> $artists */
        $artists = $this->artistRepository->findBy([], limit: 200);
        $data = [];
        foreach ($artists as $artist) {
            $data[] = $this->dtoFactory->readDtoFromEntity($artist);
        }
        return $data;
    }

    /**
     * @return array<ArtistReadDTO>
     */
    public function searchArtist(string $query, int $limit = 200, int $offset = 200): array
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
