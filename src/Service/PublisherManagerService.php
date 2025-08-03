<?php

namespace App\Service;

use App\Repository\PublisherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Publisher;

class PublisherManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PublisherRepository $publisherRepository,
        private LoggerInterface $logger,
    ) {}

    public function createPublisher(Publisher $newPublisher): Publisher
    {
        $this->entityManager->persist($newPublisher);
        $this->entityManager->flush();
        return $newPublisher;
    }
}
