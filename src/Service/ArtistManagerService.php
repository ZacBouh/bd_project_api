<?php

namespace App\Service;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ArtistManagerService
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SkillRepository $skillRepository,
        private LoggerInterface $logger,
        private ArtistRepository $artistRepository
    ) {}

    public function createArtist(Artist $newArtist): Artist
    {
        $this->entityManager->persist($newArtist);
        $this->entityManager->flush($newArtist);

        return $newArtist;
    }

    public function getAll(): array
    {
        /** @var Array<int, Artist> $artists */
        $artists = $this->artistRepository->findBy([], limit: 200);
        return $artists;
    }
}
