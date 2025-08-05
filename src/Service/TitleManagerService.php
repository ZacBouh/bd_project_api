<?php

namespace App\Service;

use App\Entity\Title;
use App\Repository\PublisherRepository;
use App\Repository\TitleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TitleManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TitleRepository $titleRepository,
        private LoggerInterface $logger,
        private SerializerInterface $serializer,
        private PublisherRepository $publisherRepository,
    ) {}

    public function createTitle(array $newTitleContent)
    {
        $publisher = $this->publisherRepository->findOneBy(['id' => $newTitleContent['publisher']]);
        $this->logger->warning("Deserialized data : " . $publisher->getName());

        return $publisher;
    }

    public function getTitles(): array
    {
        /** @var Array<int, Title> $titles */
        $titles = $this->titleRepository->findBy([], limit: 200);
        return $titles;
    }
}
