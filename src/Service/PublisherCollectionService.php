<?php

namespace App\Service;

use App\DTO\PublisherCollection\PublisherCollectionDTOFactory;
use App\DTO\PublisherCollection\PublisherCollectionReadDTO;
use App\Entity\PublisherCollection;
use App\Mapper\PublisherCollectionMapper;
use App\Repository\PublisherCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use InvalidArgumentException;
use App\Service\UploadedImageService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Security\Role;

class PublisherCollectionService
{
    public function __construct(
        private ValidatorInterface $validator,
        private PublisherCollectionMapper $mapper,
        private EntityManagerInterface $em,
        private UploadedImageService $imageService,
        private PublisherCollectionRepository $repo,
        private PublisherCollectionDTOFactory $dtoFactory,
        private Security $security,
    ) {}

    /**
     * @param InputBag<scalar> $inputBag
     */
    public function createPublisherCollection(InputBag $inputBag, FileBag $files): PublisherCollection
    {
        $dto = $this->dtoFactory->writeDtoFromInputBag($inputBag, $files);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }
        $coverImage = null;
        $extra = [];
        if (!is_null($dto->coverImageFile)) {
            $coverImage = $this->imageService->saveUploadedImage($dto->coverImageFile, 'Collection Logo');
            $extra['coverImage'] = $coverImage;
        }
        $entity = $this->mapper->fromWriteDTO($dto, null, $extra);
        $this->em->persist($entity);
        $this->em->flush();
        return $entity;
    }

    /**
     * @param InputBag<scalar> $inputBag
     */
    public function updatePublisherCollection(InputBag $inputBag, FileBag $files): PublisherCollection
    {
        $dto = $this->dtoFactory->writeDtoFromInputBag($inputBag, $files);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }
        if (is_null($dto->id)) {
            throw new InvalidArgumentException('Update collection : id is null');
        }
        /** @var PublisherCollection|null $collection */
        $collection = $this->repo->find($dto->id);
        if (is_null($collection)) {
            throw new ResourceNotFoundException('No collection was found for id ' . $dto->id);
        }
        if ($collection->isDeleted()) {
            throw new ResourceNotFoundException('No collection was found for id ' . $dto->id);
        }
        $extra = [];
        if (!is_null($dto->coverImageFile)) {
            $extra['coverImage'] = $this->imageService->saveUploadedImage($dto->coverImageFile, 'Collection Logo');
        }
        $collection = $this->mapper->fromWriteDTO($dto, $collection, $extra);
        $this->em->persist($collection);
        $this->em->flush();
        return $collection;
    }

    public function removePublisherCollection(int $collectionId, bool $hardDelete = false): void
    {
        /** @var PublisherCollection|null $collection */
        $collection = $this->repo->find($collectionId);
        if (is_null($collection)) {
            throw new ResourceNotFoundException('No collection was found for id ' . $collectionId);
        }
        if ($collection->isDeleted() && !$hardDelete) {
            throw new ResourceNotFoundException('No collection was found for id ' . $collectionId);
        }

        if ($hardDelete) {
            if (!$this->security->isGranted(Role::ADMIN->value)) {
                throw new AccessDeniedException('Hard delete requires administrator role');
            }
            $this->em->remove($collection);
        } else {
            $collection->markAsDeleted();
            $this->em->persist($collection);
        }
        $this->em->flush();
    }

    /**
     * @return PublisherCollectionReadDTO[]
     */
    public function getPublisherCollections(): array
    {
        $collections = $this->repo->findAllWithPublisherAndImages();
        $dtos = array_map(fn($collection) => $this->dtoFactory->readDtoFromEntity($collection), $collections);
        return $dtos;
    }
}
