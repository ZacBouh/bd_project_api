<?php

namespace App\Service;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Entity\Artist;
use App\Entity\Copy;
use App\Entity\Publisher;
use App\Entity\PublisherCollection;
use App\Entity\Series;
use App\Entity\Title;
use App\Entity\UploadedImage;
use App\Repository\UploadedImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;

class UploadedImageService
{
    /**
     * @var class-string<HasUploadedImagesInterface>[]
     */
    private const COVER_IMAGE_ENTITIES = [
        Artist::class,
        Copy::class,
        Publisher::class,
        PublisherCollection::class,
        Series::class,
        Title::class,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UploadedImageRepository $uploadedImageRepository,
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

    /**
     * @return UploadedImage[]
     */
    public function getAllImages(): array
    {
        return $this->uploadedImageRepository->findBy([], ['createdAt' => 'DESC']);
    }

    public function deleteUploadedImage(int $imageId): void
    {
        $image = $this->uploadedImageRepository->find($imageId);

        if (is_null($image)) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(UploadedImage::class, ['id' => (string) $imageId]);
        }

        $this->detachCoverImageReferences($image);

        $this->entityManager->remove($image);
        $this->entityManager->flush();
    }

    private function detachCoverImageReferences(UploadedImage $image): void
    {
        foreach (self::COVER_IMAGE_ENTITIES as $entityClass) {
            $repository = $this->entityManager->getRepository($entityClass);
            /** @var HasUploadedImagesInterface[] $entities */
            $entities = $repository->findBy(['coverImage' => $image]);
            foreach ($entities as $entity) {
                $entity->removeUploadedImage($image);
            }
        }
    }
}
