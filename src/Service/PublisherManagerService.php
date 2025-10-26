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
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\UploadedImageService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Security\Role;

class PublisherManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PublisherRepository $publisherRepository,
        private UploadedImageService $imageService,
        private ValidatorInterface $validator,
        private PublisherEntityMapper $publisherMapper,
        private PublisherDTOFactory $dtoFactory,
        private LoggerInterface $logger,
        private Security $security,
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
     * @param InputBag<scalar> $inputBag
     */
    public function updatePublisher(InputBag $inputBag, FileBag $files): Publisher
    {
        $dto = $this->dtoFactory->writeDtoFromInputBag($inputBag, $files);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }
        if (is_null($dto->id)) {
            throw new InvalidArgumentException('Update publisher : id is null');
        }
        /** @var Publisher|null $publisher */
        $publisher = $this->publisherRepository->find($dto->id);
        if (is_null($publisher)) {
            throw new ResourceNotFoundException('No publisher was found for id ' . $dto->id);
        }
        $extra = [];
        if (!is_null($dto->coverImageFile)) {
            $extra['coverImage'] = $this->imageService->saveUploadedImage($dto->coverImageFile, 'Publisher Logo');
        }
        if ($publisher->isDeleted()) {
            throw new ResourceNotFoundException('No publisher was found for id ' . $dto->id);
        }

        /** @var Publisher $publisher */
        $publisher = $this->publisherMapper->fromWriteDTO($dto, $publisher, $extra);
        $this->entityManager->persist($publisher);
        $this->entityManager->flush();
        return $publisher;
    }

    public function removePublisher(int $publisherId, bool $hardDelete = false): void
    {
        /** @var Publisher|null $publisher */
        $publisher = $this->publisherRepository->find($publisherId);
        if (is_null($publisher)) {
            throw new ResourceNotFoundException('No publisher was found for id ' . $publisherId);
        }
        if ($publisher->isDeleted() && !$hardDelete) {
            throw new ResourceNotFoundException('No publisher was found for id ' . $publisherId);
        }

        if ($hardDelete) {
            if (!$this->security->isGranted(Role::ADMIN->value)) {
                throw new AccessDeniedException('Hard delete requires administrator role');
            }
            $this->entityManager->remove($publisher);
        } else {
            $publisher->markAsDeleted();
            $this->entityManager->persist($publisher);
        }
        $this->entityManager->flush();
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

    /**
     * @return Publisher[]
     */
    public function searchPublisher(string $query, int $limit = 200, int $offset = 0): array
    {
        if (trim($query, " \n\r\t\v\0") === '') {
            throw new InvalidArgumentException('The query string is empty');
        }
        $queryWords = preg_split('/\s+/', trim($query));
        if ($queryWords === false) {
            throw new InvalidArgumentException('The query does not contain valid words');
        }
        $queryWords = array_map(fn($word) => "$word*", $queryWords);
        $query = implode($queryWords);
        $this->logger->debug("Searching Publishers with query $query");
        $publishers = $this->publisherRepository->searchPublisher($query, $limit, $offset);

        $this->logger->debug(sprintf("Found %s publisher", count($publishers)));

        return $publishers;
    }
}
