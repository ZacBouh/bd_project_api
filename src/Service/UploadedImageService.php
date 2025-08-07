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

    public function saveUploadedCoverImage(HasUploadedImagesInterface $entity, FileBag $files, ?string $imageName = "Cover Image")
    {
        $file = $files->get('coverImageFile');
        if (is_null($file)) {
            return false;
        }
        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException("File argument passed to saveUploadedCoverImage is not a FileBag");
        }
        $coverImage = new UploadedImage();
        $coverImage->setFile($file);
        $coverImage->setImageName($imageName);
        $this->entityManager->persist($coverImage);
        $this->entityManager->flush();
        $entity->setCoverImage($coverImage);
    }
}
