<?php

namespace App\Service;

use App\DTO\Artist\ArtistReadDTOBuilder;
use App\DTO\Artist\ArtistWriteDTO;
use App\Entity\Artist;
use App\Mapper\ArtistEntityMapper;
use App\Repository\ArtistRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        private ArtistReadDTOBuilder $dtoBuilder,
        private ValidatorInterface $validator,
        private ArtistEntityMapper $artistMapper,
    ) {}

    /**
     * @param InputBag<scalar> $newArtistContent
     */
    public function createArtist(InputBag $newArtistContent, FileBag $files): Artist
    {
        $dto = $this->dtoBuilder->writeDTOFromInputBags($newArtistContent, $files)->denormalizeToDTO(ArtistWriteDTO::class);
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
     * @return array<Artist>
     */
    public function getAll(): array
    {
        /** @var Array<int, Artist> $artists */
        $artists = $this->artistRepository->findBy([], limit: 200);
        return $artists;
    }
}
