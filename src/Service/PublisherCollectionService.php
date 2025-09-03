<?php

namespace App\Service;

use App\DTO\PublisherCollection\PublisherCollectionDTOBuilder;
use App\DTO\PublisherCollection\PublisherCollectionReadDTO;
use App\Entity\PublisherCollection;
use App\Mapper\PublisherCollectionMapper;
use App\Repository\PublisherCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PublisherCollectionService
{
    public function __construct(
        private ValidatorInterface $validator,
        private PublisherCollectionMapper $mapper,
        private PublisherCollectionDTOBuilder $dtoBuilder,
        private EntityManagerInterface $em,
        private UploadedImageService $imageService,
        private PublisherCollectionRepository $repo,
    ) {}

    /**
     * @param InputBag<string> $inputBag
     */
    public function createPublisherCollection(InputBag $inputBag, FileBag $files): PublisherCollection
    {
        $dto = $this->dtoBuilder->writeDTOFromInputBags($inputBag, $files)
            ->buildWriteDTO();
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }
        $coverImage = null;
        if (!is_null($dto->coverImageFile)) {
            $coverImage = $this->imageService->saveUploadedImage($dto->coverImageFile, 'Collection Logo');
        }
        $entity = $this->mapper->fromWriteDTO($dto, null, ['coverImage' => $coverImage]);
        $this->em->persist($entity);
        $this->em->flush();
        return $entity;
    }

    /**
     * @return PublisherCollectionReadDTO[]
     */
    public function getPublisherCollections(): array
    {
        $collections = $this->repo->findAllWithPublisherAndImages();
        $dtos = array_map(fn($collection) => $this->dtoBuilder->readDTOFromEntity($collection)->buildReadDTO(), $collections);
        return $dtos;
    }
}
