<?php

namespace App\Service;

use App\Entity\Title;
use App\Repository\TitleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TitleManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TitleRepository $titleRepository,
        private LoggerInterface $logger,
    ) {}

    public function createTitle(Title $newTitle): Title
    {
        $this->entityManager->persist($newTitle);
        $this->entityManager->flush();

        return $newTitle;
    }

    public function getTitles(): array
    {
        /** @var Array<int, Title> $titles */
        $titles = $this->titleRepository->findBy([], limit: 200);
        return $titles;
    }
}
