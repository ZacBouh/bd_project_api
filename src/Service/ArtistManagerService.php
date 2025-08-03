<?php

namespace App\Service;

use App\Entity\Artist;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ArtistManagerService
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SkillRepository $skillRepository,
        private LoggerInterface $logger,
    ) {}

    public function createArtist(Artist $newArtist): Artist
    {
        $this->entityManager->persist($newArtist);
        $this->entityManager->flush($newArtist);

        return $newArtist;
    }
}
