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
        private EntityManagerInterface $entityManager,
    ) {}

    public function saveUploadedCoverImage(HasUploadedImagesInterface $entity, FileBag $files, string $imageName = "Cover Image"): void
    {
        $file = $files->get('coverImageFile');
        if (is_null($file)) {
            return;
        }
        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException("File argument passed to saveUploadedCoverImage is not a FileBag");
        }
        $coverImage = $this->saveUploadedImage($file, $imageName);
        $entity->setCoverImage($coverImage);
    }

    public function saveUploadedImage(UploadedFile $file, string $imageName = "unnamed"): UploadedImage
    {
        $image =  (new UploadedImage())
            ->setFile($file)
            ->setImageName($imageName);
        $this->entityManager->persist($image);
        $this->entityManager->flush();
        return $image;
    }
}
