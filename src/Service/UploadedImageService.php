<?php

namespace App\Service;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Entity\UploadedImage;
use App\Repository\UploadedImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;

class UploadedImageService
{
    public function __construct(
        private LoggerInterface $logger,
        private UploadedImageRepository $uploadedImageRepo,
        private EntityManagerInterface $entityManager,
    ) {}

    public function saveUploadedCoverImage(HasUploadedImagesInterface $entity, UploadedFile $file, ?string $imageName = "Cover Image")
    {
        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException("File argument passed to saveUploadedCoverImage is not Symfony\Component\HttpFoundation\File\UploadedFile");
        }

        $coverImage = new UploadedImage();
        $coverImage->setFile($file);
        $coverImage->setImageName($imageName);
        $this->entityManager->persist($coverImage);
        $this->entityManager->flush();
        $entity->setCoverImage($coverImage);
    }
}
