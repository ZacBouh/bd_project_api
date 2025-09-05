<?php

namespace App\Service;

use App\DTO\Publisher\PublisherDTOBuilder;
use App\DTO\Publisher\PublisherDTOFactory;
use App\DTO\Publisher\PublisherWriteDTO;
use App\DTO\Publisher\PublisherReadDTO;
use App\Repository\PublisherRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Publisher;
use App\Mapper\PublisherEntityMapper;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PublisherManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PublisherRepository $publisherRepository,
        private UploadedImageService $imageService,
        private ValidatorInterface $validator,
        private PublisherEntityMapper $publisherMapper,
        private PublisherDTOFactory $dtoFactory,
    ) {}

    /**
     * @param InputBag<scalar> $newPublisherContent
     */
    public function createPublisher(InputBag $newPublisherContent, FileBag $files): Publisher
    {
        $dto = $this->dtoFactory->writeDtoFromInputBag($newPublisherContent, $files);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }
        $coverImage = null;
        if (!is_null($dto->coverImageFile)) {
            $coverImage = $this->imageService->saveUploadedImage($dto->coverImageFile, 'Publisher Logo');
        }
        $entity = $this->publisherMapper->fromWriteDTO($dto, extra: ['coverImage' => $coverImage]);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    /**
     * @return PublisherReadDTO[]
     */
    public function getPublishers(): array
    {
        $data = [];
        $publishers = $this->publisherRepository->findWithAllRelations();
        foreach ($publishers as $publisher) {
            $data[] = $this->dtoFactory->readDtoFromEntity($publisher);
        }
        return $data;
    }
}
