<?php

namespace App\Service;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\DTO\UploadedImage\UploadedImageDTOFactory;
use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Entity\Artist;
use App\Entity\Copy;
use App\Entity\Publisher;
use App\Entity\PublisherCollection;
use App\Entity\Series;
use App\Entity\Title;
use App\Entity\UploadedImage;
use App\Mapper\UploadedImageEntityMapper;
use App\Repository\UploadedImageRepository;
use App\Security\Role;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
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
        private UploadedImageEntityMapper $imageMapper,
        private UploadedImageDTOFactory $dtoFactory,
        private Security $security,
        private LoggerInterface $logger,
    ) {}

    public function saveUploadedCoverImage(HasUploadedImagesInterface $entity, FileBag $files, string $imageName = 'Cover Image'): void
    {
        $file = $files->get('coverImageFile');
        if (is_null($file)) {
            return;
        }
        if (!$file instanceof UploadedFile) {
            throw new InvalidArgumentException('File argument passed to saveUploadedCoverImage is not a FileBag');
        }
        $coverImage = $this->saveUploadedImage($file, $imageName);
        $entity->setCoverImage($coverImage);
    }

    public function saveUploadedImage(UploadedFile $file, string $imageName = 'unnamed'): UploadedImage
    {
        $image = (new UploadedImage())
            ->setFile($file)
            ->setImageName($imageName);
        $this->entityManager->persist($image);
        $this->entityManager->flush();
        return $image;
    }

    /**
     * @return list<UploadedImageReadDTO>
     */
    public function getAllImages(): array
    {
        $this->assertAdminAccess();

        /** @var list<UploadedImage> $images */
        $images = $this->imageRepository->createQueryBuilder('image')
            ->orderBy('image.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn(UploadedImage $image) => $this->dtoFactory->readDtoFromEntity($image), $images);
    }

    public function updateImage(int $imageId, InputBag $inputBag, FileBag $files): UploadedImageReadDTO
    {
        $this->assertAdminAccess();

        $inputBag->set('id', $imageId);
        $dto = $this->dtoFactory->writeDtoFromInputBag($inputBag, $files);

        if (!$dto->hasImageNameUpdate && is_null($dto->imageFile)) {
            throw new InvalidArgumentException('No modification provided for the uploaded image.');
        }

        $image = $this->imageMapper->fromWriteDTO($dto);

        $this->logger->info(sprintf('Updating uploaded image %d', $imageId));

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        return $this->dtoFactory->readDtoFromEntity($image);
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
