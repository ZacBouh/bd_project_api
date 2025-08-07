<?php

namespace App\Service;

use App\Repository\PublisherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Publisher;
use App\Entity\UploadedImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;

class PublisherManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PublisherRepository $publisherRepository,
        private LoggerInterface $logger,
    ) {}

    public function createPublisher(InputBag $newPublisherContent, ?FileBag $files): Publisher
    {
        $newPublisher = new Publisher();
        $newPublisher->setDescription($newPublisherContent->get('description'));
        $newPublisher->setBirthDate($newPublisherContent->get('birthDate'));
        $newPublisher->setDeathDate(new \DateTime($newPublisherContent->get('deathDate')));
        $newPublisher->setName($newPublisherContent->get('name'));

        if (!is_null($files)) {
            $uploadedFile = $files->get('coverImageFile');
            if ($uploadedFile instanceof UploadedFile) {
                $coverImage = new UploadedImage();
                $coverImage->setFile($uploadedFile);
                $coverImage->setImageName("Cover");

                $newPublisher->setCoverImage($coverImage);
                $this->entityManager->persist($coverImage);
            }
        }

        $this->entityManager->persist($newPublisher);
        $this->entityManager->flush();
        return $newPublisher;
    }
}
