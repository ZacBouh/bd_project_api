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
use App\Security\Role;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UploadedImageService
{
    /**
     * @var class-string[]
     */
    private const IMAGE_OWNER_ENTITIES = [
        Artist::class,
        Copy::class,
        Publisher::class,
        PublisherCollection::class,
        Series::class,
        Title::class,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UploadedImageRepository $imageRepository,
        private Security $security,
        private LoggerInterface $logger,
    ) {}

    public function saveUploadedCoverImage(HasUploadedImagesInterface $entity, FileBag $files, string $imageName = "Cover Image"): void
    {
        $file = $files->get('coverImageFile');
        if (is_null($file)) {
            return;
        }
        if (!$file instanceof UploadedFile) {
            throw new InvalidArgumentException("File argument passed to saveUploadedCoverImage is not a FileBag");
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
     * @return list<UploadedImage>
     */
    public function getAllImages(): array
    {
        $this->assertAdminAccess();

        /** @var list<UploadedImage> $images */
        $images = $this->imageRepository->createQueryBuilder('image')
            ->orderBy('image.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $images;
    }

    public function updateImage(int $imageId, ?string $imageName = null, ?UploadedFile $file = null): UploadedImage
    {
        $this->assertAdminAccess();

        $image = $this->findImageOrFail($imageId);

        $hasChanges = false;
        if (!is_null($imageName)) {
            $trimmed = trim($imageName);
            if ($trimmed === '') {
                throw new InvalidArgumentException('imageName cannot be empty.');
            }
            $image->setImageName($trimmed);
            $hasChanges = true;
        }

        if (!is_null($file)) {
            $image->setFile($file);
            $hasChanges = true;
        }

        if (!$hasChanges) {
            throw new InvalidArgumentException('No modification provided for the uploaded image.');
        }

        $this->logger->info(sprintf('Updating uploaded image %d', $imageId));

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        return $image;
    }

    /**
     * @param list<int> $imageIds
     */
    public function removeImages(array $imageIds): void
    {
        $this->assertAdminAccess();

        if ($imageIds === []) {
            throw new InvalidArgumentException('At least one image id must be provided.');
        }

        $uniqueIds = array_values(array_unique($imageIds));

        /** @var array<int, UploadedImage> $imagesById */
        $imagesById = [];
        foreach ($uniqueIds as $imageId) {
            $image = $this->findImageOrFail($imageId);
            $imagesById[$imageId] = $image;
        }

        foreach ($imagesById as $imageId => $image) {
            $this->logger->info(sprintf('Removing uploaded image %d', $imageId));
            $this->detachImageFromEntities($image);
            $this->entityManager->remove($image);
        }

        $this->entityManager->flush();
    }

    private function assertAdminAccess(): void
    {
        if (!$this->security->isGranted(Role::ADMIN->value)) {
            throw new AccessDeniedException('Only administrators can manage uploaded images.');
        }
    }

    private function findImageOrFail(int $imageId): UploadedImage
    {
        $image = $this->imageRepository->find($imageId);
        if (!$image instanceof UploadedImage) {
            throw new ResourceNotFoundException('Uploaded image not found for id ' . $imageId);
        }

        return $image;
    }

    private function detachImageFromEntities(UploadedImage $image): void
    {
        foreach (self::IMAGE_OWNER_ENTITIES as $entityClass) {
            $repository = $this->entityManager->getRepository($entityClass);

            $entitiesWithCover = $repository->createQueryBuilder('owner')
                ->andWhere('owner.coverImage = :image')
                ->setParameter('image', $image)
                ->getQuery()
                ->getResult();

            foreach ($entitiesWithCover as $entity) {
                if (method_exists($entity, 'setCoverImage')) {
                    $entity->setCoverImage(null);
                }
                if (method_exists($entity, 'removeUploadedImage')) {
                    $entity->removeUploadedImage($image);
                }
                $this->entityManager->persist($entity);
            }

            $entitiesWithImage = $repository->createQueryBuilder('owner')
                ->innerJoin('owner.uploadedImages', 'uploadedImage')
                ->andWhere('uploadedImage = :image')
                ->setParameter('image', $image)
                ->getQuery()
                ->getResult();

            foreach ($entitiesWithImage as $entity) {
                if (method_exists($entity, 'removeUploadedImage')) {
                    $entity->removeUploadedImage($image);
                    $this->entityManager->persist($entity);
                }
            }
        }
    }
}
