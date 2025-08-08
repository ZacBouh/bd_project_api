<?php

namespace App\Service;

use App\DTO\Publisher\PublisherDTOBuilder;
use App\DTO\Publisher\PublisherDTOFactory;
use App\Repository\PublisherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Publisher;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;

class PublisherManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PublisherRepository $publisherRepository,
        private LoggerInterface $logger,
        private UploadedImageService $imageService,
        private PublisherDTOFactory $dtoFactory,
        private PublisherDTOBuilder $dtoBuilder,
    ) {}

    public function createPublisher(InputBag $newPublisherContent, ?FileBag $files): Publisher
    {
        $newPublisher = new Publisher();
        $newPublisher->setDescription($newPublisherContent->get('description'));
        $newPublisher->setBirthDate($newPublisherContent->get('birthDate'));
        $newPublisher->setDeathDate(new \DateTime($newPublisherContent->get('deathDate')));
        $newPublisher->setName($newPublisherContent->get('name'));

        if (!is_null($files)) {
            $this->imageService->saveUploadedCoverImage($newPublisher, $files, "Publisher Logo");
        }

        $this->entityManager->persist($newPublisher);
        $this->entityManager->flush();
        return $newPublisher;
    }

    /**
     * @return PublisherReadDTO[]
     */
    public function getPublishers(): array
    {
        $data = [];
        $publishers = $this->publisherRepository->findWithAllRelations();
        foreach ($publishers as $publisher) {
            // $data[] = $this->dtoFactory->createFromEntity($publisher);
            $builder = $this->dtoBuilder
                ->fromEntity($publisher)
                ->withTitlesIds()
                ->withUploadedImages();
            $data[] = $builder->build();
        }
        return $data;
    }
}
