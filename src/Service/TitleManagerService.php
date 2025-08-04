<?php

namespace App\Service;

use App\Entity\Title;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TitleManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    public function createTitle(Title $newTitle): Title
    {
        $this->entityManager->persist($newTitle);
        $this->entityManager->flush();

        return $newTitle;
    }
}
